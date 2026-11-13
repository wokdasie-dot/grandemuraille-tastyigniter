<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_reservation_feedbacks')) {
            return;
        }

        Schema::create('restoline_reservation_feedbacks', function(Blueprint $table): void {
            $table->bigIncrements('feedback_id');
            $table->unsignedInteger('reservation_id')->unique();
            $table->string('token', 80)->unique();
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('telephone')->nullable();
            $table->date('reserve_date')->nullable()->index();
            $table->time('reserve_time')->nullable();
            $table->unsignedTinyInteger('guest_num')->nullable();
            $table->string('reservation_channel', 20)->default('unknown')->index();
            $table->string('visit_answer', 20)->nullable()->index();
            $table->string('reservation_answer', 20)->nullable()->index();
            $table->string('problem_type', 80)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable()->index();
            $table->timestamp('google_clicked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_reservation_feedbacks');
    }
};
