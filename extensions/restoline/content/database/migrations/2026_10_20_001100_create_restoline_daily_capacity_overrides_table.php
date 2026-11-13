<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_daily_capacity_overrides', function (Blueprint $table): void {
            $table->bigIncrements('override_id');
            $table->unsignedBigInteger('location_id')->nullable()->default(1);
            $table->date('date');
            $table->boolean('lunch_closed')->default(false);
            $table->boolean('dinner_closed')->default(false);
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_daily_capacity_overrides');
    }
};
