<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_gift_cards', function(Blueprint $table): void {
            $table->bigIncrements('gift_card_id');
            $table->string('code', 32)->unique();
            $table->string('qr_token', 80)->unique();
            $table->decimal('initial_amount', 10, 2);
            $table->decimal('balance_amount', 10, 2);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('purchaser_name')->nullable();
            $table->text('note')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restoline_gift_card_transactions', function(Blueprint $table): void {
            $table->bigIncrements('gift_card_transaction_id');
            $table->unsignedBigInteger('gift_card_id')->index();
            $table->string('type', 24);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('staff_name')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_gift_card_transactions');
        Schema::dropIfExists('restoline_gift_cards');
    }
};
