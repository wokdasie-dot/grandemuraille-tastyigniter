<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_content_items')) {
            return;
        }

        Schema::create('restoline_content_items', function(Blueprint $table): void {
            $table->bigIncrements('content_id');
            $table->string('title');
            $table->string('type', 20)->index();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_content_items');
    }
};
