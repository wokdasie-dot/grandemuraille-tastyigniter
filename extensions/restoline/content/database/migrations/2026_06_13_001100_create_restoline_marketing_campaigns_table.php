<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restoline_marketing_campaigns')) {
            return;
        }

        Schema::create('restoline_marketing_campaigns', function(Blueprint $table): void {
            $table->bigIncrements('campaign_id');
            $table->string('title');
            $table->string('type', 20)->default('manual')->index();
            $table->string('channel', 20)->default('both');
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->integer('birthday_days_before')->default(3);
            $table->integer('valid_days')->default(10);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        DB::table('restoline_marketing_campaigns')->insert([
            'title' => 'Offre anniversaire',
            'type' => 'birthday',
            'channel' => 'both',
            'email_subject' => "Votre offre anniversaire La Grande Muraille de Chine",
            'email_body' => "Bonjour {prenom},\n\nPour votre anniversaire, La Grande Muraille de Chine vous offre un apéritif au choix, cocktails compris.\n\nOffre valable du {date_debut} au {date_fin}, sur présentation de ce message et d'une pièce d'identité.\n\nÀ bientôt,\nLa Grande Muraille de Chine",
            'sms_body' => "La Grande Muraille de Chine : bon anniversaire {prenom}. Un apéritif au choix offert, cocktails compris. Valable du {date_debut} au {date_fin}. Désinscription : {desinscription}",
            'birthday_days_before' => 3,
            'valid_days' => 10,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_marketing_campaigns');
    }
};
