<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restoline_reservation_feedbacks')) {
            return;
        }

        Schema::table('restoline_reservation_feedbacks', function(Blueprint $table): void {
            if (!Schema::hasColumn('restoline_reservation_feedbacks', 'follow_up_status')) {
                $table->string('follow_up_status', 30)->default('new')->index()->after('message');
            }
            if (!Schema::hasColumn('restoline_reservation_feedbacks', 'follow_up_note')) {
                $table->text('follow_up_note')->nullable()->after('follow_up_status');
            }
            if (!Schema::hasColumn('restoline_reservation_feedbacks', 'followed_up_at')) {
                $table->timestamp('followed_up_at')->nullable()->index()->after('follow_up_note');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restoline_reservation_feedbacks')) {
            return;
        }

        Schema::table('restoline_reservation_feedbacks', function(Blueprint $table): void {
            foreach (['followed_up_at', 'follow_up_note', 'follow_up_status'] as $column) {
                if (Schema::hasColumn('restoline_reservation_feedbacks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
