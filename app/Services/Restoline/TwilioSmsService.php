<?php

namespace App\Services\Restoline;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\SmsGatewayMessage;
use Restoline\Content\Services\TelephonyEventLogger;
use RuntimeException;

final class TwilioSmsService
{
    public function send(string $to, string $message): void
    {
        $normalizedTo = $this->normalizePhone($to);
        $normalizedMessage = $this->normalizeMessage($message);
        $duplicateKey = 'restoline:sms:dedupe:'.sha1($normalizedTo.'|'.$normalizedMessage);

        if (!Cache::add($duplicateKey, true, now()->addMinutes(3))) {
            Log::info('SMS ignore: doublon exact bloque.', [
                'to_hash' => hash('sha256', $normalizedTo),
                'body_hash' => hash('sha256', $normalizedMessage),
            ]);
            return;
        }

        if ($this->smsGatewayEnabled()) {
            try {
                $gatewayResponse = $this->sendViaSmsGateway($normalizedTo, $normalizedMessage);
                $messageId = $this->responseValue($gatewayResponse, ['id', 'messageId', 'message_id']);
                $state = $this->responseValue($gatewayResponse, ['state', 'status']);

                Log::info('SMS accepte par la passerelle GSM.', [
                    'to_hash' => hash('sha256', $normalizedTo),
                    'message_id' => $messageId,
                    'state' => $state,
                ]);

                $this->recordTelemetry('sms_gateway_accepted', $normalizedTo, [
                    'message_id' => $messageId,
                    'state' => $state,
                ]);

                $trackedMessage = $this->trackSmsGatewayMessage($messageId, $normalizedTo, $normalizedMessage, $state, $gatewayResponse);
                $this->scheduleDelayedFallbackCheck($trackedMessage);

                return;
            } catch (\Throwable $exception) {
                Log::warning('Passerelle SMS GSM indisponible, bascule vers Twilio.', [
                    'to_hash' => hash('sha256', $normalizedTo),
                    'error' => $exception->getMessage(),
                ]);

                $this->recordTelemetry('sms_gateway_fallback_twilio', $normalizedTo, [
                    'error' => $exception->getMessage(),
                ]);

                $this->notifyRestaurantGatewayFallback($normalizedTo, $exception->getMessage());
            }
        }

        $this->sendViaTwilio($normalizedTo, $normalizedMessage);
    }

    public function sendFallbackSms(string $to, string $message, string $reason): void
    {
        $normalizedTo = $this->normalizePhone($to);
        $normalizedMessage = $this->normalizeMessage($message);

        $this->sendViaTwilio($normalizedTo, $normalizedMessage);
        $this->recordTelemetry('sms_gateway_fallback_twilio', $normalizedTo, [
            'error' => $reason,
            'delayed' => true,
        ]);
        $this->notifyRestaurantGatewayFallback($normalizedTo, $reason);
    }

    private function sendViaTwilio(string $normalizedTo, string $normalizedMessage): void
    {
        $sid = PrivateEnvironment::get('TWILIO_ACCOUNT_SID', '');
        $token = PrivateEnvironment::get('TWILIO_AUTH_TOKEN', '');
        $from = PrivateEnvironment::get('TWILIO_FROM', '');

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Configuration Twilio incomplete.');
        }

