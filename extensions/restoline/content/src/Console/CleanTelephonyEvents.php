<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanTelephonyEvents extends Command
{
    protected $signature = 'restoline:clean-telephony-events {--days=90}';

    protected $description = 'Nettoie les anciens événements du répondeur et des SMS.';

    public function handle(): int
    {
        if (!Schema::hasTable('restoline_telephony_events')) {
            $this->warn('Table restoline_telephony_events absente.');

            return self::SUCCESS;
        }

        $days = max(30, (int)$this->option('days'));
        $deleted = DB::table('restoline_telephony_events')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info(sprintf(
            'Événements téléphonie nettoyés: %d supprimés, conservation %d jours.',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
