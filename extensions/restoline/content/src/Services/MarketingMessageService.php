<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\TwilioSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\MarketingCampaign;
use Restoline\Content\Models\NewsletterSubscriber;

class MarketingMessageService
{
    public function sendBirthdayPromotions(): array
    {
        $campaign = MarketingCampaign::query()
            ->where('type', 'birthday')
            ->where('status', true)
            ->latest('updated_at')
            ->first();

        if (!$campaign) {
            return ['email' => 0, 'sms' => 0, 'skipped' => 0];
        }

        $target = now()->addDays((int)$campaign->birthday_days_before);
        $year = (int)now()->year;
        $result = ['email' => 0, 'sms' => 0, 'skipped' => 0];

        NewsletterSubscriber::query()
            ->active()
            ->where('birthday_day', (int)$target->day)
            ->where('birthday_month', (int)$target->month)
            ->orderBy('subscriber_id')
            ->chunkById(100, function($subscribers) use ($campaign, $year, &$result): void {
                foreach ($subscribers as $subscriber) {
                    $sent = false;

                    if (in_array($campaign->channel, ['email', 'both'], true)
                        && $subscriber->email_consent
                        && $subscriber->email
                        && (int)$subscriber->last_birthday_email_year !== $year) {
                        $this->sendEmail($subscriber, $campaign);
                        $subscriber->last_birthday_email_year = $year;
                        $result['email']++;
                        $sent = true;
                    }

                    if (in_array($campaign->channel, ['sms', 'both'], true)
                        && $subscriber->sms_consent
                        && $subscriber->phone
                        && (int)$subscriber->last_birthday_sms_year !== $year) {
                        $this->sendSms($subscriber, $campaign);
                        $subscriber->last_birthday_sms_year = $year;
                        $result['sms']++;
                        $sent = true;
                    }

                    if ($sent) {
                        $subscriber->save();
                    } else {
                        $result['skipped']++;
                    }
                }
            }, 'subscriber_id');

        return $result;
    }

    public function sendEmail(NewsletterSubscriber $subscriber, MarketingCampaign $campaign): void
    {
        $body = nl2br(e($this->renderMessage((string)$campaign->email_body, $subscriber, $campaign)));
        $unsubscribeUrl = $this->unsubscribeUrl($subscriber);

        Mail::html(
            '<!doctype html><html lang="fr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;background:#f3f4f6;">
                    <tr><td align="center">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;">
                            <tr><td style="padding:30px 34px;">
                                <h1 style="margin:0 0 18px;color:#17211c;font-family:Georgia,serif;font-size:30px;font-weight:500;">'.e($campaign->title).'</h1>
                                <div style="color:#374151;line-height:1.7;">'.$body.'</div>
                                <p style="margin:28px 0 0;color:#6b7280;font-size:12px;line-height:1.5;">
                                    Vous recevez ce message car vous avez accepté les offres La Grande Muraille de Chine.
                                    <a href="'.e($unsubscribeUrl).'" style="color:#215b45;">Se désinscrire</a>.
                                </p>
                            </td></tr>
                        </table>
                    </td></tr>
                </table>
            </body></html>',
            fn($message) => $message
                ->to($subscriber->email, $subscriber->first_name ?: null)
                ->subject($campaign->email_subject ?: $campaign->title),
        );
    }

    public function sendSms(NewsletterSubscriber $subscriber, MarketingCampaign $campaign): void
    {
        try {
            app(TwilioSmsService::class)->send(
                (string)$subscriber->phone,
                $this->renderMessage((string)$campaign->sms_body, $subscriber, $campaign, false),
            );
        } catch (\Throwable $exception) {
            Log::warning('SMS marketing non envoyé.', [
                'subscriber_id' => $subscriber->subscriber_id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function renderMessage(string $message, NewsletterSubscriber $subscriber, MarketingCampaign $campaign, bool $personalizedUnsubscribe = true): string
    {
        $start = now()->format('d/m/Y');
        $end = now()->addDays((int)$campaign->valid_days)->format('d/m/Y');

        return strtr($message, [
            '{prenom}' => $subscriber->first_name ?: 'cher client',
            '{date_debut}' => $start,
            '{date_fin}' => $end,
            '{desinscription}' => $personalizedUnsubscribe
                ? $this->unsubscribeUrl($subscriber)
                : $this->publicUnsubscribeUrl(),
        ]);
    }

    private function unsubscribeUrl(NewsletterSubscriber $subscriber): string
    {
        return url('/newsletter/desinscription/'.$subscriber->unsubscribe_token);
    }

    private function publicUnsubscribeUrl(): string
    {
        return url('/newsletter/desinscription');
    }
}
