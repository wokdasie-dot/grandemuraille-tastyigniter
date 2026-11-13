<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_service_devices', function(Blueprint $table): void {
            $table->bigIncrements('device_id');
            $table->string('device_key', 80)->unique();
            $table->string('name', 120);
            $table->char('token_hash', 64);
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_service_devices');
    }
};
