<?php

declare(strict_types=1);

namespace App\Http\Controllers\Restoline;

use App\Http\Controllers\Controller;
use App\Services\Restoline\PrivateEnvironment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Restoline\Content\Services\ReservationOtpService;
use Symfony\Component\HttpFoundation\Response;

class ResendWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        ReservationOtpService $reservations,
    ): JsonResponse {
        $expected = (string)PrivateEnvironment::get('RESEND_WEBHOOK_TOKEN', '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $payload = $request->json()->all();
        $type = strtolower((string)data_get($payload, 'type', ''));
        $event = str_starts_with($type, 'email.') ? substr($type, 6) : $type;
        $data = (array)data_get($payload, 'data', []);
        $messageId = trim((string)(data_get($data, 'message_id') ?: data_get($data, 'email_id') ?: ''));
        $to = data_get($data, 'to', []);
        $email = $this->extractEmailAddress(is_array($to) ? ($to[0] ?? '') : $to);

        $notificationId = $reservations->recordEmailProviderEvent('resend', $event, $email, $messageId, $payload);
        $fallbackSent = false;

        if (in_array($event, ['bounced', 'failed'], true)) {
            try {
                $fallbackSent = $reservations->sendSmsFallbackForBouncedEmail($email, $messageId, $payload);
            } catch (\Throwable $exception) {
                Log::warning('Fallback SMS après événement Resend échoué.', [
                    'email_hash' => $email !== '' ? hash('sha256', strtolower($email)) : null,
                    'message_id' => $messageId,
                    'event' => $event,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'provider' => 'resend',
            'event' => $event,
            'notification_id' => $notificationId,
            'sms_fallback_sent' => $fallbackSent,
        ]);
    }

    private function extractEmailAddress(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['email'] ?? $value['address'] ?? reset($value) ?: '';
        }

        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/<([^>]+)>/', $value, $matches)) {
            $value = trim($matches[1]);
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)
            && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $matches)
        ) {
            $value = $matches[0];
        }

        return strtolower(trim($value));
    }
}
