<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Restoline\Content\Services\ReservationReminderService;

class SendReservationReminders extends Command
{
    protected $signature = 'restoline:send-reservation-reminders {--date=} {--reservation=}';

    protected $description = 'Envoie les rappels email et SMS pour les grandes tables du lendemain.';

    public function handle(ReservationReminderService $reminders): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', (string)$this->option('date'), config('app.timezone'))->startOfDay()
            : now()->addDay()->startOfDay();
        $reservationId = $this->option('reservation') ? (int)$this->option('reservation') : null;
        $result = $reminders->sendForDate($date, $reservationId);

        $this->info(sprintf(
            '%s: %d éligible(s), %d email(s), %d SMS, %d échec(s).',
            $date->format('d/m/Y'),
            $result['eligible'],
            $result['email_sent'],
            $result['sms_sent'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
