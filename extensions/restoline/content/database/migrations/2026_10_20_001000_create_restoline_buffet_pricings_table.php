<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_buffet_pricings', function (Blueprint $table): void {
            $table->bigIncrements('pricing_id');
            $table->unsignedBigInteger('location_id')->nullable()->default(1);
            $table->string('label', 255);
            $table->string('category', 64)->unique();
            $table->decimal('adult_price', 8, 2);
            $table->decimal('child_4_8_price', 8, 2);
            $table->decimal('child_9_13_price', 8, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('location_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_buffet_pricings');
    }
};
