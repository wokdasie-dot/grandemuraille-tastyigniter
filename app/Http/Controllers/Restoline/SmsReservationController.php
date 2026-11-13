<?php

namespace App\Http\Controllers\Restoline;

use App\Http\Controllers\Controller;
use App\Services\Restoline\PrivateEnvironment;
use App\Services\Restoline\TwilioRequestValidator;
use App\Services\Restoline\TwilioSmsService;
use App\Services\Restoline\VoiceReservationService;
use App\Services\Restoline\SmsReservationConversationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Restoline\Content\Models\SmsGatewayMessage;
use Restoline\Content\Services\RestaurantNotificationService;
use Restoline\Content\Services\TelephonyEventLogger;

final class SmsReservationController extends Controller
{
    private const SMS_LIMIT_PER_HOUR = 6;
    private const SMS_LIMIT_PER_DAY = 20;
    private const UNKNOWN_ATTEMPT_LINK_STEPS = [3, 5];

    public function __construct(
        private readonly VoiceReservationService $reservations,
        private readonly TwilioRequestValidator $validator,
        private readonly TwilioSmsService $sms,
        private readonly SmsReservationConversationService $conversationService,
    ) {}

    public function incoming(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }

        $from = $this->normalizePhone((string)$request->input('From', ''));
        $body = trim((string)$request->input('Body', ''));
        $messageSid = (string)$request->input('MessageSid', '');

        if ($from === '' || $body === '') {
            return $this->emptyTwiml();
        }

        $this->telemetry('sms_incoming', $request, [
            'message_sid' => $messageSid,
            'payload' => [
                'body_length' => mb_strlen($body),
            ],
        ]);

        try {
            // GSM/SMSGate et Twilio utilisent le même moteur IA, les mêmes
            // contrôles et le même historique de conversation.
            $this->conversationService->handleIncoming(
                $from,
                $body,
                ['source' => 'twilio', 'to' => (string)$request->input('To', '')],
                $messageSid !== '' ? $messageSid : null,
            );
        } catch (\Throwable $exception) {
            Log::error('Reservation SMS non traitee.', [
                'from_hash' => sha1($from),
                'body_length' => mb_strlen($body),
                'error' => $exception->getMessage(),
            ]);
            $this->recordSmsEvent('sms_error', $from, [
                'message_sid' => $messageSid,
                'status' => 'error',
                'payload' => [
                    'body_length' => mb_strlen($body),
                    'error' => $exception->getMessage(),
                ],
            ]);
            $this->reply($from, "Je suis desolee, votre SMS n'a pas pu etre traite. Merci d'appeler le 087 22 08 88.");
        }

