<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Herstal news items are not seeded here.
        return;

        if (!Schema::hasTable('restoline_content_items')) {
            return;
        }

        $hasNews = DB::table('restoline_content_items')
            ->where('type', 'news')
            ->exists();

        if ($hasNews) {
            return;
        }

        $now = now();

        DB::table('restoline_content_items')->insert([
            [
                'title' => 'Nouvelles saveurs japonaises',
                'type' => 'news',
                'summary' => 'Sushis, makis et poké bowls rejoignent la sélection du buffet.',
                'image_path' => null,
                'content' => '<p>Sushis, makis et poké bowls rejoignent la sélection du buffet.</p><p><img src="/vendor/restoline-wokdasie/images/buffet-sushi-lumineux.jpg" alt="Sélection lumineuse de sushis"></p>',
                'published_at' => $now,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Ramen, wok et teppanyaki',
                'type' => 'news',
                'summary' => 'Composez votre assiette et confiez-la à nos cuisiniers.',
                'image_path' => null,
                'content' => '<p>Composez votre assiette et confiez-la à nos cuisiniers.</p><p><img src="/vendor/restoline-wokdasie/images/buffet-chaud.jpg" alt="Buffet chaud asiatique"></p>',
                'published_at' => $now,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_content_items')) {
            return;
        }

        DB::table('restoline_content_items')
            ->where('type', 'news')
            ->whereIn('title', [
                'Nouvelles saveurs japonaises',
                'Ramen, wok et teppanyaki',
            ])
            ->delete();
    }
};
