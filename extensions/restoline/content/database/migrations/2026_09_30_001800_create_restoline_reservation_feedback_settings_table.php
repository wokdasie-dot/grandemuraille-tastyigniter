<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_reservation_feedback_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('sample_percent')->default(40);
            $table->unsignedTinyInteger('days_after_visit')->default(1);
            $table->unsignedSmallInteger('cooldown_days')->default(60);
            $table->boolean('google_review_enabled')->default(true);
            $table->string('email_subject', 190)->default("Votre avis nous aide - La Grande Muraille de Chine");
            $table->string('email_heading', 190)->default('Comment s’est passée votre visite ?');
            $table->text('email_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_reservation_feedback_settings');
    }
};
