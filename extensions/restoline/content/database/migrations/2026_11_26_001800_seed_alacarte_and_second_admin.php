<?php

declare(strict_types=1);

use App\Services\Restoline\AlacarteImporter;
use App\Services\Restoline\SecondAdminProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus') && !Schema::hasColumn('menus', 'alacarte_number')) {
            Schema::table('menus', function (Blueprint $table): void {
                $table->string('alacarte_number', 16)->nullable();
                $table->string('alacarte_seed_key', 80)->nullable()->unique();
            });
        }

        (new AlacarteImporter())->import();
        (new SecondAdminProvisioner())->provision();
    }

    public function down(): void
    {
    }
};
