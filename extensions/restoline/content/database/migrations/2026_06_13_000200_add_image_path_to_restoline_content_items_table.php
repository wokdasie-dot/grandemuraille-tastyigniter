<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_content_items') || Schema::hasColumn('restoline_content_items', 'image_path')) {
            return;
        }

        Schema::table('restoline_content_items', function(Blueprint $table): void {
            $table->string('image_path')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_content_items') || !Schema::hasColumn('restoline_content_items', 'image_path')) {
            return;
        }

        Schema::table('restoline_content_items', function(Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
