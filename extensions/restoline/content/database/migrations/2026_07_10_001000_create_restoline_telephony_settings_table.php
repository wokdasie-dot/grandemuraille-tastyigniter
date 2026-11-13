<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Restoline\Content\Models\TelephonySetting;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_telephony_settings')) {
            return;
        }

        Schema::create('restoline_telephony_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->boolean('status')->default(true)->index();
            $table->text('welcome_message');
            $table->boolean('annual_leave_enabled')->default(false);
            $table->text('annual_leave_message')->nullable();
            $table->text('main_menu_message');
            $table->text('phone_reservation_message');
            $table->text('invalid_menu_message');
            $table->text('tariffs_message');
            $table->text('opening_hours_message');
            $table->text('sms_number_notice');
            $table->text('rate_limited_message');
            $table->text('session_limited_message');
            $table->unsignedSmallInteger('call_limit_per_hour')->default(4);
            $table->unsignedSmallInteger('call_limit_per_day')->default(12);
            $table->unsignedSmallInteger('max_call_seconds')->default(480);
            $table->unsignedSmallInteger('max_call_steps')->default(18);
            $table->timestamps();
        });

        DB::table('restoline_telephony_settings')->insert(array_merge(TelephonySetting::defaults(), [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_telephony_settings');
    }
};
