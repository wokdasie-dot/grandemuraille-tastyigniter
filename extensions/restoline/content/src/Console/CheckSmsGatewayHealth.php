<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use App\Services\Restoline\PrivateEnvironment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckSmsGatewayHealth extends Command
{
    protected $signature = 'restoline:check-sms-gateway-health
        {--max-age=10 : Minutes sans signe de vie avant alerte}
        {--alert-cooldown=360 : Minutes entre deux alertes identiques}
        {--dry-run : Verifie sans envoyer email}';

    protected $description = 'Alerte si le telephone SMSGate/GSM ne donne plus de signe de vie.';

    public function handle(): int
    {
        if (!filter_var(PrivateEnvironment::get('SMS_GATEWAY_ENABLED', '0'), FILTER_VALIDATE_BOOL)) {
            $this->info('SMSGate desactive.');

            return self::SUCCESS;
        }

        $maxAgeMinutes = max(1, (int)$this->option('max-age'));
        $cooldownMinutes = max(15, (int)$this->option('alert-cooldown'));

        try {
            $devices = $this->fetchDevices();
        } catch (\Throwable $exception) {
            $this->alertRestaurant(
                'api-error',
                'SMSGate - API inaccessible',
                [
                    'Le serveur SMSGate ne repond pas correctement.',
                    'Erreur: '.$exception->getMessage(),
                    '',
                    'Action conseillee: verifier https://sms.restoline.be et le conteneur SMSGate sur le VPS.',
                ],
                $cooldownMinutes,
            );

            $this->error('API SMSGate inaccessible: '.$exception->getMessage());

            return self::SUCCESS;
        }

        if ($devices === []) {
            $this->alertRestaurant(
                'no-device',
                'SMSGate - aucun telephone connecte',
                [
                    'Aucun telephone GSM n est visible dans SMSGate.',
                    '',
                    'Action conseillee: ouvrir l application SMS Gateway sur le telephone, verifier Cloud server, Internet et les identifiants.',
                ],
                $cooldownMinutes,
            );

            $this->warn('Aucun appareil SMSGate visible.');

            return self::SUCCESS;
        }

        $freshest = $this->freshestDevice($devices);
        $lastSeen = $freshest['last_seen'];
        $ageMinutes = $lastSeen ? abs($lastSeen->diffInMinutes(now())) : null;

        if ($lastSeen === null || $ageMinutes === null || $ageMinutes > $maxAgeMinutes) {
            $this->alertRestaurant(
                'stale-device',
                'SMSGate - telephone GSM probablement deconnecte',
                [
                    'Le telephone SMSGate ne donne plus de signe de vie recent.',
                    'Dernier signe de vie: '.($lastSeen ? $lastSeen->timezone(config('app.timezone', 'Europe/Brussels'))->format('d/m/Y H:i:s') : 'inconnu'),
                    'Age estime: '.($ageMinutes !== null ? $ageMinutes.' minute(s)' : 'inconnu'),
                    'Appareil: '.($freshest['name'] ?: $freshest['id'] ?: 'inconnu'),
                    '',
                    'Action conseillee: ouvrir l application SMS Gateway sur le GSM, verifier Cloud server = ON et Internet disponible.',
                    'Les SMS continueront a basculer vers Twilio si necessaire.',
                ],
                $cooldownMinutes,
            );

            $this->warn('SMSGate stale: dernier signe de vie trop ancien.');

            return self::SUCCESS;
        }

        Cache::forget('restoline:sms-gateway:health-alert:stale-device');
        Cache::forget('restoline:sms-gateway:health-alert:no-device');
        Cache::forget('restoline:sms-gateway:health-alert:api-error');

        $this->info(sprintf(
            'SMSGate OK. Dernier signe de vie il y a %d minute(s).',
            $ageMinutes,
        ));

        return self::SUCCESS;
    }

    private function fetchDevices(): array
    {
        $baseUrl = rtrim((string)PrivateEnvironment::get('SMS_GATEWAY_BASE_URL', 'https://api.sms-gate.app'), '/');
        $username = (string)PrivateEnvironment::get('SMS_GATEWAY_USERNAME', '');
        $password = (string)PrivateEnvironment::get('SMS_GATEWAY_PASSWORD', '');
        $bearerToken = (string)PrivateEnvironment::get('SMS_GATEWAY_BEARER_TOKEN', '');

        if ($baseUrl === '' || ($bearerToken === '' && ($username === '' || $password === ''))) {
            throw new \RuntimeException('Configuration SMS Gateway incomplete.');
        }

        $handle = curl_init($baseUrl.'/api/3rdparty/v1/devices');
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: '.$this->authorizationHeader($username, $password, $bearerToken),
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
            throw new \RuntimeException($error !== '' ? $error : "SMS Gateway a repondu avec le statut {$status}.");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Reponse SMS Gateway illisible.');
        }

        return $decoded;
    }

    private function freshestDevice(array $devices): array
    {
        $freshest = [
            'id' => null,
            'name' => null,
            'last_seen' => null,
        ];

        foreach ($devices as $device) {
            if (!is_array($device)) {
                continue;
            }

            $lastSeen = $this->parseDate($device['lastSeen'] ?? $device['updatedAt'] ?? null);
            if ($freshest['last_seen'] === null || ($lastSeen !== null && $lastSeen->greaterThan($freshest['last_seen']))) {
                $freshest = [
                    'id' => isset($device['id']) ? (string)$device['id'] : null,
                    'name' => isset($device['name']) ? (string)$device['name'] : null,
                    'last_seen' => $lastSeen,
                ];
            }
        }

        return $freshest;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function alertRestaurant(string $key, string $subject, array $lines, int $cooldownMinutes): void
    {
        if ((bool)$this->option('dry-run')) {
            $this->line('[dry-run] '.$subject);

            return;
        }

        $cacheKey = 'restoline:sms-gateway:health-alert:'.$key;
        if (!Cache::add($cacheKey, true, now()->addMinutes($cooldownMinutes))) {
            return;
        }

        $to = (string)PrivateEnvironment::get('RESTAURANT_NOTIFICATION_EMAIL', env('RESTAURANT_NOTIFICATION_EMAIL', 'jinzhu1031@gmail.com'));
        if (trim($to) === '') {
            return;
        }

        try {
            Mail::raw(
                implode("\n", array_merge([
                    "La Grande Muraille de Chine",
                    '',
                ], $lines)),
                fn($mail) => $mail->to($to)->subject($subject),
            );
        } catch (\Throwable $exception) {
            Log::warning('Email alerte SMSGate non envoye.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function authorizationHeader(string $username, string $password, string $bearerToken): string
    {
        if ($bearerToken !== '') {
            return 'Bearer '.$bearerToken;
        }

        return 'Basic '.base64_encode($username.':'.$password);
    }
}
