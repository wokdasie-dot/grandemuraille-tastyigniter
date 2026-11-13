<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restoline_table_orders', function(Blueprint $table): void {
            $table->bigIncrements('order_id');
            $table->string('reference', 32)->unique();
            $table->unsignedBigInteger('dining_table_id')->nullable()->index();
            $table->unsignedBigInteger('access_code_id')->nullable()->index();
            $table->string('table_name', 32)->index();
            $table->date('valid_date')->nullable()->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('print_status', 32)->default('pending')->index();
            $table->text('note')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'print_status', 'submitted_at'], 'restoline_table_orders_queue_index');
        });

        Schema::create('restoline_table_order_items', function(Blueprint $table): void {
            $table->bigIncrements('item_id');
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('menu_id')->index();
            $table->string('menu_name');
            $table->text('menu_description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_id', 'restoline_table_order_items_order_fk')
                ->references('order_id')
                ->on('restoline_table_orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restoline_table_order_items');
        Schema::dropIfExists('restoline_table_orders');
    }
};
