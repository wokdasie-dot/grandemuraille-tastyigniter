<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\TwilioSmsService;
use Igniter\Cart\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Restoline\Content\Models\TakeawayOrder;

final class TakeawayWorkflowService
{
    public function registerIncomingOrder(Order $order): TakeawayOrder
    {
        $order->loadMissing('menus');
        $takeaway = TakeawayOrder::query()->firstOrNew([
            'source_order_id' => $order->getKey(),
        ]);
        $isNew = !$takeaway->exists;

        $takeaway->customer_name = trim((string)$order->first_name.' '.(string)$order->last_name) ?: 'Client';
        $takeaway->telephone = (string)$order->telephone;
        $takeaway->customer_email = (string)$order->email ?: null;
        $takeaway->pickup_at = $order->order_datetime ?? now();
        $takeaway->items = $order->menus
            ->map(fn($menu) => (int)$menu->quantity.' x '.(string)$menu->name)
            ->implode("\n");
        $takeaway->note = (string)$order->comment ?: null;
        $takeaway->payment_note = (string)($order->payment_method?->name ?? 'Paiement au retrait');

        if ($isNew) {
            $takeaway->status = 'new';
            $takeaway->estimated_delay_minutes = 30;
            $takeaway->estimated_ready_at = now()->addMinutes(30);
        }

        $takeaway->save();
        if ($isNew) {
            $takeaway->reference = 'EMP-'.str_pad((string)$takeaway->getKey(), 5, '0', STR_PAD_LEFT);
            $takeaway->save();
        }

        return $takeaway;
    }

    public function updateEstimate(TakeawayOrder $takeaway, int $delayMinutes): TakeawayOrder
    {
        $takeaway->estimated_delay_minutes = $delayMinutes;
        $takeaway->estimated_ready_at = now()->addMinutes($delayMinutes);
        $takeaway->save();

        return $takeaway->fresh();
    }

    public function notifyAccepted(TakeawayOrder $takeaway): string
    {
        $readyAt = $this->readyAt($takeaway);
        $minutes = (int)($takeaway->estimated_delay_minutes ?? 30);

        return $this->sendSms(
            $takeaway,
            "La Grande Muraille de Chine: commande {$takeaway->reference} acceptee. Retrait estime vers {$readyAt->format('H:i')} (environ {$minutes} min). Paiement au restaurant.",
            'customer_estimate_notified_at',
        );
    }

    public function notifyDelay(TakeawayOrder $takeaway): string
    {
        $readyAt = $this->readyAt($takeaway);

        return $this->sendSms(
            $takeaway,
            "La Grande Muraille de Chine: mise a jour commande {$takeaway->reference}. Votre commande sera prete vers {$readyAt->format('H:i')}. Merci de votre patience.",
            'customer_estimate_notified_at',
        );
    }

    public function notifyReady(TakeawayOrder $takeaway): string
    {
        if ($takeaway->customer_ready_notified_at) {
            return 'already_sent';
        }

        return $this->sendSms(
            $takeaway,
            "La Grande Muraille de Chine: votre commande {$takeaway->reference} est prete. Vous pouvez venir la retirer. Merci.",
            'customer_ready_notified_at',
        );
    }

    private function readyAt(TakeawayOrder $takeaway): Carbon
    {
        return $takeaway->estimated_ready_at instanceof Carbon
            ? $takeaway->estimated_ready_at
            : now()->addMinutes((int)($takeaway->estimated_delay_minutes ?? 30));
    }

    private function sendSms(TakeawayOrder $takeaway, string $message, string $timestampColumn): string
    {
        if (trim((string)$takeaway->telephone) === '') {
            return 'unavailable';
        }

        try {
            app(TwilioSmsService::class)->send((string)$takeaway->telephone, $message);
            $takeaway->{$timestampColumn} = now();
            $takeaway->save();
            return 'sent';
        } catch (\Throwable $exception) {
            Log::warning('SMS operationnel commande emporter non envoye.', [
                'takeaway_id' => $takeaway->getKey(),
                'phone_hash' => hash('sha256', (string)$takeaway->telephone),
                'error' => $exception->getMessage(),
            ]);
            return 'failed';
        }
    }
}
