<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Herstal gallery photos are not seeded here.
        return;

        if (!Schema::hasTable('restoline_gallery_items')) {
            return;
        }

        if (DB::table('restoline_gallery_items')->exists()) {
            return;
        }

        $now = now();
        $base = 'vendor/restoline-wokdasie/images/galerie/';

        $items = [
            ['title' => 'Le buffet', 'image_path' => $base.'galerie-buffet-wok.jpg', 'caption' => 'Le buffet chaud et ses spécialités préparées devant vous.'],
            ['title' => 'La salle', 'image_path' => $base.'galerie-salle-lanternes.jpg', 'caption' => 'Une salle chaleureuse avec ses lanternes.'],
            ['title' => 'Le bar', 'image_path' => $base.'galerie-tireuses-cafe.jpg', 'caption' => 'Bières pression et café à volonté.'],
            ['title' => 'Les boissons', 'image_path' => $base.'galerie-frigos-boissons.jpg', 'caption' => 'Softs et boissons fraîches en libre-service.'],
            ['title' => 'Le buffet central', 'image_path' => $base.'galerie-buffet-central.jpg', 'caption' => 'Le cœur du restaurant, entre buffet et salle.'],
            ['title' => 'La salle rouge', 'image_path' => $base.'galerie-salle-rouge.jpg', 'caption' => 'Un espace plus intime pour les familles et les groupes.'],
            ['title' => 'Le buffet en salle', 'image_path' => $base.'galerie-buffet-salle.jpg', 'caption' => 'Le buffet servi directement en salle.'],
        ];

        foreach ($items as $index => $item) {
            DB::table('restoline_gallery_items')->insert([
                'title' => $item['title'],
                'image_path' => $item['image_path'],
                'caption' => $item['caption'],
                'sort_order' => ($index + 1) * 10,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_gallery_items')) {
            return;
        }

        DB::table('restoline_gallery_items')->delete();
    }
};
