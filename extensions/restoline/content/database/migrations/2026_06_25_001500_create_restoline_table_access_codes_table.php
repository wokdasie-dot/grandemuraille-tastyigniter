<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_table_access_codes', function(Blueprint $table): void {
            $table->bigIncrements('code_id');
            $table->unsignedBigInteger('dining_table_id')->index();
            $table->string('table_name', 32)->index();
            $table->date('valid_date')->index();
            $table->char('code', 4);
            $table->boolean('status')->default(true)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['dining_table_id', 'valid_date'], 'restoline_table_codes_table_date_unique');
            $table->index(['table_name', 'valid_date', 'status'], 'restoline_table_codes_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_table_access_codes');
    }
};
