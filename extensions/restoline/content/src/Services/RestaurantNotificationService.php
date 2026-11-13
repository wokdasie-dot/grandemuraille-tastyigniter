<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use Igniter\Reservation\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Restoline\Content\Models\RestaurantNotificationSetting;

class RestaurantNotificationService
{
    public function sendReservationEvent(Reservation $reservation, string $event): void
    {
        if (!in_array($event, ['new_reservation', 'modified_reservation', 'cancelled_reservation'], true)) {
            return;
        }

        RestaurantNotificationSetting::query()
            ->where('status', true)
            ->get()
            ->each(function(RestaurantNotificationSetting $setting) use ($reservation, $event): void {
                if (!$this->shouldSend($setting, $event)) {
                    return;
                }

                $cacheKey = $this->cacheKey($setting, $reservation, $event);
                if (Cache::has($cacheKey)) {
                    return;
                }

                try {
                    $this->sendToProvider($setting, $this->payload($setting, $reservation, $event));
                    Cache::put($cacheKey, true, now()->addDays(30));
                } catch (\Throwable $exception) {
                    Log::warning('Echec notification restaurateur.', [
                        'setting_id' => $setting->getKey(),
                        'provider' => $setting->provider,
                        'event' => $event,
                        'reservation_id' => $reservation->reservation_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }

    private function shouldSend(RestaurantNotificationSetting $setting, string $event): bool
    {
        return match ($event) {
            'new_reservation' => (bool)$setting->notify_new_reservation,
            'modified_reservation' => (bool)$setting->notify_modified_reservation,
            'cancelled_reservation' => (bool)$setting->notify_cancelled_reservation,
            default => false,
        };
    }

    private function sendToProvider(RestaurantNotificationSetting $setting, array $payload): void
    {
        if ($setting->provider !== 'wecom_workflow' && $setting->provider !== 'custom') {
            return;
        }

        $webhookUrl = trim((string)$setting->api_base_url);
        if ($webhookUrl === '') {
            return;
        }

        $request = Http::timeout(8)
            ->acceptJson()
            ->asJson();

        if ($setting->access_token) {
            $request = $request->withToken($setting->access_token);
        }

        $body = $setting->provider === 'wecom_workflow'
            ? [
                'msgtype' => 'text',
                'text' => [
                    'content' => $this->wecomText($payload),
                ],
            ]
            : $payload;

        $response = $request->post($webhookUrl, $body);

        if (!$response->successful()) {
            throw new \RuntimeException('Webhook HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300));
        }

        $json = $response->json();
        if (is_array($json) && array_key_exists('errcode', $json) && (int)$json['errcode'] !== 0) {
            throw new \RuntimeException('Webhook errcode '.$json['errcode'].': '.($json['errmsg'] ?? 'Erreur inconnue'));
        }
    }

    private function payload(RestaurantNotificationSetting $setting, Reservation $reservation, string $event): array
    {
        $reservation->refresh();
        $date = $reservation->reserve_date?->format('Y-m-d');
        $dateFr = $reservation->reserve_date?->format('d/m/Y');
        $time = substr((string)$reservation->reserve_time, 0, 5);
        $clientName = trim((string)$reservation->first_name.' '.(string)$reservation->last_name);

        return [
            'source' => 'restoline_wokdasie',
            'event' => $event,
            'event_label' => $this->eventLabel($event),
            'sent_at' => now()->toIso8601String(),
            'restaurant' => [
                'name' => "La Grande Muraille de Chine",
                'phone' => '065 78 33 78',
                'address' => 'Rue de Mons 171, 7301 Hornu',
            ],
            'notification_setting' => [
                'id' => $setting->getKey(),
                'channel' => $setting->channel,
                'provider' => $setting->provider,
            ],
            'reservation' => [
                'id' => $reservation->reservation_id,
                'hash' => $reservation->hash,
                'status_id' => (int)$reservation->status_id,
                'client_name' => $clientName,
                'first_name' => (string)$reservation->first_name,
                'last_name' => (string)$reservation->last_name,
                'phone' => (string)$reservation->telephone,
                'email' => (string)$reservation->email,
                'guests' => (int)$reservation->guest_num,
                'date' => $date,
                'date_fr' => $dateFr,
                'time' => $time,
                'comment' => (string)$reservation->comment,
                'admin_url' => rtrim((string) config('app.url'), '/').'/admin/reservations',
                'manage_url' => rtrim((string) config('app.url'), '/').'/reservation/gerer',
            ],
        ];
    }

    private function cacheKey(RestaurantNotificationSetting $setting, Reservation $reservation, string $event): string
    {
        $version = $event === 'modified_reservation'
            ? sha1((string)$reservation->updated_at)
            : (string)$reservation->status_id;

        return sprintf(
            'restoline:restaurant-notification:%s:%s:%s:%s',
            $setting->getKey(),
            $reservation->reservation_id,
            $event,
            $version,
        );
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'new_reservation' => 'Nouvelle réservation',
            'modified_reservation' => 'Réservation modifiée',
            'cancelled_reservation' => 'Réservation annulée',
            default => $event,
        };
    }

    private function wecomText(array $payload): string
    {
        $reservation = $payload['reservation'] ?? [];
        $restaurant = $payload['restaurant'] ?? [];

        return trim(sprintf(
            "%s\n\nRestaurant: %s\nClient: %s\nTéléphone: %s\nEmail: %s\nCouverts: %s\nDate: %s\nHeure: %s\nNotes: %s\n\nDashboard: %s",
            (string)($payload['event_label'] ?? 'Notification réservation'),
            (string)($restaurant['name'] ?? "La Grande Muraille de Chine"),
            (string)($reservation['client_name'] ?? ''),
            (string)($reservation['phone'] ?? ''),
            (string)($reservation['email'] ?? ''),
            (string)($reservation['guests'] ?? ''),
            (string)($reservation['date_fr'] ?? $reservation['date'] ?? ''),
            (string)($reservation['time'] ?? ''),
            (string)($reservation['comment'] ?? ''),
            (string)($reservation['admin_url'] ?? ''),
        ));
    }
}