        return $this->emptyTwiml();
    }

    public function status(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }

        Log::info('Twilio SMS status callback.', [
            'message_sid' => $request->input('MessageSid'),
            'message_status' => $request->input('MessageStatus'),
            'to_hash' => $this->phoneHash($request->input('To')),
            'from_hash' => $this->phoneHash($request->input('From')),
            'error_code' => $request->input('ErrorCode'),
            'error_message' => $request->input('ErrorMessage'),
        ]);
        $this->telemetry('sms_status', $request, [
            'message_sid' => $request->input('MessageSid'),
            'status' => $request->input('MessageStatus'),
            'payload' => [
                'error_code' => $request->input('ErrorCode'),
                'error_message' => $request->input('ErrorMessage'),
            ],
        ]);

        return $this->emptyTwiml();
    }

    public function gatewayStatus(Request $request, string $token): Response
    {
        $expected = (string)PrivateEnvironment::get('SMS_GATEWAY_WEBHOOK_TOKEN', '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            return response('Webhook token invalide.', 403);
        }

        $event = (string)$request->input('event', 'sms:unknown');
        $payload = $request->input('payload', []);
        $payload = is_array($payload) ? $payload : ['value' => $payload];
        $messageId = $this->payloadString($payload, ['messageId', 'message_id', 'id']);
        $status = $this->gatewayStatusFromEvent($event, $payload);

        Log::info('SMS Gateway status webhook.', [
            'event' => $event,
            'message_id' => $messageId,
            'status' => $status,
        ]);

        app(TelephonyEventLogger::class)->record('sms', 'sms_gateway_status', [
            'message_sid' => $messageId,
            'from' => $this->payloadString($payload, ['sender', 'from']),
            'to' => $this->payloadString($payload, ['recipient', 'phoneNumber', 'to']),
            'status' => $status,
            'payload' => [
                'provider' => 'sms_gateway',
                'event' => $event,
                'reason' => $this->payloadString($payload, ['reason', 'error', 'errorMessage']),
                'raw' => $payload,
            ],
        ]);

        if ($messageId !== null) {
            $message = SmsGatewayMessage::query()->where('provider_message_id', $messageId)->first();
            if ($message) {
                $message->status = $status;
                $message->last_status_at = now();
                $message->last_payload = $payload;
                $message->save();
            }
        }

        return response('OK', 200)->header('Cache-Control', 'no-store');
    }

    private function gatewayStatusFromEvent(string $event, array $payload): string
    {
        $state = $this->payloadString($payload, ['state', 'status']);
        if ($state !== null) {
            return $state;
        }

        return match ($event) {
            'sms:sent' => 'Sent',
            'sms:delivered' => 'Delivered',
            'sms:failed' => 'Failed',
            'sms:cancelled' => 'Cancelled',
            default => $event,
        };
    }

    private function payloadString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $value = trim((string)$payload[$key]);
                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private function handleMessage(string $from, string $body): void
    {
        $normalized = $this->normalizeText($body);
        $pendingKey = $this->pendingKey($from);
        $pending = Cache::get($pendingKey);

        if ($this->isYes($normalized) && is_array($pending)) {
            $this->recordSmsEvent('sms_modify_request', $from, [
                'payload' => [
                    'body_length' => mb_strlen($body),
                    'source' => 'pending_yes',
                ],
            ]);
            $this->reply($from, $this->manageReservationMessage());
            Cache::forget($pendingKey);
            return;
        }

        if ($this->isCancelRequest($normalized)) {
            Cache::forget($pendingKey);
            $this->recordSmsEvent('sms_cancel_request', $from, [
                'payload' => [
                    'body_length' => mb_strlen($body),
                ],
            ]);
            $this->reply($from, $this->manageReservationMessage());
            return;
        }

        if ($this->isModifyRequest($normalized)) {
            Cache::forget($pendingKey);
            $this->recordSmsEvent('sms_modify_request', $from, [
                'payload' => [
                    'body_length' => mb_strlen($body),
                ],
            ]);
            $this->reply($from, $this->manageReservationMessage());
            return;
        }

        $payload = $this->parseReservationText($body, true);
        if (!$payload) {
            $eventType = $this->isReservationIntent($normalized) ? 'sms_incomplete' : 'sms_unknown';
            $this->recordSmsEvent($eventType, $from, [
                'payload' => [
                    'body_length' => mb_strlen($body),
                    'intent' => $this->isReservationIntent($normalized) ? 'reservation' : 'unknown',
                ],
            ]);
            $this->reply($from, $this->isReservationIntent($normalized)
                ? $this->incompleteReservationMessage()
                : $this->unknownMessage($from));
            return;
        }

        Cache::forget($this->unknownAttemptsKey($from));

        $slots = $this->availableSlotsOrEmpty($payload);
        if (!in_array($payload['time'], $slots, true)) {
            $this->recordSmsEvent('sms_unavailable', $from, [
                'payload' => [
                    'date' => $payload['date'],
                    'time' => $payload['time'],
                    'guests' => $payload['guests'],
                ],
            ]);
            $this->reply($from, $this->unavailableMessage());
            return;
        }

        $reservation = $this->reservations->createReservation([
            'name' => $payload['name'],
            'phone' => $from,
            'guests' => $payload['guests'],
            'date' => $payload['date'],
            'time' => $payload['time'],
            'service' => $this->serviceFromTime($payload['time']),
            'status_id' => (int)setting('confirmed_reservation_status'),
            'comment' => trim('Reservation recue par SMS.'.($payload['comment'] ? "\n".$payload['comment'] : '')),
        ]);

        app(RestaurantNotificationService::class)->sendReservationEvent($reservation, 'new_reservation');
        $this->recordSmsEvent('sms_reservation_created', $from, [
            'reservation_id' => $reservation->getKey(),
            'payload' => [
                'date' => $payload['date'],
                'time' => $payload['time'],
                'guests' => $payload['guests'],
            ],
        ]);
    }

    private function parseReservationText(string $body, bool $requireName): ?array
    {
        $text = trim($body);
        $dateMatch = $this->matchDate($text);
        if (!$dateMatch) {
            return null;
        }

        $date = $this->parseDate((int)$dateMatch['day'], (int)$dateMatch['month'], $dateMatch['year']);
        if (!$date) {
            return null;
        }

        $beforeDate = trim(substr($text, 0, $dateMatch['offset']));
        $afterDate = trim(substr($text, $dateMatch['offset'] + strlen($dateMatch['raw'])));
        $afterDate = $this->stripReservationWords($afterDate);
        $beforeDate = $this->stripReservationWords($beforeDate);

        if (!preg_match('/\b([01]?\d|2[0-3])\s*(?:h|:|\.)\s*([0-5]\d)?\b/i', $afterDate, $timeMatch, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $hour = (int)$timeMatch[1][0];
        $minute = isset($timeMatch[2][0]) && $timeMatch[2][0] !== '' ? (int)$timeMatch[2][0] : 0;
        $time = sprintf('%02d:%02d', $hour, $minute);

        $afterTime = trim(substr($afterDate, $timeMatch[0][1] + strlen($timeMatch[0][0])));
        if (!$this->matchGuests($afterTime, $guestMatch)) {
            return null;
        }
        $guests = (int)$guestMatch[1][0];
        if ($guests < 1 || $guests > 30) {
            return null;
        }

        $name = $this->cleanName($beforeDate);
        if ($requireName && mb_strlen($name) < 2) {
            return null;
        }

        $comment = trim(substr($afterTime, $guestMatch[0][1] + strlen($guestMatch[0][0])));
        $comment = trim(preg_replace('/^[\s,.;:-]+/', '', $comment) ?? $comment);

        return [
            'name' => $name ?: 'Client SMS',
            'date' => $date->format('Y-m-d'),
            'time' => $time,
            'guests' => $guests,
            'comment' => $comment,
        ];
    }

    private function matchDate(string $text): ?array
    {
        if (preg_match('/\b(\d{1,2})\s*(?:[\/\-.]|du|de)\s*(\d{1,2})(?:(?:\s*[\/\-.]\s*|\s+de\s+)(\d{2,4}))?\b/i', $text, $match, PREG_OFFSET_CAPTURE)) {
            $day = (int)$match[1][0];
            $month = (int)$match[2][0];
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                return [
                    'raw' => $match[0][0],
                    'offset' => $match[0][1],
                    'day' => $match[1][0],
                    'month' => $match[2][0],
                    'year' => $match[3][0] ?? null,
                ];
            }
        }

        if (preg_match('/\b(\d{1,2})\s+(janvier|janv|fevrier|fevrier|février|fev|févr|mars|avril|avr|mai|juin|juillet|juil|aout|août|septembre|sept|octobre|oct|novembre|nov|decembre|décembre|dec|déc)\s*(\d{2,4})?\b/iu', $text, $match, PREG_OFFSET_CAPTURE)) {
            $month = $this->monthNumber($match[2][0]);
            if ($month === null) {
                return null;
            }

            return [
                'raw' => $match[0][0],
                'offset' => $match[0][1],
                'day' => $match[1][0],
                'month' => (string)$month,
                'year' => $match[3][0] ?? null,
            ];
        }

        return null;
    }

    private function monthNumber(string $month): ?int
    {
        $month = mb_strtolower($month);
        $month = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $month) ?: $month;
        $month = preg_replace('/[^a-z]/', '', $month) ?? $month;

        return [
            'janvier' => 1, 'janv' => 1,
            'fevrier' => 2, 'fev' => 2, 'fevr' => 2,
            'mars' => 3,
            'avril' => 4, 'avr' => 4,
            'mai' => 5,
            'juin' => 6,
            'juillet' => 7, 'juil' => 7,
            'aout' => 8,
            'septembre' => 9, 'sept' => 9,
            'octobre' => 10, 'oct' => 10,
            'novembre' => 11, 'nov' => 11,
            'decembre' => 12, 'dec' => 12,
        ][$month] ?? null;
    }

    private function matchGuests(string $text, ?array &$match): bool
    {
        if ($this->matchGuestComposition($text, $match)) {
            return true;
        }

        if (preg_match('/\b(\d{1,2})\s*(personnes?|pers?\.?|pax|couverts?|places?|p)\b/i', $text, $match, PREG_OFFSET_CAPTURE)) {
            return true;
        }

        if (preg_match('/\b(?:pour|a|à|de|table\s+de)\s+(\d{1,2})\b/i', $text, $match, PREG_OFFSET_CAPTURE)) {
            $match[0][1] = $match[1][1];
            $match[0][0] = $match[1][0];
            return true;
        }

        if (preg_match('/^\s*[,.;:-]?\s*(\d{1,2})\b/i', $text, $match, PREG_OFFSET_CAPTURE)) {
            return true;
        }

        return false;
    }

    private function matchGuestComposition(string $text, ?array &$match): bool
    {
        $pattern = '/\b(\d{1,2}|un|une|deux|trois|quatre|cinq|six|sept|huit|neuf|dix)\s*(adultes?|enfants?|bebes?|bébés?|bebe|bébé|personnes?|pers?\.?|couverts?|places?)\b/iu';
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $total = 0;
        $firstOffset = null;
        $lastEnd = null;
        foreach ($matches[1] as $index => $numberMatch) {
            $value = $this->guestNumberValue($numberMatch[0]);
            if ($value === null) {
                continue;
            }

            $total += $value;
            $firstOffset ??= $matches[0][$index][1];
            $lastEnd = $matches[0][$index][1] + strlen($matches[0][$index][0]);
        }

        if ($total < 1 || $firstOffset === null || $lastEnd === null) {
            return false;
        }

        $match = [
            [$lastEnd > $firstOffset ? substr($text, $firstOffset, $lastEnd - $firstOffset) : (string)$total, $firstOffset],
            [(string)$total, $firstOffset],
        ];

        return true;
    }

    private function guestNumberValue(string $number): ?int
    {
        $number = mb_strtolower(trim($number));
        $number = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $number) ?: $number;

        if (preg_match('/^\d{1,2}$/', $number)) {
            return (int)$number;
        }

        return [
            'un' => 1,
            'une' => 1,
            'deux' => 2,
            'trois' => 3,
            'quatre' => 4,
            'cinq' => 5,
            'six' => 6,
            'sept' => 7,
            'huit' => 8,
            'neuf' => 9,
            'dix' => 10,
        ][$number] ?? null;
    }

    private function stripReservationWords(string $text): string
    {
        $text = preg_replace('/\b(bonjour|bonsoir|svp|merci|je\s+voudrais|j[\' ]aimerais|possible\s+de|pourriez[- ]vous|modifier|modifie|modification|reservation|reserver|reserve|table)\b/iu', ' ', $text) ?? $text;
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function cleanName(string $text): string
    {
        $text = preg_replace('/\b(au\s+nom\s+de|nom|pour|mr|monsieur|mme|madame|mlle|mademoiselle)\b/iu', ' ', $text) ?? $text;
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function parseDate(int $day, int $month, ?string $year): ?Carbon
    {
        $timezone = config('app.timezone');
        $today = now($timezone)->startOfDay();
        $year = $year !== null && $year !== '' ? (int)$year : (int)$today->format('Y');
        if ($year < 100) {
            $year += 2000;
        }

        try {
            $date = Carbon::create($year, $month, $day, 0, 0, 0, $timezone)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->format('j') !== (string)$day || $date->format('n') !== (string)$month) {
            return null;
        }
        if ($date->lt($today) && $year === (int)$today->format('Y')) {
            $date->addYear();
        }
        if ($date->lt($today) || $date->gt($today->copy()->addYear())) {
            return null;
        }

        return $date;
    }

    private function serviceFromTime(string $time): string
    {
        return ((int)substr($time, 0, 2)) < 16 ? 'lunch' : 'dinner';
    }

    private function availableSlotsOrEmpty(array $payload): array
    {
        try {
            return $this->reservations->availableSlots(
                $payload['date'],
                $this->serviceFromTime($payload['time']),
                (int)$payload['guests'],
            );
        } catch (\Throwable $exception) {
            Log::warning('Disponibilites SMS non calculees.', [
                'date' => $payload['date'] ?? null,
                'time' => $payload['time'] ?? null,
                'guests' => $payload['guests'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function isCancelRequest(string $text): bool
    {
        return str_contains($text, 'annul')
            || str_contains($text, 'supprim')
            || str_contains($text, 'effac')
            || str_contains($text, 'desist');
    }

    private function isModifyRequest(string $text): bool
    {
        return str_contains($text, 'modif')
            || str_contains($text, 'changer')
            || str_contains($text, 'decaler')
            || str_contains($text, 'deplace')
            || str_contains($text, 'reporter')
            || str_contains($text, 'avance')
            || str_contains($text, 'retard');
    }

    private function isReservationIntent(string $text): bool
    {
        return str_contains($text, 'reserv')
            || str_contains($text, 'reserver')
            || str_contains($text, 'table')
            || str_contains($text, 'je voudrais')
            || str_contains($text, 'j aimerais')
            || str_contains($text, 'j aimerai');
    }

    private function isYes(string $text): bool
    {
        return in_array($text, ['oui', 'ok', 'yes'], true);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s.\/()-]+/', '', trim($phone)) ?? '';
        if (str_starts_with($phone, '00')) {
            return '+'.substr($phone, 2);
        }
        if (str_starts_with($phone, '0')) {
            return '+32'.substr($phone, 1);
        }
        return $phone;
    }

    private function phoneHash(mixed $phone): ?string
    {
        $phone = trim((string)$phone);
        return $phone === '' ? null : sha1($phone);
    }

    private function pendingKey(string $phone): string
    {
        return 'restoline:sms-reservation:pending:'.sha1($phone);
    }

    private function helpMessage(): string
    {
        return "Je ne peux pas traiter votre demande par SMS. Merci de contacter le restaurant La Grande Muraille de Chine : 087 22 08 88 ou jinzhu1031@gmail.com.";
    }

    private function incompleteReservationMessage(): string
    {
        return "Pour reserver par SMS, envoyez : nom, date, heure, nombre de personnes. Exemple : Dupont 30/06/2026 18h30 4p anniversaire. En ligne : https://nouveau.grandemuraillespa.be/grande-muraille/reservation";
    }

    private function manageReservationMessage(): string
    {
        return "Pour modifier ou annuler votre reservation, utilisez la page securisee : https://nouveau.grandemuraillespa.be/reservation/gerer. Contact : 087 22 08 88.";
    }

    private function unknownMessage(string $phone): string
    {
        $key = $this->unknownAttemptsKey($phone);
        $attempts = (int)Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addDay());

        if (in_array($attempts, self::UNKNOWN_ATTEMPT_LINK_STEPS, true)) {
            return "Je ne peux pas traiter votre demande par SMS. Pour reserver en ligne : https://nouveau.grandemuraillespa.be/grande-muraille/reservation. Contact : 087 22 08 88 ou jinzhu1031@gmail.com.";
        }

        return $this->helpMessage();
    }

    private function unknownAttemptsKey(string $phone): string
    {
        return 'restoline:sms-reservation:unknown-attempts:'.sha1($phone);
    }

    private function unavailableMessage(): string
    {
        return "Ce creneau n'est pas disponible. Merci de proposer une autre date ou une autre heure. Exemple : Dupont 28/06/2026 20h00 4p. En ligne : https://nouveau.grandemuraillespa.be/grande-muraille/reservation";
    }

    private function reply(string $phone, string $message): void
    {
        $this->sms->send($phone, $message);
    }

    private function emptyTwiml(): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?><Response/>', 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    private function telemetry(string $eventType, Request $request, array $data = []): void
    {
        app(TelephonyEventLogger::class)->record('sms', $eventType, array_merge([
            'message_sid' => $request->input('MessageSid'),
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'status' => $request->input('MessageStatus'),
        ], $data));
    }

    private function recordSmsEvent(string $eventType, string $from, array $data = []): void
    {
        app(TelephonyEventLogger::class)->record('sms', $eventType, array_merge([
            'from' => $from,
        ], $data));
    }
}
