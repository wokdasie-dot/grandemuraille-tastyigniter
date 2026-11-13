<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_newsletter_subscribers')) {
            return;
        }

        Schema::create('restoline_newsletter_subscribers', function(Blueprint $table): void {
            $table->bigIncrements('subscriber_id');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->unsignedTinyInteger('birthday_day')->nullable();
            $table->unsignedTinyInteger('birthday_month')->nullable();
            $table->boolean('email_consent')->default(false)->index();
            $table->boolean('sms_consent')->default(false)->index();
            $table->timestamp('consented_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();
            $table->string('consent_source')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->unsignedSmallInteger('last_birthday_email_year')->nullable();
            $table->unsignedSmallInteger('last_birthday_sms_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_newsletter_subscribers');
    }
};
