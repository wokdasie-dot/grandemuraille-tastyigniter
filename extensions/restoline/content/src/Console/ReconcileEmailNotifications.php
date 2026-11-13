<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Restoline\Content\Services\ReservationOtpService;

class ReconcileEmailNotifications extends Command
{
    protected $signature = 'restoline:reconcile-email-notifications {--hours=3 : Anciennete minimale des emails pending} {--dry-run : Affiche sans modifier}';

    protected $description = 'Marque les suivis email de reservation encore pending comme probablement envoyes apres delai.';

    public function handle(ReservationOtpService $reservations): int
    {
        if (!Schema::hasTable('restoline_reservation_email_notifications')) {
            $this->warn('Table restoline_reservation_email_notifications absente.');
            return self::SUCCESS;
        }

        $hours = max(1, (int)$this->option('hours'));

        if ($this->option('dry-run')) {
            $count = DB::table('restoline_reservation_email_notifications')
                ->where('message_type', 'client_status')
                ->where('email_status', 'pending')
                ->whereNull('sms_fallback_sent_at')
                ->where('created_at', '<=', now()->subHours($hours))
                ->count();

            $this->info(sprintf('%d suivi(s) email pending seraient marques sent_assumed apres %d heure(s).', $count, $hours));
            return self::SUCCESS;
        }

        $count = $reservations->markStalePendingEmailNotificationsAsAssumedSent($hours);
        $this->info(sprintf('%d suivi(s) email marques sent_assumed apres %d heure(s).', $count, $hours));

        return self::SUCCESS;
    }
}
