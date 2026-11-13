<?php

declare(strict_types=1);

namespace Database\Seeders;

use Igniter\Local\Models\Location;
use Igniter\Reservation\Models\DiningArea;
use Igniter\Reservation\Models\DiningTable;
use Igniter\System\Models\Language;
use Igniter\System\Models\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Restoline\Content\Models\BuffetPricing;

class GrandeMurailleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLanguages();
        $location = $this->seedLocation();
        $this->seedOpeningHours($location);
        $this->seedBookingSettings($location);
        $this->seedStatuses();
        $this->seedTables($location);
        $this->seedBuffetPrices((int)$location->getKey());
        $this->seedMenus((int)$location->getKey());
        $this->disableOutOfScopeDefaults();

        Settings::set([
            'site_name' => (string)config('restoline.restaurant.name'),
            'site_email' => (string)env('MAIL_FROM_ADDRESS', config('restoline.restaurant.notification_email')),
            'sender_name' => (string)config('restoline.restaurant.name'),
            'sender_email' => (string)env('MAIL_FROM_ADDRESS', config('restoline.restaurant.notification_email')),
            'default_language' => 'fr',
        ]);
        Settings::set('ti_setup', 'installed', 'prefs');
    }

    private function seedLanguages(): void
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        DB::table('languages')->update(['is_default' => false]);

        $french = DB::table('languages')->where('code', 'fr')->first();
        if ($french) {
            DB::table('languages')->where('code', 'fr')->update([
                'name' => 'Français',
                'idiom' => 'french',
                'status' => true,
                'is_default' => true,
                'can_delete' => false,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('languages')->insert([
                'code' => 'fr',
                'name' => 'Français',
                'idiom' => 'french',
                'status' => true,
                'is_default' => true,
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (class_exists(Language::class)) {
            Language::applySupportedLanguages();
            Language::clearInternalCache();
        }
    }

    private function seedLocation(): Location
    {
        $restaurant = config('restoline.restaurant');
        $location = Location::query()->first() ?? new Location();
        $countryId = null;
        if (Schema::hasTable('countries')) {
            $countryId = DB::table('countries')->where('iso_code_2', 'BE')->value('country_id')
                ?? DB::table('countries')->where('iso_code_3', 'BEL')->value('country_id');
        }

        $location->forceFill([
            'location_name' => $restaurant['name'],
            'location_email' => (string)env('RESTAURANT_NOTIFICATION_EMAIL', $restaurant['notification_email']),
            'description' => 'Une invitation à découvrir les saveurs de la cuisine chinoise, au cœur de Spa.',
            'location_address_1' => $restaurant['street'],
            'location_city' => $restaurant['city'],
            'location_postcode' => $restaurant['postcode'],
            'location_country_id' => $countryId,
            'location_telephone' => $restaurant['phone'],
            'location_status' => true,
            'permalink_slug' => $restaurant['location_slug'],
            'is_default' => true,
        ]);
        $location->save();

        DB::table('locations')->where('location_id', $location->getKey())->update([
            'permalink_slug' => $restaurant['location_slug'],
        ]);

        return $location->fresh();
    }

    private function seedOpeningHours(Location $location): void
    {
        $flexible = [];
        for ($day = 0; $day <= 6; $day++) {
            $flexible[$day] = [
                'day' => $day,
                'hours' => '12:00-14:30,18:00-22:30',
                'status' => $day === 1 ? 0 : 1,
            ];
        }

        $location->updateSchedule('opening', [
            'type' => 'flexible',
            'flexible' => $flexible,
        ]);
    }

    private function seedBookingSettings(Location $location): void
    {
        $reservation = config('restoline.reservation');
        $settings = $location->findSettings('booking');
        $settings->forceFill([
            'is_enabled' => 1,
            'time_interval' => (int)$reservation['interval_minutes'],
            'stay_time' => (int)$reservation['stay_minutes'],
            'min_guest_count' => (int)$reservation['min_guests'],
            'max_guest_count' => (int)$reservation['max_guests'],
            'min_advance_time' => 0,
            'max_advance_time' => (int)$reservation['max_advance_days'],
            'auto_allocate_table' => 1,
            'include_start_time' => 1,
            'cancellation_timeout' => 60,
        ]);
        $settings->save();
    }

    private function seedStatuses(): void
    {
        if (!Schema::hasTable('statuses')) {
            return;
        }

        $map = [
            'default_reservation_status' => 'Pending',
            'confirmed_reservation_status' => 'Confirmed',
            'canceled_reservation_status' => 'Canceled',
        ];

        $values = [];
        foreach ($map as $setting => $name) {
            $id = DB::table('statuses')
                ->where('status_for', 'reservation')
                ->where('status_name', $name)
                ->value('status_id');
            if ($id) {
                $values[$setting] = (int)$id;
            }
        }

        if ($values !== []) {
            Settings::set($values);
        }
    }

    private function seedTables(Location $location): void
    {
        $definitions = [
            ['name' => 'T2-1', 'min' => 1, 'max' => 2, 'shape' => 'rectangle'],
            ['name' => 'T2-2', 'min' => 1, 'max' => 2, 'shape' => 'rectangle'],
            ['name' => 'T4-1', 'min' => 3, 'max' => 4, 'shape' => 'rectangle'],
            ['name' => 'T4-2', 'min' => 3, 'max' => 4, 'shape' => 'rectangle'],
            ['name' => 'T6-1', 'min' => 5, 'max' => 6, 'shape' => 'rectangle'],
            ['name' => 'R6-1', 'min' => 5, 'max' => 6, 'shape' => 'round'],
            ['name' => 'R6-2', 'min' => 5, 'max' => 6, 'shape' => 'round'],
            ['name' => 'T10-1', 'min' => 7, 'max' => 10, 'shape' => 'rectangle'],
            ['name' => 'T12-1', 'min' => 9, 'max' => 12, 'shape' => 'rectangle'],
            ['name' => 'T20-1', 'min' => 13, 'max' => 20, 'shape' => 'rectangle'],
        ];

        $areaIds = DiningArea::query()->where('location_id', $location->getKey())->pluck('id');
        if ($areaIds->isNotEmpty()) {
            DiningTable::query()->whereIn('dining_area_id', $areaIds)->delete();
            DiningArea::query()->where('location_id', $location->getKey())->delete();
        }

        $area = DiningArea::query()->create([
            'location_id' => $location->getKey(),
            'name' => 'Salle',
            'description' => 'Salle principale, environ 72 couverts.',
        ]);

        foreach ($definitions as $index => $definition) {
            DiningTable::query()->create([
                'dining_area_id' => $area->getKey(),
                'name' => $definition['name'],
                'shape' => $definition['shape'],
                'min_capacity' => $definition['min'],
                'max_capacity' => $definition['max'],
                'extra_capacity' => 0,
                'is_combo' => false,
                'is_enabled' => true,
                'priority' => $index,
            ]);
        }

        if (Schema::hasTable('tables')) {
            $legacyIds = DB::table('tables')->pluck('table_id');
            if (Schema::hasTable('locationables') && $legacyIds->isNotEmpty()) {
                DB::table('locationables')
                    ->where('locationable_type', 'tables')
                    ->whereIn('locationable_id', $legacyIds)
                    ->delete();
            }
            DB::table('tables')->delete();
        }
    }

    private function seedBuffetPrices(int $locationId): void
    {
        if (!Schema::hasTable('restoline_buffet_pricings')) {
            return;
        }

        BuffetPricing::query()->delete();

        $rows = [
            [
                'label' => 'Buffet sans boissons (ven. et sam. soir, dimanche midi et soir)',
                'category' => 'weekend_dinner',
                'adult_price' => 31.80,
                'child_4_8_price' => 13.80,
                'child_9_13_price' => 21.80,
                'sort_order' => 10,
            ],
            [
                'label' => 'Buffet boissons comprises (ven. et sam. soir, dimanche midi et soir)',
                'category' => 'weekend_drinks',
                'adult_price' => 48.80,
                'child_4_8_price' => 17.80,
                'child_9_13_price' => 29.80,
                'sort_order' => 20,
            ],
        ];

        foreach ($rows as $row) {
            BuffetPricing::query()->create($row + [
                'location_id' => $locationId,
                'status' => true,
            ]);
        }
    }

    private function seedMenus(int $locationId): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('menus')) {
            return;
        }

        $path = base_path('data/menus.json');
        if (!is_file($path)) {
            return;
        }

        $menus = json_decode((string)file_get_contents($path), true);
        if (!is_array($menus)) {
            return;
        }

        $now = now();
        $category = DB::table('categories')->where('permalink_slug', 'menus')->first();
        if ($category) {
            $categoryId = (int)$category->category_id;
        } else {
            $categoryId = (int)DB::table('categories')->insertGetId([
                'name' => 'Menus',
                'description' => 'Menus de la carte, à consulter sur place. La réservation en ligne ne prend pas de commande.',
                'parent_id' => null,
                'priority' => 1,
                'status' => true,
                'permalink_slug' => 'menus',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($menus as $index => $menu) {
            $name = trim((string)($menu['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $price = (float)str_replace(',', '.', (string)($menu['price'] ?? '0'));
            $details = array_values(array_filter(
                array_map(static fn($line): string => trim((string)$line), (array)($menu['details'] ?? [])),
                static fn(string $line): bool => $line !== '',
            ));

            $existing = DB::table('menus')->where('menu_name', $name)->first();
            $payload = [
                'menu_name' => $name,
                'menu_description' => implode("\n\n", $details),
                'menu_price' => $price,
                'minimum_qty' => 1,
                'menu_status' => true,
                'menu_priority' => $index + 1,
                'updated_at' => $now,
            ];

            if ($existing) {
                $menuId = (int)$existing->menu_id;
                DB::table('menus')->where('menu_id', $menuId)->update($payload);
            } else {
                $menuId = (int)DB::table('menus')->insertGetId($payload + ['created_at' => $now]);
            }

            if (Schema::hasTable('menu_categories')) {
                $linked = DB::table('menu_categories')
                    ->where('menu_id', $menuId)
                    ->where('category_id', $categoryId)
                    ->exists();
                if (!$linked) {
                    DB::table('menu_categories')->insert([
                        'menu_id' => $menuId,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            if (Schema::hasTable('locationables')) {
                $attached = DB::table('locationables')
                    ->where('location_id', $locationId)
                    ->where('locationable_id', $menuId)
                    ->where('locationable_type', 'menus')
                    ->exists();
                if (!$attached) {
                    DB::table('locationables')->insert([
                        'location_id' => $locationId,
                        'locationable_id' => $menuId,
                        'locationable_type' => 'menus',
                        'options' => null,
                    ]);
                }
            }
        }
    }

    private function disableOutOfScopeDefaults(): void
    {
        if (Schema::hasTable('restoline_reservation_feedback_settings')) {
            DB::table('restoline_reservation_feedback_settings')->update([
                'enabled' => false,
                'email_subject' => 'Votre avis nous aide - La Grande Muraille de Chine',
            ]);
        }
    }
}
