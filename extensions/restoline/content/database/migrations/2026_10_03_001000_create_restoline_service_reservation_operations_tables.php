<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_service_reservation_operations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reservation_id')->unique();
            $table->string('operational_status', 32)->default('planned');
            $table->unsignedBigInteger('updated_by_device_id')->nullable();
            $table->timestamps();
            $table->index('operational_status', 'rsrv_ops_status_idx');
        });

        Schema::create('restoline_service_reservation_actions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reservation_id')->index();
            $table->unsignedBigInteger('device_id')->nullable()->index();
            $table->string('idempotency_key', 80);
            $table->string('action', 32);
            $table->string('previous_status', 32)->nullable();
            $table->string('next_status', 32);
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['device_id', 'idempotency_key'], 'restoline_service_res_action_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_service_reservation_actions');
        Schema::dropIfExists('restoline_service_reservation_operations');
    }
};
