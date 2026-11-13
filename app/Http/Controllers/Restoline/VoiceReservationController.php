<?php

namespace App\Http\Controllers\Restoline;

use App\Http\Controllers\Controller;
use App\Services\Restoline\TwilioRequestValidator;
use App\Services\Restoline\TwimlResponse;
use App\Services\Restoline\VoiceReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Restoline\Content\Models\TelephonySetting;
use Restoline\Content\Models\VoiceMessage;
use Restoline\Content\Services\TelephonyEventLogger;

final class VoiceReservationController extends Controller
{
    private const CALL_LIMIT_PER_HOUR = 4;
    private const CALL_LIMIT_PER_DAY = 12;
    private const MAX_CALL_SECONDS = 480;
    private const MAX_CALL_STEPS = 18;

    private ?TelephonySetting $telephonySetting = null;

    public function __construct(
        private readonly VoiceReservationService $reservations,
        private readonly TwilioRequestValidator $validator,
    ) {}

    public function incoming(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }

        if ($limited = $this->guardVoiceUsage($request, true)) {
            return $limited;
        }

        $this->telemetry('voice_incoming', $request);

        return $this->mainMenu();
    }

    private function mainMenu(): Response
    {
        $settings = $this->telephonySettings();
        $menuAction = htmlspecialchars(route('restoline.voice.menu'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $recordingAction = htmlspecialchars(route('restoline.voice.recording'), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $xml = '<Response>'
            .'<Gather input="dtmf" action="'.$menuAction.'" method="POST" timeout="0" numDigits="1">'
            .TwimlResponse::say($settings->greeting())
            .'</Gather>'
            .'<Record action="'.$recordingAction.'" method="POST" maxLength="120" timeout="5" playBeep="true" trim="trim-silence"/>'
            .TwimlResponse::say("Je n'ai pas reçu de message. Merci de rappeler pendant les horaires d'ouverture.")
            .'<Hangup/></Response>';

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public function status(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }

        Log::info('Twilio voice status callback.', [
            'call_sid' => $request->input('CallSid'),
            'call_status' => $request->input('CallStatus'),
            'from_hash' => $this->phoneHash($request->input('From')),
            'to_hash' => $this->phoneHash($request->input('To')),
            'direction' => $request->input('Direction'),
            'duration' => $request->input('CallDuration'),
            'answered_by' => $request->input('AnsweredBy'),
        ]);
        $this->telemetry('voice_status', $request, [
            'status' => $request->input('CallStatus'),
            'duration_seconds' => $request->input('CallDuration'),
            'payload' => [
                'direction' => $request->input('Direction'),
                'answered_by' => $request->input('AnsweredBy'),
            ],
        ]);

        return response('<?xml version="1.0" encoding="UTF-8"?><Response/>', 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public function smsNumberVoiceNotice(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $this->telemetry('voice_sms_number_notice', $request);

        return TwimlResponse::finish(
            (string)$this->telephonySettings()->sms_number_notice,
        );
    }

    public function selectMenu(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $digit = (string)$request->input('Digits');
        $this->telemetry('voice_menu_choice', $request, [
            'digit' => $digit,
            'payload' => [
                'option' => match ($digit) {
                    '1' => 'reservation_phone',
                    '2' => 'website_link_sms',
                    '3' => 'opening_hours',
                    '4' => 'buffet_tariffs',
                    default => 'unknown',
                },
            ],
        ]);

        if (!in_array($digit, ['1', '2', '3', '4'], true)) {
            $callSid = $this->callSid($request);
            $state = $this->reservations->state($callSid);
            $invalidMenuAttempts = (int)($state['invalid_menu_attempts'] ?? 0) + 1;
            $this->reservations->merge($callSid, [
                'invalid_menu_attempts' => $invalidMenuAttempts,
            ]);

            if ($invalidMenuAttempts >= 3) {
                $this->telemetry('voice_menu_invalid_limited', $request, [
                    'payload' => [
                        'attempts' => $invalidMenuAttempts,
                    ],
                ]);

                return TwimlResponse::finish(
                    (string)$this->telephonySettings()->session_limited_message,
                );
            }
        } else {
            $this->reservations->merge($this->callSid($request), [
                'invalid_menu_attempts' => 0,
            ]);
        }

        return match ($digit) {
            '1' => $this->recordReservationRequest($request),
            '2' => $this->sendWebsiteLink($request),
            '3' => $this->openingHours($request),
            '4' => $this->buffetTariffs($request),
            default => TwimlResponse::gather(
                (string)$this->telephonySettings()->invalid_menu_message,
                route('restoline.voice.menu'),
                numDigits: 1,
            ),
        };
    }

    private function recordReservationRequest(Request $request): Response
    {
        $this->telemetry('voice_reservation_voicemail_started', $request);

        $action = htmlspecialchars(route('restoline.voice.recording'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<Response>'
            .TwimlResponse::say((string)$this->telephonySettings()->phone_reservation_message)
            .'<Record action="'.$action.'" method="POST" maxLength="120" timeout="5" playBeep="true" trim="trim-silence"/>'
            .TwimlResponse::say("Je n'ai pas reçu de message. Merci de rappeler pendant les horaires d'ouverture.")
            .'<Hangup/></Response>';

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public function reservationRecording(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $recordingUrl = trim((string)$request->input('RecordingUrl', ''));
        $recordingSid = trim((string)$request->input('RecordingSid', ''));
        $duration = (int)$request->input('RecordingDuration', 0);
        $status = $recordingUrl !== '' && $recordingSid !== '' ? 'recorded' : 'missing';
        if ($status === 'recorded' && $duration > 0 && $duration < 6) {
            $status = 'too_short';
        }

        $this->telemetry('voice_reservation_voicemail', $request, [
            'status' => $status,
            'duration_seconds' => $duration,
            'payload' => [
                'caller_phone' => (string)$request->input('From', ''),
                'recording_sid' => $recordingSid,
                'recording_url' => $recordingUrl,
                'recording_mp3_url' => $recordingUrl !== '' ? $recordingUrl.'.mp3' : '',
                'recording_duration' => $duration,
            ],
        ]);

        $message = VoiceMessage::create([
            'call_sid' => (string)$request->input('CallSid', ''),
            'caller_phone' => (string)$request->input('From', ''),
            'to_phone' => (string)$request->input('To', ''),
            'recording_sid' => $recordingSid,
            'recording_url' => $recordingUrl,
            'recording_duration' => $duration,
            'status' => $status === 'recorded' ? 'received' : 'needs_human',
            'payload' => $request->except(['AccountSid', 'ApiVersion']),
            'expires_at' => now()->addDays(7),
        ]);

        if (in_array($status, ['recorded', 'too_short'], true)) {
            $this->processVoiceMessageInBackground($message);
        } else {
            $this->sendVoicemailNotification($request, $recordingUrl, $recordingSid, $duration, $status);
        }

        return TwimlResponse::finish(
            "Merci. Votre demande de reservation est bien enregistree. "
                ."Un SMS de confirmation vous sera envoye apres validation. "
                ."Sans confirmation, merci de contacter le restaurant pendant les horaires d'ouverture.",
        );
    }

    private function processVoiceMessageInBackground(VoiceMessage $message): void
    {
        $artisan = base_path('artisan');
        $command = sprintf(
            '%s %s restoline:process-voice-messages %d > /dev/null 2>&1 &',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($artisan),
            (int)$message->getKey(),
        );

        try {
            exec($command);
        } catch (\Throwable $exception) {
            Log::warning('Traitement IA vocal non lancé.', [
                'message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function buffetTariffs(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $this->telemetry('voice_tariffs_requested', $request);

        $message = (string)$this->telephonySettings()->tariffs_message;

        $xml = '<Response><Gather input="dtmf" action="'.htmlspecialchars(route('restoline.voice.tariffs-action'), ENT_XML1 | ENT_QUOTES, 'UTF-8').'" method="POST" timeout="10" numDigits="1">'
            .TwimlResponse::say($message)
            .'</Gather><Hangup/></Response>';

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public function selectTariffsAction(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        if ((string)$request->input('Digits') === '1') {
            return $this->mainMenu();
        }

        return TwimlResponse::finish("Merci, et a bientot chez La Grande Muraille de Chine.");
    }

    public function openingHours(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $this->telemetry('voice_hours_requested', $request);

        $message = (string)$this->telephonySettings()->opening_hours_message;

        $xml = '<Response><Gather input="dtmf" action="'.htmlspecialchars(route('restoline.voice.tariffs-action'), ENT_XML1 | ENT_QUOTES, 'UTF-8').'" method="POST" timeout="10" numDigits="1">'
            .TwimlResponse::say($message)
            .'</Gather><Hangup/></Response>';

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public function selectDate(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $choice = (string)$request->input('Digits');
        $this->telemetry('voice_date_choice', $request, ['digit' => $choice]);
        if ($choice === '4') {
            return TwimlResponse::gatherSpeechAfterBeep(
                'Apres le bip, dites le jour souhaite. Par exemple : samedi, dimanche midi, ou mardi prochain.',
                route('restoline.voice.exact-date'),
            );
        }

        $offset = match ($choice) {
            '1' => 0,
            '2' => 1,
            '3' => 2,
            default => null,
        };

        if ($offset === null) {
            return TwimlResponse::gather(
                "Je n'ai pas compris. Aujourd'hui, tapez 1. Demain, tapez 2. Apres-demain, tapez 3. Autre jour, tapez 4.",
                route('restoline.voice.date'),
                numDigits: 1,
            );
        }

        $date = now(config('app.timezone'))->addDays($offset)->format('Y-m-d');
        if ($this->reservations->isAnnualLeaveDate($date)) {
            return $this->askAnotherDate(
                $this->callSid($request),
                $this->reservations->annualLeaveUnavailableMessage(),
            );
        }

        $this->reservations->merge($this->callSid($request), ['date' => $date]);

        return TwimlResponse::gather(
            'Pour midi, tapez 1. Pour le soir, tapez 2.',
            route('restoline.voice.service'),
            numDigits: 1,
        );
    }

    public function selectExactDate(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $speech = trim((string)$request->input('SpeechResult', ''));
        $digits = preg_replace('/\D+/', '', (string)$request->input('Digits', '')) ?? '';
        $this->telemetry('voice_exact_date_entered', $request, [
            'payload' => [
                'digits_length' => strlen($digits),
                'speech_length' => mb_strlen($speech),
            ],
        ]);
        $date = $speech !== ''
            ? $this->parseRequestedDateFromSpeech($speech)
            : $this->parseRequestedDateFromDigits($digits);

        $today = now(config('app.timezone'))->startOfDay();
        if (!$date || $date->lt($today) || $date->gt($today->copy()->addYear())) {
            return TwimlResponse::gatherSpeechAfterBeep(
                "Je n'ai pas reconnu le jour. Apres le bip, dites par exemple samedi, dimanche midi, ou mardi prochain.",
                route('restoline.voice.exact-date'),
            );
        }

        if ($date->isMonday()) {
            return TwimlResponse::gatherSpeechAfterBeep(
                'Le restaurant est ferme le lundi. Apres le bip, dites un autre jour.',
                route('restoline.voice.exact-date'),
            );
        }

        if ($this->reservations->isAnnualLeaveDate($date->format('Y-m-d'))) {
            return TwimlResponse::gatherSpeechAfterBeep(
                $this->reservations->annualLeaveUnavailableMessage().' Apres le bip, dites un autre jour.',
                route('restoline.voice.exact-date'),
            );
        }

        $this->reservations->merge($this->callSid($request), [
            'date' => $date->format('Y-m-d'),
        ]);

        return TwimlResponse::gather(
            'Pour midi, tapez 1. Pour le soir, tapez 2.',
            route('restoline.voice.service'),
            numDigits: 1,
        );
    }

    public function selectService(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $service = match ((string)$request->input('Digits')) {
            '1' => 'lunch',
            '2' => 'dinner',
            default => null,
        };
        $this->telemetry('voice_service_choice', $request, [
            'digit' => (string)$request->input('Digits'),
            'payload' => [
                'service' => $service,
            ],
        ]);

        if ($service === null) {
            return TwimlResponse::gather(
                "Je n'ai pas compris. Tapez 1 pour midi, ou 2 pour le soir.",
                route('restoline.voice.service'),
                numDigits: 1,
            );
        }

        $callSid = $this->callSid($request);
        $state = $this->reservations->merge($callSid, ['service' => $service]);
        $slots = $this->reservations->availableSlots($state['date'], $service);
        if ($slots === []) {
            $this->telemetry('voice_unavailable', $request, [
                'payload' => [
                    'date' => $state['date'] ?? null,
                    'service' => $service,
                ],
            ]);

            return $this->askAnotherDate(
                $callSid,
                "Il n'y a plus de disponibilite pour ce service.",
            );
        }

        $this->reservations->merge($callSid, ['slots' => $slots]);
        $prompt = 'Horaires disponibles. ';
        foreach ($slots as $index => $slot) {
            $prompt .= 'Pour '.$this->spokenTime($slot).', tapez '.($index + 1).'. ';
        }

        return TwimlResponse::gather($prompt, route('restoline.voice.time'), numDigits: 1);
    }

    public function selectTime(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $callSid = $this->callSid($request);
        $state = $this->reservations->state($callSid);
        $index = ((int)$request->input('Digits', 0)) - 1;
        $time = $state['slots'][$index] ?? null;
        $this->telemetry('voice_time_choice', $request, [
            'digit' => (string)$request->input('Digits'),
            'payload' => [
                'time' => $time,
            ],
        ]);
        if (!$time) {
            return TwimlResponse::gather(
                "Je n'ai pas reconnu ce choix. Tapez 1 pour midi, ou 2 pour le soir.",
                route('restoline.voice.service'),
                numDigits: 1,
            );
        }

        $this->reservations->merge($callSid, ['time' => $time]);

        return TwimlResponse::gather(
            'Nombre de personnes ? Tapez de 1 a 30, puis diese.',
            route('restoline.voice.guests'),
        );
    }

    public function selectGuests(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $callSid = $this->callSid($request);
        $state = $this->reservations->state($callSid);
        $guests = (int)$request->input('Digits', 0);
        $this->telemetry('voice_guests_entered', $request, [
            'payload' => [
                'guests' => $guests,
            ],
        ]);
        if (!empty($state['confirm_single_guest']) && $guests === 1) {
            $this->reservations->merge($callSid, [
                'guests' => 1,
                'confirm_single_guest' => false,
            ]);

            return $this->askConfirmation($callSid);
        }

        if ($guests < 1 || $guests > 30) {
            return TwimlResponse::gather(
                'Tapez un nombre de 1 a 30, puis diese.',
                route('restoline.voice.guests'),
            );
        }

        if ($guests === 1 && empty($state['confirm_single_guest'])) {
            $this->reservations->merge($callSid, ['confirm_single_guest' => true]);

            return TwimlResponse::gather(
                'Vous avez tape 1 personne. Si c est correct, tapez 1. Sinon, tapez le bon nombre.',
                route('restoline.voice.guests'),
            );
        }

        $this->reservations->merge($callSid, [
            'guests' => $guests,
            'confirm_single_guest' => false,
            'name' => 'Client telephone',
        ]);

        return $this->askConfirmation($callSid);
    }

    public function captureName(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $name = trim((string)$request->input('SpeechResult', ''));
        $this->telemetry('voice_name_captured', $request, [
            'payload' => [
                'heard' => $name !== '',
                'length' => mb_strlen($name),
            ],
        ]);
        if ($name === '') {
            return TwimlResponse::gatherSpeechAfterBeep(
                "Je n'ai pas entendu. Apres le bip, dites votre nom lentement.",
                route('restoline.voice.name'),
            );
        }

        $callSid = $this->callSid($request);
        $state = $this->reservations->merge($callSid, ['name' => mb_substr($name, 0, 80)]);
        $summary = sprintf(
            'Recapitulatif : %s, %d personnes, le %s a %s. Confirmer, tapez 1. Recommencer, tapez 2.',
            $state['name'],
            $state['guests'],
            Carbon::parse($state['date'])->locale('fr')->isoFormat('D MMMM'),
            $this->spokenTime($state['time']),
        );

        return TwimlResponse::gather($summary, route('restoline.voice.confirm'), numDigits: 1);
    }

    private function askConfirmation(string $callSid): Response
    {
        $state = $this->reservations->merge($callSid, ['name' => 'Client telephone']);
        $summary = sprintf(
            'Recapitulatif : %d personnes, le %s a %s. Confirmer, tapez 1. Recommencer, tapez 2.',
            $state['guests'],
            Carbon::parse($state['date'])->locale('fr')->isoFormat('D MMMM'),
            $this->spokenTime($state['time']),
        );

        return TwimlResponse::gather($summary, route('restoline.voice.confirm'), numDigits: 1);
    }

    public function confirm(Request $request): Response
    {
        if (!$this->validator->isValid($request)) {
            return response('Signature Twilio invalide.', 403);
        }
        if ($limited = $this->guardVoiceUsage($request)) {
            return $limited;
        }

        $callSid = $this->callSid($request);
        $this->telemetry('voice_confirm_choice', $request, [
            'digit' => (string)$request->input('Digits'),
        ]);
        if ((string)$request->input('Digits') === '2') {
            $this->reservations->clear($callSid);

            return $this->incoming($request);
        }
        if ((string)$request->input('Digits') !== '1') {
            return TwimlResponse::gather(
                "Je n'ai pas compris. Tapez 1 pour confirmer, ou 2 pour recommencer.",
                route('restoline.voice.confirm'),
                numDigits: 1,
            );
        }

        try {
            $state = $this->reservations->state($callSid);
            $state['status_id'] = (int)setting('confirmed_reservation_status');
            $reservation = $this->reservations->createReservation($state);
            $this->telemetry('voice_reservation_confirmed', $request, [
                'reservation_id' => $reservation->getKey(),
            ]);
            $this->reservations->clear($callSid);

            return TwimlResponse::finish(
                "C'est confirmé. Vous allez recevoir un SMS récapitulatif. "
                    ."Merci, et à bientôt chez La Grande Muraille de Chine.",
            );
        } catch (\Throwable $exception) {
            Log::error('Reservation telephonique non creee.', [
                'call_sid' => $callSid,
                'error' => $exception->getMessage(),
            ]);
            $this->telemetry('voice_reservation_failed', $request, [
                'status' => 'error',
                'payload' => [
                    'error' => $exception->getMessage(),
                ],
            ]);

            return $this->askAnotherDate(
                $callSid,
                "Ce creneau n'est plus disponible.",
            );
        }
    }

    private function callSid(Request $request): string
    {
        return (string)$request->input('CallSid', 'unknown-'.sha1((string)$request->ip()));
    }

    private function guardVoiceUsage(Request $request, bool $newCall = false): ?Response
    {
        $settings = $this->telephonySettings();
        $callSid = $this->callSid($request);
        $phone = $this->normalizePhone((string)$request->input('From', ''));

        if ($newCall) {
            $rateKeyHour = 'restoline:voice-call:phone-hour:'.sha1($phone ?: (string)$request->ip());
            $rateKeyDay = 'restoline:voice-call:phone-day:'.sha1($phone ?: (string)$request->ip());
            if (RateLimiter::tooManyAttempts($rateKeyHour, max(1, (int)$settings->call_limit_per_hour))
                || RateLimiter::tooManyAttempts($rateKeyDay, max(1, (int)$settings->call_limit_per_day))) {
                $this->telemetry('voice_rate_limited', $request);

                return TwimlResponse::finish(
                    (string)$settings->rate_limited_message,
                );
            }

            RateLimiter::hit($rateKeyHour, 3600);
            RateLimiter::hit($rateKeyDay, 86400);
            $state = $this->reservations->start($callSid, $phone);
        } else {
            $state = $this->reservations->touch($callSid, $phone);
        }

        $startedAt = $state['started_at'] ?? now()->toIso8601String();
        try {
            $startedAt = Carbon::parse($startedAt, config('app.timezone'));
        } catch (\Throwable) {
            $startedAt = now(config('app.timezone'));
        }

        if ($startedAt->diffInSeconds(now(config('app.timezone'))) > max(60, (int)$settings->max_call_seconds)
            || (int)($state['steps'] ?? 0) > max(5, (int)$settings->max_call_steps)) {
            $this->reservations->clear($callSid);
            $this->telemetry('voice_session_limited', $request, [
                'payload' => [
                    'steps' => (int)($state['steps'] ?? 0),
                ],
            ]);

            return TwimlResponse::finish(
                (string)$settings->session_limited_message,
            );
        }

        return null;
    }

    private function telephonySettings(): TelephonySetting
    {
        return $this->telephonySetting ??= TelephonySetting::current();
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
        $phone = preg_replace('/[\s.\/()-]+/', '', trim((string)$phone)) ?? '';

        return $phone === '' ? null : hash_hmac('sha256', $phone, (string)config('app.key', 'restoline'));
    }

    private function sendVoicemailNotification(
        Request $request,
        string $recordingUrl,
        string $recordingSid,
        int $duration,
        string $status,
    ): void {
        $to = env('RESTAURANT_NOTIFICATION_EMAIL', 'jinzhu1031@gmail.com');
        if (!is_string($to) || trim($to) === '') {
            $to = 'jinzhu1031@gmail.com';
        }

        $caller = (string)$request->input('From', '');
        $called = (string)$request->input('To', '');
        $callSid = $this->callSid($request);
        $recordingMp3 = $recordingUrl !== '' ? $recordingUrl.'.mp3' : '';

        $body = implode("\n", array_filter([
            'URGENT - Demande de réservation vocale à traiter',
            '',
            'Restaurant : La Grande Muraille de Chine',
            'Heure appel : '.now(config('app.timezone'))->format('d/m/Y H:i:s'),
            'Numéro appelant : '.($caller !== '' ? $caller : 'Numéro masqué ou indisponible'),
            'Numéro appelé : '.($called !== '' ? $called : '-'),
            'Durée message : '.$duration.' seconde(s)',
            'Statut enregistrement : '.$status,
            'Call SID : '.$callSid,
            'Recording SID : '.($recordingSid !== '' ? $recordingSid : '-'),
            $recordingUrl !== '' ? 'Lien écoute : '.$recordingUrl : null,
            $recordingMp3 !== '' ? 'Lien MP3 : '.$recordingMp3 : null,
            '',
            'Action conseillée : écouter le message, encoder ou vérifier la réservation, puis confirmer au client par SMS ou téléphone.',
        ]));

        try {
            Mail::raw($body, function($message) use ($to): void {
                $message->to($to)
                    ->subject('URGENT - Réservation vocale La Grande Muraille de Chine');
            });
        } catch (\Throwable $exception) {
            Log::error('Email reservation vocale non envoye.', [
                'call_sid' => $callSid,
                'error' => $exception->getMessage(),
            ]);
            $this->telemetry('voice_reservation_voicemail_email_failed', $request, [
                'status' => 'error',
                'payload' => [
                    'error' => $exception->getMessage(),
                ],
            ]);
        }
    }

    private function spokenTime(string $time): string
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $minutes === 0 ? "{$hours} heures" : "{$hours} heures {$minutes}";
    }

    private function parseRequestedDateFromDigits(string $digits): ?Carbon
    {
        $timezone = config('app.timezone');
        $today = now($timezone)->startOfDay();

        try {
            if (strlen($digits) === 4) {
                $date = Carbon::createFromFormat('dmY', $digits.$today->format('Y'), $timezone)->startOfDay();
                if ($date->format('dm') !== $digits) {
                    return null;
                }

                return $date->lt($today) ? $date->addYear() : $date;
            }

            if (strlen($digits) === 8) {
                $date = Carbon::createFromFormat('dmY', $digits, $timezone)->startOfDay();
                return $date->format('dmY') === $digits ? $date : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function parseRequestedDateFromSpeech(string $speech): ?Carbon
    {
        $timezone = config('app.timezone');
        $today = now($timezone)->startOfDay();
        $text = Str::ascii(Str::lower($speech));
        $text = preg_replace('/[^a-z0-9\\s]/', ' ', $text) ?? $text;
        $text = preg_replace('/\\s+/', ' ', trim($text)) ?? $text;

        if (str_contains($text, 'aujourd')) {
            return $today;
        }
        if (str_contains($text, 'apres demain') || str_contains($text, 'apres-demain')) {
            return $today->copy()->addDays(2);
        }
        if (str_contains($text, 'demain')) {
            return $today->copy()->addDay();
        }

        $weekdays = [
            'lundi' => Carbon::MONDAY,
            'mardi' => Carbon::TUESDAY,
            'mercredi' => Carbon::WEDNESDAY,
            'jeudi' => Carbon::THURSDAY,
            'vendredi' => Carbon::FRIDAY,
            'samedi' => Carbon::SATURDAY,
            'dimanche' => Carbon::SUNDAY,
        ];

        foreach ($weekdays as $word => $weekday) {
            if (!str_contains($text, $word)) {
                continue;
            }

            $date = $today->copy();
            while ($date->dayOfWeek !== $weekday) {
                $date->addDay();
            }

                if ($date->isSameDay($today)) {
                    $date->addWeek();
                }

            return $date;
        }

        return null;
    }

    private function sendWebsiteLink(Request $request): Response
    {
        $phone = (string)$request->input('From', '');

        try {
            $this->reservations->sendWebsiteLink($phone);
            $this->telemetry('voice_sms_link_requested', $request, ['status' => 'sent']);

            return TwimlResponse::finish(
                'Le lien de réservation vient de vous être envoyé par SMS. '
                    ."Merci, et à bientôt chez La Grande Muraille de Chine.",
            );
        } catch (\Throwable $exception) {
            Log::warning('Lien de reservation non envoye par SMS.', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
            $this->telemetry('voice_sms_link_requested', $request, [
                'status' => 'error',
                'payload' => [
                    'error' => $exception->getMessage(),
                ],
            ]);

            return TwimlResponse::finish(
                "Je suis désolée, le SMS n'a pas pu être envoyé. "
                    .'Vous pouvez réserver sur le site wokdasie point com.',
            );
        }
    }

    private function askAnotherDate(string $callSid, string $reason): Response
    {
        $state = $this->reservations->state($callSid);
        $attempts = (int)($state['unavailable_attempts'] ?? 0) + 1;

        if ($attempts <= 2) {
            $this->reservations->merge($callSid, [
                'unavailable_attempts' => $attempts,
                'date' => null,
                'service' => null,
                'time' => null,
                'slots' => [],
            ]);

            return TwimlResponse::gather(
                $reason.' Choisissez une autre date. Pour aujourd\'hui, tapez 1. '
                    .'Pour demain, tapez 2. Pour une autre date, tapez 3.',
                route('restoline.voice.date'),
                numDigits: 1,
            );
        }

        $this->reservations->clear($callSid);

        return TwimlResponse::finish(
            $reason.' Je ne trouve pas de disponibilité par téléphone. '
                .'Merci de réserver sur notre site ou de rappeler le restaurant pendant les horaires d\'ouverture.',
        );
    }

    private function telemetry(string $eventType, Request $request, array $data = []): void
    {
        app(TelephonyEventLogger::class)->record('voice', $eventType, array_merge([
            'call_sid' => $this->callSid($request),
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'digit' => $request->input('Digits'),
        ], $data));
    }
}
