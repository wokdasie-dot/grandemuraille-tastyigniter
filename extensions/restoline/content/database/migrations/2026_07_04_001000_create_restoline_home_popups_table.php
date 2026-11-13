<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_home_popups')) {
            return;
        }

        Schema::create('restoline_home_popups', function(Blueprint $table): void {
            $table->bigIncrements('popup_id');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('status')->default(false)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->time('daily_start_time')->nullable();
            $table->time('daily_end_time')->nullable();
            $table->unsignedSmallInteger('dismiss_hours')->default(12);
            $table->unsignedInteger('priority')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_home_popups');
    }
};
