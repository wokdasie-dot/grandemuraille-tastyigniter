<?php

declare(strict_types=1);

namespace Restoline\Content\Support;

use Igniter\Reservation\Models\Status;

/**
 * Resout les identifiants de statut de reservation configures dans le dashboard.
 *
 * Les statuts ne sont pas figes : chaque installation peut avoir ses propres
 * identifiants. Toute la logique applicative doit passer par cette classe.
 */
final class ReservationStatusIds
{
    public static function confirmed(): int
    {
        return self::resolve('confirmed_reservation_status', 'Confirmed');
    }

    public static function canceled(): int
    {
        return self::resolve('canceled_reservation_status', 'Canceled');
    }

    public static function pending(): int
    {
        return self::resolve('default_reservation_status', 'Pending');
    }

    private static function resolve(string $settingKey, string $statusName): int
    {
        $configured = (int)setting($settingKey);
        if ($configured > 0) {
            return $configured;
        }

        $status = Status::query()
            ->where('status_for', 'reservation')
            ->where('status_name', $statusName)
            ->first();

        return (int)($status?->getKey() ?? 0);
    }
}
