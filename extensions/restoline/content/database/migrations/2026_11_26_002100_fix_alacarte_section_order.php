<?php

declare(strict_types=1);

use App\Services\Restoline\AlacarteImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new AlacarteImporter())->correctBuggySectionPriorities();
    }

    public function down(): void
    {
    }
};
