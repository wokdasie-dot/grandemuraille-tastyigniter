<?php

declare(strict_types=1);

namespace Restoline\Content\Support;

use Igniter\Local\Facades\Location as LocationFacade;
use Igniter\Local\Models\Location;
use Igniter\Local\Models\LocationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Interrupteur global de la reservation en ligne.
 *
 * Le drapeau est stocke dans les reglages du lieu (`location_settings.item = booking`,
 * cle `is_enabled`), c'est-a-dire exactement le champ « Proposer la reservation » du
 * dashboard. Ce service evite de lire le modele (cache interne) et fournit une API
 * simple pour le bouton du dashboard et les garde-fous du site public.
 */
final class OnlineBooking
{
    public static function isEnabled(?int $locationId = null): bool
    {
        try {
            if (!Schema::hasTable('location_settings')) {
                return true;
            }

            $locationId ??= self::locationId();
            if (!$locationId) {
                return true;
            }

            $raw = DB::table('location_settings')
                ->where('location_id', $locationId)
                ->where('item', 'booking')
                ->value('data');

            $settings = is_string($raw) ? json_decode($raw, true) : $raw;
            $settings = is_array($settings) ? $settings : [];

            // Aucune valeur enregistree = comportement par defaut du formulaire : actif.
            return !array_key_exists('is_enabled', $settings) || (bool)$settings['is_enabled'];
        } catch (\Throwable) {
            return true;
        }
    }

    public static function setEnabled(bool $enabled, ?int $locationId = null): void
    {
        $location = self::location($locationId);
        if (!$location) {
            return;
        }

        $settings = LocationSettings::instance($location, 'booking');
        $settings->is_enabled = $enabled ? 1 : 0;
        $settings->save();

        LocationSettings::clearInternalCache();
    }

    public static function toggle(?int $locationId = null): bool
    {
        $newState = !self::isEnabled($locationId);
        self::setEnabled($newState, $locationId);

        return $newState;
    }

    public static function statusLabel(): string
    {
        return self::isEnabled()
            ? 'Réservation en ligne : active'
            : 'Réservation en ligne : désactivée';
    }

    private static function location(?int $locationId = null): ?Location
    {
        try {
            if ($locationId) {
                return Location::query()->find($locationId);
            }

            $current = LocationFacade::current();
            if ($current instanceof Location) {
                return $current;
            }
        } catch (\Throwable) {
            // contexte console ou pas de lieu courant
        }

        return Location::query()->where('location_status', 1)->first()
            ?? Location::query()->first();
    }

    private static function locationId(): ?int
    {
        return self::location()?->getKey();
    }
}
