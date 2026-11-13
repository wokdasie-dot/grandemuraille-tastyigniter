<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_telephony_events')) {
            return;
        }

        Schema::create('restoline_telephony_events', function(Blueprint $table): void {
            $table->bigIncrements('event_id');
            $table->string('channel', 20)->index();
            $table->string('event_type', 80)->index();
            $table->string('call_sid', 80)->nullable()->index();
            $table->string('message_sid', 80)->nullable()->index();
            $table->string('from_hash', 80)->nullable()->index();
            $table->string('to_hash', 80)->nullable();
            $table->string('menu_digit', 10)->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->string('status', 80)->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['channel', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_telephony_events');
    }
};
