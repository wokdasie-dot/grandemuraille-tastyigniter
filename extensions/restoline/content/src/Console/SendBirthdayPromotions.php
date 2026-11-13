<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Services\MarketingMessageService;

class SendBirthdayPromotions extends Command
{
    protected $signature = 'restoline:send-birthday-promotions';

    protected $description = 'Envoie les offres anniversaire aux inscrits newsletter qui ont donné leur consentement.';

    public function handle(MarketingMessageService $messages): int
    {
        $result = $messages->sendBirthdayPromotions();

        $this->info(sprintf(
            'Offres anniversaire envoyées. Emails: %d, SMS: %d, ignorés: %d',
            $result['email'],
            $result['sms'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