        $handle = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json");
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'To' => $normalizedTo,
                'From' => $from,
                'Body' => $normalizedMessage,
                'StatusCallback' => url('/api/restoline/sms/status'),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $sid.':'.$token,
        ]);

        curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : "Twilio a repondu avec le statut {$status}.");
        }

        Log::info('SMS envoye via Twilio.', [
            'to_hash' => hash('sha256', $normalizedTo),
        ]);

        $this->recordTelemetry('sms_twilio_sent', $normalizedTo);
    }

    private function sendViaSmsGateway(string $normalizedTo, string $normalizedMessage): array
    {
        $baseUrl = rtrim((string)PrivateEnvironment::get('SMS_GATEWAY_BASE_URL', 'https://api.sms-gate.app'), '/');
        $username = (string)PrivateEnvironment::get('SMS_GATEWAY_USERNAME', '');
        $password = (string)PrivateEnvironment::get('SMS_GATEWAY_PASSWORD', '');
        $bearerToken = (string)PrivateEnvironment::get('SMS_GATEWAY_BEARER_TOKEN', '');
        $deviceId = (string)PrivateEnvironment::get('SMS_GATEWAY_DEVICE_ID', '');

        if ($baseUrl === '' || ($bearerToken === '' && ($username === '' || $password === ''))) {
            throw new RuntimeException('Configuration SMS Gateway incomplete.');
        }

        $payload = [
            'phoneNumbers' => [$normalizedTo],
            'textMessage' => [
                'text' => $normalizedMessage,
            ],
            'withDeliveryReport' => true,
        ];

        if ($deviceId !== '') {
            $payload['deviceId'] = $deviceId;
        }

        $apiPath = str_contains($baseUrl, 'sms-gate.app') ? '/3rdparty/v1/messages' : '/api/3rdparty/v1/messages';
        $handle = curl_init($baseUrl.$apiPath);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: '.$this->smsGatewayAuthorizationHeader($username, $password, $bearerToken),
                'Content-Type: application/json',
                'User-Agent: Restoline/1.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(5, (int)PrivateEnvironment::get('SMS_GATEWAY_TIMEOUT', '12')),
        ]);

        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : "SMS Gateway a repondu avec le statut {$status}.");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('SMS Gateway a renvoye une reponse illisible.');
        }

        $state = strtolower((string)$this->responseValue($decoded, ['state', 'status']));
        if (in_array($state, ['failed', 'error', 'rejected', 'cancelled', 'canceled'], true)) {
            throw new RuntimeException('SMS Gateway a refuse le SMS: '.$state);
        }

        return $decoded;
    }

    private function smsGatewayEnabled(): bool
    {
        return filter_var(PrivateEnvironment::get('SMS_GATEWAY_ENABLED', '0'), FILTER_VALIDATE_BOOL);
    }

    private function trackSmsGatewayMessage(?string $messageId, string $normalizedTo, string $normalizedMessage, ?string $state, array $gatewayResponse): ?SmsGatewayMessage
    {
        try {
            if (!class_exists(SmsGatewayMessage::class)) {
                return null;
            }

            return SmsGatewayMessage::query()->updateOrCreate(
                [
                    'provider_message_id' => $messageId ?: 'local-'.sha1($normalizedTo.'|'.$normalizedMessage.'|'.microtime(true)),
                ],
                [
                    'to_number' => $normalizedTo,
                    'message_body' => $normalizedMessage,
                    'status' => $state ?: 'accepted',
                    'accepted_at' => now(),
                    'fallback_due_at' => now()->addMinute(),
                    'last_payload' => $gatewayResponse,
                ],
            );
        } catch (\Throwable $exception) {
            Log::warning('Suivi SMS Gateway non enregistre.', [
                'to_hash' => hash('sha256', $normalizedTo),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function scheduleDelayedFallbackCheck(?SmsGatewayMessage $message): void
    {
        if (!$message || !$message->getKey()) {
            return;
        }

        $delaySeconds = max(65, (int)PrivateEnvironment::get('SMS_GATEWAY_FALLBACK_DELAY_SECONDS', '75'));
        $script = sprintf(
            'sleep %d; cd %s && php artisan restoline:check-sms-gateway-fallbacks --id=%d >> storage/logs/restoline-sms-gateway-fallbacks.log 2>&1',
            $delaySeconds,
            escapeshellarg(base_path()),
            (int)$message->getKey(),
        );

        try {
            @exec('nohup bash -lc '.escapeshellarg($script).' >/dev/null 2>&1 &');
            Log::info('Controle differe SMS Gateway planifie.', [
                'sms_gateway_message_id' => $message->getKey(),
                'delay_seconds' => $delaySeconds,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Controle differe SMS Gateway non planifie.', [
                'sms_gateway_message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyRestaurantGatewayFallback(string $normalizedTo, string $reason): void
    {
        $alertKey = 'restoline:sms-gateway:fallback-alert:'.sha1($normalizedTo.'|'.$reason);
        if (!Cache::add($alertKey, true, now()->addMinutes(30))) {
            return;
        }

        $to = (string)PrivateEnvironment::get('RESTAURANT_NOTIFICATION_EMAIL', env('RESTAURANT_NOTIFICATION_EMAIL', 'jinzhu1031@gmail.com'));
        if (trim($to) === '') {
            return;
        }

        try {
            Mail::raw(
                implode("\n", [
                    "La Grande Muraille de Chine",
                    '',
                    'La passerelle SMS via GSM n\'a pas accepte un envoi.',
                    'Le site a bascule automatiquement vers Twilio en secours.',
                    '',
                    'Numero client: '.$normalizedTo,
                    'Erreur technique: '.$reason,
                    '',
                    'Action conseillee: verifier que le telephone GSM est allume, connecte a Internet, et que le service SMS Gateway est en ligne.',
                ]),
                fn($mail) => $mail->to($to)->subject('Alerte SMS - bascule Twilio'),
            );
        } catch (\Throwable $exception) {
            Log::warning('Email alerte fallback SMS non envoye.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function smsGatewayAuthorizationHeader(string $username, string $password, string $bearerToken): string
    {
        if ($bearerToken !== '') {
            return 'Bearer '.$bearerToken;
        }

        return 'Basic '.base64_encode($username.':'.$password);
    }

    private function recordTelemetry(string $eventType, string $normalizedTo, array $payload = []): void
    {
        try {
            app(TelephonyEventLogger::class)->record('sms', $eventType, [
                'to' => $normalizedTo,
                'status' => $eventType,
                'payload' => $payload,
            ]);
        } catch (\Throwable) {
            // La statistique ne doit jamais bloquer l'envoi SMS.
        }
    }

    private function responseValue(array $response, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($response[$key]) && is_scalar($response[$key])) {
                return (string)$response[$key];
            }
        }

        return null;
    }

    private function normalizeMessage(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = strtr($message, [
            chr(226).chr(128).chr(153) => "'",
            chr(226).chr(128).chr(152) => "'",
            chr(226).chr(128).chr(156) => '"',
            chr(226).chr(128).chr(157) => '"',
            chr(226).chr(128).chr(147) => '-',
            chr(226).chr(128).chr(148) => '-',
            chr(226).chr(128).chr(166) => '...',
        ]);

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $message);
        if ($ascii !== false) {
            $message = $ascii;
        }

        $message = str_replace([
            "La Grande Muraille de Chine : votre code de validation est ",
            "La Grande Muraille de Chine : votre code d'acces est ",
            "La Grande Muraille de Chine : ",
            "reservation confirmee.\n",
            "reservation modifiee.\n",
            "reservation annulee.\n",
            " personne - ",
            " personnes - ",
            "Valable 10 minutes.",
        ], [
            "La Grande Muraille de Chine: code ",
            "La Grande Muraille de Chine: code ",
            "La Grande Muraille de Chine: ",
            "reservation confirmee. ",
            "reservation modifiee. ",
            "reservation annulee. ",
            " pers - ",
            " pers - ",
            "Valable 10 min.",
        ], $message);

        $message = preg_replace('/\s+/', ' ', $message) ?? $message;
        $message = trim($message);

        return preg_replace('/[^\x0A\x0D\x20-\x7E]/', '', $message) ?? $message;
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
}
