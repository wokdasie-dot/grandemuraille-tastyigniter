<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use App\Services\Restoline\TwilioSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Restoline\Content\Models\SmsGatewayMessage;

class CheckSmsGatewayFallbacks extends Command
{
    protected $signature = 'restoline:check-sms-gateway-fallbacks {--id= : ID interne du SMS Gateway a verifier} {--limit=30}';

    protected $description = 'Bascule vers Twilio si un SMS GSM reste sans confirmation apres 1 minute.';

    public function handle(TwilioSmsService $sms): int
    {
        if (!Schema::hasTable('restoline_sms_gateway_messages')) {
            $this->warn('Table restoline_sms_gateway_messages absente.');

            return self::SUCCESS;
        }

        $query = SmsGatewayMessage::query()
            ->whereNull('fallback_sent_at')
            ->where('fallback_due_at', '<=', now())
            ->whereNotIn('status', ['Delivered', 'delivered', 'Sent', 'sent', 'Processed', 'processed']);

        if ($this->option('id')) {
            $query->whereKey((int)$this->option('id'));
        } else {
            $query->orderBy('fallback_due_at')->limit(max(1, (int)$this->option('limit')));
        }

        $messages = $query->get();

        $fallbacks = 0;
        foreach ($messages as $message) {
            try {
                $reason = sprintf(
                    'Aucun webhook SMS Gateway confirme apres 1 minute. Dernier statut: %s',
                    $message->status ?: 'inconnu',
                );

                $sms->sendFallbackSms((string)$message->to_number, (string)$message->message_body, $reason);

                $message->status = 'fallback_twilio_sent';
                $message->fallback_sent_at = now();
                $message->fallback_reason = $reason;
                $message->save();
                $fallbacks++;
            } catch (\Throwable $exception) {
                Log::error('Fallback Twilio SMS Gateway impossible.', [
                    'sms_gateway_message_id' => $message->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Fallbacks SMS verifies: %d traite(s).', $fallbacks));

        return self::SUCCESS;
    }
}
