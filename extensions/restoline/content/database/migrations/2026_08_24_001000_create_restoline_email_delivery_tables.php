<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_reservation_email_notifications')) {
            Schema::create('restoline_reservation_email_notifications', function(Blueprint $table): void {
                $table->bigIncrements('notification_id');
                $table->unsignedBigInteger('reservation_id')->index();
                $table->unsignedInteger('status_id')->index();
                $table->string('message_type', 60)->default('client_status')->index();
                $table->string('email')->index();
                $table->string('telephone')->nullable();
                $table->string('provider_message_id', 120)->nullable();
                $table->string('email_status', 40)->default('pending');
                $table->timestamp('last_event_at')->nullable();
                $table->timestamp('sms_fallback_sent_at')->nullable();
                $table->json('last_payload')->nullable();
                $table->timestamps();

                $table->unique(['reservation_id', 'status_id', 'message_type'], 'restoline_res_email_unique');
                $table->index('provider_message_id', 'restoline_res_email_msg_idx');
                $table->index('email_status', 'restoline_res_email_status_idx');
                $table->index('sms_fallback_sent_at', 'restoline_res_email_sms_idx');
                $table->index(['email', 'created_at'], 'restoline_res_email_lookup');
            });
        }

        if (!Schema::hasTable('restoline_email_provider_events')) {
            Schema::create('restoline_email_provider_events', function(Blueprint $table): void {
                $table->bigIncrements('event_id');
                $table->string('provider', 40)->default('sendlayer')->index();
                $table->string('event_type', 60)->index();
                $table->string('email')->nullable()->index();
                $table->string('provider_message_id', 120)->nullable();
                $table->unsignedBigInteger('reservation_email_notification_id')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('provider_message_id', 'restoline_email_evt_msg_idx');
                $table->index('reservation_email_notification_id', 'restoline_email_evt_notif_idx');
                $table->index(['provider', 'created_at'], 'restoline_email_provider_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_email_provider_events');
        Schema::dropIfExists('restoline_reservation_email_notifications');
    }
};
