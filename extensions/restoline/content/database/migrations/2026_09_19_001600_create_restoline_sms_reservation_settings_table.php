<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { if (!Schema::hasTable('restoline_sms_reservation_settings')) Schema::create('restoline_sms_reservation_settings', function (Blueprint $table): void { $table->bigIncrements('setting_id'); $table->boolean('enabled')->default(true); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('restoline_sms_reservation_settings'); }
};
