<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_restaurant_notification_settings')) {
            return;
        }

        DB::table('restoline_restaurant_notification_settings')
            ->where('name', 'Notifications restaurant')
            ->update([
                'channel' => 'wechat',
                'provider' => 'wecom_workflow',
                'message_template' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_restaurant_notification_settings')) {
            return;
        }

        DB::table('restoline_restaurant_notification_settings')
            ->where('name', 'Notifications restaurant')
            ->update([
                'channel' => 'whatsapp',
                'provider' => 'twilio_whatsapp',
                'updated_at' => now(),
            ]);
    }
};
