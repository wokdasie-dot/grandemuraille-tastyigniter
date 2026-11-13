<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Services\ReservationFeedbackService;

class SendReservationFeedbackRequests extends Command
{
    protected $signature = 'restoline:send-reservation-feedback {--days= : Remplace temporairement le délai configuré} {--limit=30}';

    protected $description = 'Envoie un email de retour à une partie des clients après leur visite.';

    public function handle(ReservationFeedbackService $feedbacks): int
    {
        $days = $this->option('days');
        $result = $feedbacks->sendDueFeedbackEmails(
            $days === null || $days === '' ? null : max(1, (int)$days),
            max(1, (int)$this->option('limit')),
        );

        $this->info(sprintf(
            'Retours réservation: %d envoyés, %d ignorés, %d échecs.',
            $result['sent'],
            $result['skipped'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
