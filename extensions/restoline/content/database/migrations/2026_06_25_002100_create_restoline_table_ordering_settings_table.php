<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_table_ordering_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->boolean('status')->default(true)->index();
            $table->time('start_time')->default('18:00:00');
            $table->time('end_time')->default('22:00:00');
            $table->string('closed_message')->nullable();
            $table->timestamps();
        });

        DB::table('restoline_table_ordering_settings')->insert([
            'status' => true,
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'closed_message' => 'La commande à table est disponible tous les soirs de 18h00 à 22h00.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_table_ordering_settings');
    }
};