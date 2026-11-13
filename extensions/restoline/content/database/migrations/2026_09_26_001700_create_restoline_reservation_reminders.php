<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('restoline_reservation_reminder_settings')) {
            Schema::create('restoline_reservation_reminder_settings', function(Blueprint $table): void {
                $table->bigIncrements('setting_id');
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('min_guests')->default(6);
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(true);
                $table->string('send_time', 5)->default('08:00');
                $table->string('email_subject')->default("La Grande Muraille de Chine - Rappel de votre réservation");
                $table->text('email_message');
                $table->text('sms_message');
                $table->timestamps();
            });

            DB::table('restoline_reservation_reminder_settings')->insert([
                'enabled' => true,
                'min_guests' => 6,
                'email_enabled' => true,
                'sms_enabled' => true,
                'send_time' => '08:00',
                'email_subject' => "La Grande Muraille de Chine - Rappel de votre réservation",
                'email_message' => 'Petit rappel pour votre réservation de demain. Vous pouvez vérifier les informations, modifier le nombre de personnes ou annuler via le bouton ci-dessous.',
                'sms_message' => "La Grande Muraille de Chine: rappel demain a {time} pour {guests} pers. Modifier ou annuler: {url}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('restoline_reservation_reminders')) {
            Schema::create('restoline_reservation_reminders', function(Blueprint $table): void {
                $table->bigIncrements('reminder_id');
                $table->unsignedBigInteger('reservation_id')->index();
                $table->date('reserve_date')->index();
                $table->string('token', 20)->unique();
                $table->string('email')->nullable();
                $table->string('telephone', 40)->nullable();
                $table->string('email_status', 20)->default('pending')->index();
                $table->string('sms_status', 20)->default('pending')->index();
                $table->text('email_error')->nullable();
                $table->text('sms_error')->nullable();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('opened_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['reservation_id', 'reserve_date'], 'restoline_reservation_reminder_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_reservation_reminders');
        Schema::dropIfExists('restoline_reservation_reminder_settings');
    }
};
