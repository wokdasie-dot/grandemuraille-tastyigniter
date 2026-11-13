<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_sms_conversations')) {
            Schema::create('restoline_sms_conversations', function(Blueprint $table): void {
                $table->bigIncrements('conversation_id');
                $table->string('phone', 32)->index();
                $table->string('status', 40)->default('open')->index();
                $table->unsignedInteger('reservation_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->json('draft_payload')->nullable();
                $table->text('last_message')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamp('last_inbound_at')->nullable();
                $table->timestamp('last_outbound_at')->nullable();
                $table->timestamps();

                $table->index(['phone', 'status'], 'restoline_sms_conversation_phone_status');
            });
        }

        if (!Schema::hasTable('restoline_sms_conversation_messages')) {
            Schema::create('restoline_sms_conversation_messages', function(Blueprint $table): void {
                $table->bigIncrements('message_id');
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->string('direction', 12)->index();
                $table->string('phone', 32)->index();
                $table->string('provider_message_id', 120)->nullable()->index();
                $table->text('body');
                $table->string('intent', 40)->nullable()->index();
                $table->string('status', 40)->default('received')->index();
                $table->unsignedInteger('reservation_id')->nullable()->index();
                $table->json('ai_payload')->nullable();
                $table->json('raw_payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['phone', 'created_at'], 'restoline_sms_message_phone_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_sms_conversation_messages');
        Schema::dropIfExists('restoline_sms_conversations');
    }
};
