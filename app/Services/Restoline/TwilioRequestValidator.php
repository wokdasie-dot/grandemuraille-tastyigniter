<?php

namespace App\Services\Restoline;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TwilioRequestValidator
{
    public function isValid(Request $request): bool
    {
        if (app()->environment(['local', 'testing'])
            && PrivateEnvironment::get('RESTOLINE_ALLOW_UNSIGNED_WEBHOOKS', 'false') === 'true') {
            return true;
        }

        $signature = (string)$request->header('X-Twilio-Signature', '');
        $authToken = PrivateEnvironment::get('TWILIO_AUTH_TOKEN', '');
        if ($signature === '' || $authToken === '') {
            return false;
        }

        $parameters = $request->post();
        ksort($parameters, SORT_STRING);

        foreach ($this->canonicalUrls($request) as $url) {
            $payload = $url;

            foreach ($parameters as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $payload .= $key.(string)$item;
                    }
                } else {
                    $payload .= $key.(string)$value;
                }
            }

            $expected = base64_encode(hash_hmac('sha1', $payload, $authToken, true));
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return $this->isKnownTwilioMessage($request, $authToken);
    }

    /**
     * Twilio signs the exact public URL. Behind CloudPanel/Nginx/Laravel proxies,
     * the framework URL can differ from the public HTTPS URL, so we accept the
     * configured public base URL first and keep the request URL as a fallback.
     */
    private function canonicalUrls(Request $request): array
    {
        $path = $request->path();
        $urls = [];
        $baseUrl = rtrim(
            PrivateEnvironment::get('RESTOLINE_TWILIO_WEBHOOK_BASE_URL', '') ?? '',
            '/',
        );

        if ($baseUrl !== '') {
            $urls[] = $baseUrl.'/'.$path;
        }

        $forwardedProto = (string)$request->headers->get('X-Forwarded-Proto', '');
        $forwardedHost = (string)$request->headers->get('X-Forwarded-Host', '');
        if ($forwardedProto !== '' && $forwardedHost !== '') {
            $urls[] = $forwardedProto.'://'.$forwardedHost.'/'.$path;
        }

        $urls[] = $request->url();

        return array_values(array_unique($urls));
    }

    private function isKnownTwilioMessage(Request $request, string $authToken): bool
    {
        $messageSid = (string)($request->input('MessageSid') ?: $request->input('SmsSid', ''));
        if (!preg_match('/^SM[a-f0-9]{32}$/i', $messageSid)) {
            return false;
        }

        $accountSid = PrivateEnvironment::get('TWILIO_ACCOUNT_SID', '');
        if (!is_string($accountSid) || !preg_match('/^AC[a-f0-9]{32}$/i', $accountSid)) {
            return false;
        }

        $message = $this->fetchTwilioMessage($accountSid, $authToken, $messageSid);
        if (!is_array($message)) {
            return false;
        }

        if (($message['account_sid'] ?? '') !== $accountSid
            || ($message['sid'] ?? '') !== $messageSid
            || ($message['direction'] ?? '') !== 'inbound') {
            return false;
        }

        if (!$this->isRecentMessage((string)($message['date_created'] ?? ''))) {
            return false;
        }

        $fromMatches = (string)($message['from'] ?? '') === (string)$request->input('From', '');
        $toMatches = (string)($message['to'] ?? '') === (string)$request->input('To', '');
        $bodyMatches = trim((string)($message['body'] ?? '')) === trim((string)$request->input('Body', ''));

        if ($fromMatches && $toMatches && $bodyMatches) {
            Log::warning('Twilio signature fallback accepted for SMS webhook.', [
                'message_sid' => $messageSid,
                'from' => $request->input('From', ''),
                'to' => $request->input('To', ''),
            ]);
            return true;
        }

        return false;
    }

    private function fetchTwilioMessage(string $accountSid, string $authToken, string $messageSid): ?array
    {
        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages/%s.json',
            rawurlencode($accountSid),
            rawurlencode($messageSid),
        );

        $context = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic '.base64_encode($accountSid.':'.$authToken),
                'ignore_errors' => true,
                'method' => 'GET',
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response) || $response === '') {
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function isRecentMessage(string $dateCreated): bool
    {
        if ($dateCreated === '') {
            return false;
        }

        try {
            $createdAt = new \DateTimeImmutable($dateCreated);
        } catch (\Throwable) {
            return false;
        }

        return $createdAt >= (new \DateTimeImmutable('-2 hours'));
    }
}
