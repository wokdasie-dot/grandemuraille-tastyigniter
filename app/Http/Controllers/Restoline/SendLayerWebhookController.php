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

class SendLayerWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        ReservationOtpService $reservations,
    ): JsonResponse {
        $expected = (string)PrivateEnvironment::get('SENDLAYER_WEBHOOK_TOKEN', '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $payload = $request->json()->all();
        $eventData = data_get($payload, 'event.body.EventData', data_get($payload, 'EventData', []));
        $event = strtolower((string)data_get($eventData, 'Event', ''));
        $messageId = trim((string)data_get($eventData, 'MessageID', ''));
        $email = trim((string)(
            data_get($eventData, 'To')
            ?: data_get($eventData, 'BouncedEmailAddress.EmailAddress')
            ?: ''
        ));

        $notificationId = $reservations->recordSendLayerEvent($event, $email, $messageId, $payload);
        $fallbackSent = false;

        if (in_array($event, ['bounce', 'bounced', 'failed'], true)) {
            try {
                $fallbackSent = $reservations->sendSmsFallbackForBouncedEmail($email, $messageId, $payload);
            } catch (\Throwable $exception) {
                Log::warning('Fallback SMS après bounce SenderLayer échoué.', [
                    'email_hash' => $email !== '' ? hash('sha256', strtolower($email)) : null,
                    'message_id' => $messageId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'event' => $event,
            'notification_id' => $notificationId,
            'sms_fallback_sent' => $fallbackSent,
        ]);
    }
}
