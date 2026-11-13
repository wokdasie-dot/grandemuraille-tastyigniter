<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_sms_gateway_messages')) {
            return;
        }

        Schema::create('restoline_sms_gateway_messages', function(Blueprint $table): void {
            $table->bigIncrements('sms_gateway_message_id');
            $table->string('provider_message_id', 120)->unique();
            $table->string('to_number', 32)->index();
            $table->text('message_body');
            $table->string('status', 40)->default('accepted')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('fallback_due_at')->nullable()->index();
            $table->timestamp('fallback_sent_at')->nullable()->index();
            $table->string('fallback_reason')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'fallback_due_at'], 'restoline_sms_gateway_due_idx');
            $table->index(['to_number', 'created_at'], 'restoline_sms_gateway_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_sms_gateway_messages');
    }
};
