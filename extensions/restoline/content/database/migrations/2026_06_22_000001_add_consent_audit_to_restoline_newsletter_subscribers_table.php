<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_newsletter_subscribers')) {
            return;
        }

        Schema::table('restoline_newsletter_subscribers', function(Blueprint $table): void {
            if (!Schema::hasColumn('restoline_newsletter_subscribers', 'consent_url')) {
                $table->text('consent_url')->nullable()->after('consent_source');
            }
            if (!Schema::hasColumn('restoline_newsletter_subscribers', 'consent_version')) {
                $table->string('consent_version')->nullable()->after('consent_url');
            }
            if (!Schema::hasColumn('restoline_newsletter_subscribers', 'consent_text')) {
                $table->text('consent_text')->nullable()->after('consent_version');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_newsletter_subscribers')) {
            return;
        }

        Schema::table('restoline_newsletter_subscribers', function(Blueprint $table): void {
            if (Schema::hasColumn('restoline_newsletter_subscribers', 'consent_text')) {
                $table->dropColumn('consent_text');
            }
            if (Schema::hasColumn('restoline_newsletter_subscribers', 'consent_version')) {
                $table->dropColumn('consent_version');
            }
            if (Schema::hasColumn('restoline_newsletter_subscribers', 'consent_url')) {
                $table->dropColumn('consent_url');
            }
        });
    }
};
