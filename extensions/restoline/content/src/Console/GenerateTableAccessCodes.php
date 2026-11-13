<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Services\TableAccessCodeService;

class GenerateTableAccessCodes extends Command
{
    protected $signature = 'restoline:table-codes {--date= : Date YYYY-MM-DD} {--regenerate : Remplace les codes existants}';

    protected $description = 'Génère les codes journaliers de commande QR par table.';

    public function handle(TableAccessCodeService $service): int
    {
        $date = $this->option('date') ? now()->parse($this->option('date')) : now();
        $count = $service->generateForDate($date, (bool)$this->option('regenerate'));

        $this->info(sprintf('%d codes générés ou mis à jour pour le %s.', $count, $date->format('d/m/Y')));

        return self::SUCCESS;
    }
}
