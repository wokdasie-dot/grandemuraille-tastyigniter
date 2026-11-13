<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_table_orders')) {
            return;
        }

        Schema::table('restoline_table_orders', function(Blueprint $table): void {
            if (!Schema::hasColumn('restoline_table_orders', 'client_token')) {
                $table->string('client_token', 80)->nullable()->unique()->after('reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_table_orders') || !Schema::hasColumn('restoline_table_orders', 'client_token')) {
            return;
        }

        Schema::table('restoline_table_orders', function(Blueprint $table): void {
            $table->dropUnique(['client_token']);
            $table->dropColumn('client_token');
        });
    }
};
