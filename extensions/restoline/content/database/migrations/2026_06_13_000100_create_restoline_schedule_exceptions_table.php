<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_schedule_exceptions')) {
            return;
        }

        Schema::create('restoline_schedule_exceptions', function(Blueprint $table): void {
            $table->bigIncrements('exception_id');
            $table->unsignedBigInteger('location_id')->index();
            $table->string('name');
            $table->string('kind', 20)->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->time('opening_start')->nullable();
            $table->time('opening_end')->nullable();
            $table->time('evening_start')->nullable();
            $table->time('evening_end')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->foreign('location_id')
                ->references('location_id')
                ->on('locations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_schedule_exceptions');
    }
};
