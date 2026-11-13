<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_reservation_client_notification_settings')) {
            return;
        }

        Schema::create('restoline_reservation_client_notification_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->string('confirmation_mode', 40)->default('email_then_sms_fallback');
            $table->timestamps();
        });

        DB::table('restoline_reservation_client_notification_settings')->insert([
            'confirmation_mode' => 'email_then_sms_fallback',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_reservation_client_notification_settings');
    }
};
