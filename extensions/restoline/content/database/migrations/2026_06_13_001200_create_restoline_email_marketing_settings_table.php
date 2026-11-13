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
        if (Schema::hasTable('restoline_email_marketing_settings')) {
            return;
        }

        Schema::create('restoline_email_marketing_settings', function(Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->string('name')->default('Configuration principale');
            $table->string('provider', 30)->default('brevo')->index();
            $table->string('api_base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('list_id')->nullable();
            $table->boolean('status')->default(false)->index();
            $table->timestamps();
        });

        DB::table('restoline_email_marketing_settings')->insert([
            'name' => 'Configuration principale',
            'provider' => 'brevo',
            'status' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_email_marketing_settings');
    }
};
