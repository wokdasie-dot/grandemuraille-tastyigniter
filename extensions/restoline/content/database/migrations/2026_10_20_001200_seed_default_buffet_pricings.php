<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Herstal buffet prices are not seeded on this instance.
        // GrandeMurailleSeeder writes the Spa prices after installation.
        return;

        $now = now();

        DB::table('restoline_buffet_pricings')->insert([
            [
                'location_id' => 1,
                'label' => 'Du lundi au samedi midi',
                'category' => 'weekday_lunch',
                'adult_price' => 24.00,
                'child_4_8_price' => 10.00,
                'child_9_13_price' => 16.00,
                'sort_order' => 10,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'location_id' => 1,
                'label' => 'Du lundi au jeudi soir',
                'category' => 'weekday_dinner',
                'adult_price' => 33.00,
                'child_4_8_price' => 15.00,
                'child_9_13_price' => 23.00,
                'sort_order' => 20,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'location_id' => 1,
                'label' => 'Vendredi et samedi soir',
                'category' => 'weekend_dinner',
                'adult_price' => 37.00,
                'child_4_8_price' => 16.00,
                'child_9_13_price' => 25.00,
                'sort_order' => 30,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'location_id' => 1,
                'label' => 'Dimanche et jours fériés (toute la journée)',
                'category' => 'sunday_holiday',
                'adult_price' => 35.00,
                'child_4_8_price' => 15.00,
                'child_9_13_price' => 24.00,
                'sort_order' => 40,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('restoline_buffet_pricings')->delete();
    }
};
