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
        if (Schema::hasTable('restoline_group_benefit_settings')) {
            return;
        }

        Schema::create('restoline_group_benefit_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->boolean('status')->default(true)->index();
            $table->unsignedTinyInteger('min_guests')->default(6);
            $table->boolean('send_for_dinner')->default(true);
            $table->boolean('send_for_sunday_lunch')->default(true);
            $table->text('sms_message');
            $table->timestamps();
        });

        DB::table('restoline_group_benefit_settings')->insert([
            'status' => true,
            'min_guests' => 6,
            'send_for_dinner' => true,
            'send_for_sunday_lunch' => true,
            'sms_message' => "Info La Grande Muraille de Chine: si l’addition est reglee en especes, la maison offre un apero maison ou une coupe de mousseux + un cafe, thé ou pousse-cafe. Merci de faire passer le message à votre table",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_group_benefit_settings');
    }
};
