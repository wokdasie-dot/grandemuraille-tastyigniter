<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

$applicationPath = $argv[1] ?? '';
if ($applicationPath === '' || !is_file($applicationPath.'/bootstrap/app.php')) {
    fwrite(STDERR, "Usage: php bootstrap_reservation_operations.php /absolute/path/to/application\n");
    exit(1);
}

require $applicationPath.'/vendor/autoload.php';
$app = require $applicationPath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (!Schema::hasTable('restoline_service_reservation_operations')) {
    Schema::create('restoline_service_reservation_operations', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('reservation_id')->unique();
        $table->string('operational_status', 32)->default('planned');
        $table->unsignedBigInteger('updated_by_device_id')->nullable();
        $table->timestamps();
        $table->index('operational_status', 'rsro_status_idx');
    });
}

if (!Schema::hasTable('restoline_service_reservation_actions')) {
    Schema::create('restoline_service_reservation_actions', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('reservation_id');
        $table->unsignedBigInteger('device_id')->nullable();
        $table->string('idempotency_key', 80);
        $table->string('action', 32);
        $table->string('previous_status', 32)->nullable();
        $table->string('next_status', 32);
        $table->string('note', 500)->nullable();
        $table->timestamps();
        $table->index('reservation_id', 'rsra_reservation_idx');
        $table->index('device_id', 'rsra_device_idx');
        $table->unique(['device_id', 'idempotency_key'], 'restoline_service_res_action_idempotency_unique');
    });
}

if (!Schema::hasTable('restoline_service_takeaway_orders')) {
    Schema::create('restoline_service_takeaway_orders', function (Blueprint $table): void {
        $table->bigIncrements('takeaway_id');
        $table->string('reference', 32)->nullable()->unique();
        $table->string('status', 32)->default('new');
        $table->string('customer_name', 120);
        $table->string('telephone', 24);
        $table->dateTime('pickup_at');
        $table->text('items')->nullable();
        $table->text('note')->nullable();
        $table->string('payment_note', 120)->nullable();
        $table->timestamp('accepted_at')->nullable();
        $table->timestamp('ready_at')->nullable();
        $table->timestamp('handed_over_at')->nullable();
        $table->unsignedBigInteger('created_by_device_id')->nullable();
        $table->timestamps();
        $table->index('status', 'rsto_status_idx');
        $table->index('pickup_at', 'rsto_pickup_idx');
    });
}

if (!Schema::hasTable('restoline_service_takeaway_order_actions')) {
    Schema::create('restoline_service_takeaway_order_actions', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('takeaway_id');
        $table->unsignedBigInteger('device_id')->nullable();
        $table->string('idempotency_key', 80);
        $table->string('action', 32);
        $table->string('previous_status', 32)->nullable();
        $table->string('next_status', 32)->nullable();
        $table->string('note', 500)->nullable();
        $table->timestamps();
        $table->index('takeaway_id', 'rstoa_takeaway_idx');
        $table->index('device_id', 'rstoa_device_idx');
        $table->unique(['device_id', 'idempotency_key'], 'rstoa_idempotency_unique');
    });
}

Schema::table('restoline_service_takeaway_orders', function (Blueprint $table): void {
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'source_order_id')) {
        $table->unsignedBigInteger('source_order_id')->nullable()->unique()->after('reference');
    }
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'customer_email')) {
        $table->string('customer_email', 190)->nullable()->after('telephone');
    }
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'estimated_delay_minutes')) {
        $table->unsignedSmallInteger('estimated_delay_minutes')->nullable()->after('pickup_at');
    }
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'estimated_ready_at')) {
        $table->dateTime('estimated_ready_at')->nullable()->after('estimated_delay_minutes');
    }
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'customer_estimate_notified_at')) {
        $table->timestamp('customer_estimate_notified_at')->nullable()->after('handed_over_at');
    }
    if (!Schema::hasColumn('restoline_service_takeaway_orders', 'customer_ready_notified_at')) {
        $table->timestamp('customer_ready_notified_at')->nullable()->after('customer_estimate_notified_at');
    }
});

fwrite(STDOUT, "Restoline service tables are ready.\n");
