<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\PrivateEnvironment;
use App\Services\Restoline\TwilioSmsService;
use Igniter\Cart\Models\Order;
use Igniter\Cart\Notifications\OrderCreatedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final class TakeawayOrderNotificationService
{
    public function sendOrderReceived(Order $order): void
    {
        $cacheKey = 'restoline:takeaway-order:received:'.$order->getKey();
        if (!Cache::add($cacheKey, true, now()->addDays(30))) {
            return;
        }

        $order->loadMissing('location', 'payment_method', 'menus');
        app(TakeawayWorkflowService::class)->registerIncomingOrder($order);
        $this->notifyTeam($order);
        $this->notifyCustomerByEmail($order);
        $this->notifyCustomerBySms($order);
    }

    private function notifyTeam(Order $order): void
    {
        try {
            OrderCreatedNotification::make()->subject($order)->broadcast();
        } catch (\Throwable $exception) {
            Log::warning('Notification interne commande emporter non envoyee.', [
                'order_id' => $order->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }

        $restaurantEmail = trim((string)PrivateEnvironment::get(
            'RESTAURANT_NOTIFICATION_EMAIL',
            env('RESTAURANT_NOTIFICATION_EMAIL', ''),
        ));

        if ($restaurantEmail === '') {
            return;
        }

        try {
            $this->sendEmailWithFallback(
                $restaurantEmail,
                "La Grande Muraille de Chine",
                'Nouvelle commande a emporter n°'.$order->getKey(),
                $this->emailFrame('Nouvelle commande', $this->orderDetails($order, true)),
            );
        } catch (\Throwable $exception) {
            Log::warning('Email restaurant commande emporter non envoye.', [
                'order_id' => $order->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyCustomerByEmail(Order $order): void
    {
        $email = trim((string)$order->email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $this->sendEmailWithFallback(
                $email,
                $this->customerName($order),
                "La Grande Muraille de Chine: commande a emporter n°{$order->getKey()} recue",
                $this->emailFrame(
                    'Commande recue',
                    '<p>Bonjour '.e($this->customerName($order)).',</p>'
                    .'<p>Votre commande a emporter <strong>n°'.e((string)$order->getKey()).'</strong> a bien ete recue.</p>'
                    .'<p>Le restaurant vous confirmera le delai de retrait. Le reglement se fait au restaurant.</p>'
                    .$this->orderDetails($order, false),
                ),
            );
        } catch (\Throwable $exception) {
            Log::warning('Email client commande emporter non envoye.', [
                'order_id' => $order->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyCustomerBySms(Order $order): void
    {
        if (trim((string)$order->telephone) === '') {
            return;
        }

        try {
            app(TwilioSmsService::class)->send(
                (string)$order->telephone,
                "La Grande Muraille de Chine: commande n°{$order->getKey()} recue. Nous confirmons votre retrait tres vite. Paiement au restaurant.",
            );
        } catch (\Throwable $exception) {
            Log::warning('SMS client commande emporter non envoye.', [
                'order_id' => $order->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendEmailWithFallback(string $email, string $name, string $subject, string $html): void
    {
        try {
            $this->sendEmailViaResend($email, $name, $subject, $html);
        } catch (\Throwable $resendException) {
            $this->sendEmailViaWokdasieMailbox($email, $name, $subject, $html);
            Log::warning('Email commande emporter envoye par SMTP de secours.', [
                'email_hash' => hash('sha256', strtolower($email)),
                'resend_error' => $resendException->getMessage(),
            ]);
        }
    }

    private function sendEmailViaResend(string $email, string $name, string $subject, string $html): void
    {
        $apiKey = (string)PrivateEnvironment::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            throw new RuntimeException('Cle API Resend absente.');
        }

        $fromEmail = (string)PrivateEnvironment::get('RESEND_FROM_EMAIL', config('mail.from.address'));
        $fromName = (string)PrivateEnvironment::get('RESEND_FROM_NAME', config('mail.from.name', "La Grande Muraille de Chine"));
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(12)
            ->post('https://api.resend.com/emails', [
                'from' => $fromName.' <'.$fromEmail.'>',
                'to' => [$name !== '' ? $name.' <'.$email.'>' : $email],
                'subject' => $subject,
                'html' => $html,
            ]);

        if (!$response->successful() || (string)$response->json('id') === '') {
            throw new RuntimeException('Resend HTTP '.$response->status());
        }
    }

    private function sendEmailViaWokdasieMailbox(string $email, string $name, string $subject, string $html): void
    {
        if (!(string)config('mail.mailers.wokdasie.password')) {
            throw new RuntimeException('SMTP non configure.');
        }

        Mail::mailer('wokdasie')->html($html, fn($message) => $message
            ->from('jinzhu1031@gmail.com', "La Grande Muraille de Chine")
            ->to($email, $name)
            ->subject($subject));
    }

    private function customerName(Order $order): string
    {
        return trim((string)$order->first_name.' '.(string)$order->last_name) ?: 'Client';
    }

    private function orderDetails(Order $order, bool $forTeam): string
    {
        $dateTime = $order->order_datetime?->format('d/m/Y H:i') ?? '';
        $items = $order->menus->map(fn($menu) => e((string)$menu->quantity).' x '.e((string)$menu->name))->implode('<br>');

        return '<div style="margin-top:24px;padding:18px;background:#f4f6f3;line-height:1.6;">'
            .'<strong>Commande n°'.e((string)$order->getKey()).'</strong><br>'
            .'Retrait: '.e($dateTime).'<br>'
            .'Total: '.e(number_format((float)$order->order_total, 2, ',', ' ')).' EUR<br>'
            .'Paiement: '.e((string)($order->payment_method?->name ?? 'Au restaurant')).'<br><br>'
            .'<strong>Articles</strong><br>'.$items
            .($forTeam ? '<br><br><strong>Client</strong>: '.e($this->customerName($order)).'<br>Telephone: '.e((string)$order->telephone).'<br>Email: '.e((string)$order->email).'<br>Note: '.e((string)$order->comment) : '')
            .'</div>';
    }

    private function emailFrame(string $heading, string $content): string
    {
        return '<!doctype html><html lang="fr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;"><tr><td style="padding:28px 34px;text-align:left;">'
            .'<h1 style="margin:0 0 18px;color:#17211c;font-family:Georgia,serif;font-size:28px;font-weight:500;">'.e($heading).'</h1>'
            .$content
            .'</td></tr></table></td></tr></table></body></html>';
    }
}
