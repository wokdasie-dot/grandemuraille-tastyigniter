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
        if (Schema::hasTable('restoline_restaurant_notification_settings')) {
            return;
        }

        Schema::create('restoline_restaurant_notification_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->string('name')->default('Notifications restaurant');
            $table->string('channel', 20)->default('whatsapp')->index();
            $table->string('provider', 40)->default('twilio_whatsapp')->index();
            $table->string('recipient')->nullable();
            $table->string('sender')->nullable();
            $table->string('api_base_url')->nullable();
            $table->string('account_id')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->boolean('notify_new_reservation')->default(true);
            $table->boolean('notify_modified_reservation')->default(true);
            $table->boolean('notify_cancelled_reservation')->default(true);
            $table->text('message_template')->nullable();
            $table->boolean('status')->default(false)->index();
            $table->timestamps();
        });

        DB::table('restoline_restaurant_notification_settings')->insert([
            'name' => 'Notifications restaurant',
            'channel' => 'whatsapp',
            'provider' => 'twilio_whatsapp',
            'notify_new_reservation' => true,
            'notify_modified_reservation' => true,
            'notify_cancelled_reservation' => true,
            'message_template' => "Nouvelle réservation La Grande Muraille de Chine\nClient: {client}\nTéléphone: {telephone}\nDate: {date} à {heure}\nPersonnes: {personnes}\nDashboard: {dashboard}",
            'status' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_restaurant_notification_settings');
    }
};
