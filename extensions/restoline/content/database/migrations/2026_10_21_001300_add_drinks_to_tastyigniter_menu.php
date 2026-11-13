<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Herstal drinks are not part of this reservation-only instance.
        return;

        $now = now();
        $root = DB::table('categories')->where('permalink_slug', 'boissons')->first();

        if (!$root) {
            $legacyRoot = DB::table('categories')->where('permalink_slug', 'drinks')->first();
            if ($legacyRoot) {
                DB::table('categories')->where('category_id', $legacyRoot->category_id)->update([
                    'name' => 'Boissons',
                    'description' => 'Carte des boissons La Grande Muraille de Chine',
                    'permalink_slug' => 'boissons',
                    'updated_at' => $now,
                ]);
                $rootId = (int)$legacyRoot->category_id;
            } else {
                $rootId = (int)DB::table('categories')->insertGetId([
                    'name' => 'Boissons',
                    'description' => 'Carte des boissons La Grande Muraille de Chine',
                    'priority' => 100,
                    'status' => true,
                    'permalink_slug' => 'boissons',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } else {
            $rootId = (int)$root->category_id;
        }

        $aperitifs = DB::table('categories')->where('permalink_slug', 'aperitifs')->first();
        if (!$aperitifs) {
            $aperitifsId = (int)DB::table('categories')->insertGetId([
                'name' => 'Apéritifs',
                'description' => 'Apéritifs',
                'parent_id' => $rootId,
                'priority' => 1,
                'status' => true,
                'permalink_slug' => 'aperitifs',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $aperitifsId = (int)$aperitifs->category_id;
        }

        $drinks = [
            ['Apéritif maison', 6.00],
            ['Apéritif litchis', 5.00],
            ['Vin blanc moelleux', 5.00],
            ['Apérol Spritz', 6.00],
            ['Martini rouge / blanc', 5.00],
            ['Porto rouge / blanc', 5.00],
            ['Picon vin blanc / bière', 5.00],
            ['Pineau des Charentes blanc', 5.00],
            ['Batida de coco', 5.00],
            ['Campari orange', 5.00],
            ['Whisky (JB ou Johnny Walker)', 5.00],
            ['Ricard', 5.00],
            ['Pisang orange', 5.00],
            ['Passoa orange', 5.00],
            ['Safari orange', 5.00],
            ['Bacardi Coca', 5.00],
            ['Kir', 4.00],
            ["Kir royal (crémant d'Alsace)", 6.00],
            ["Coupe de crémant d'Alsace", 5.00],
            ['Gin nature', 5.00],
        ];

        foreach ($drinks as $priority => [$name, $price]) {
            $menu = DB::table('menus')->where('menu_name', $name)->first();
            $menuId = $menu
                ? (int)$menu->menu_id
                : (int)DB::table('menus')->insertGetId([
                    'menu_name' => $name,
                    'menu_description' => null,
                    'menu_price' => $price,
                    'minimum_qty' => 1,
                    'menu_status' => true,
                    'menu_priority' => $priority + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('menu_categories')->insertOrIgnore([
                'menu_id' => $menuId,
                'category_id' => $aperitifsId,
            ]);

            DB::table('locationables')->insertOrIgnore([
                'location_id' => 1,
                'locationable_id' => $menuId,
                'locationable_type' => 'menus',
                'options' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Les articles peuvent avoir ete modifies dans le dashboard : aucun effacement automatique.
    }
};
