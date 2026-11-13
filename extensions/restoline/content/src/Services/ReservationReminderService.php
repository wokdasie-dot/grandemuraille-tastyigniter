<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\PrivateEnvironment;
use App\Services\Restoline\TwilioSmsService;
use Carbon\Carbon;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Restoline\Content\Models\ReservationReminder;
use Restoline\Content\Models\ReservationReminderSetting;
use RuntimeException;

class ReservationReminderService
{
    public function sendForDate(Carbon $date, ?int $reservationId = null): array
    {
        $settings = ReservationReminderSetting::current();
        $result = ['eligible' => 0, 'email_sent' => 0, 'sms_sent' => 0, 'failed' => 0];

        if (!$settings->enabled) {
            return $result;
        }

        $query = Reservation::query()
            ->where('location_id', 1)
            ->where('status_id', \Restoline\Content\Support\ReservationStatusIds::confirmed())
            ->whereDate('reserve_date', $date->toDateString())
            ->where('guest_num', '>=', (int)$settings->min_guests)
            ->orderBy('reservation_id');

        if ($reservationId) {
            $query->where('reservation_id', $reservationId);
        }

        $query->get()->each(function(Reservation $reservation) use ($settings, &$result): void {
            $result['eligible']++;
            $lock = Cache::lock('restoline:reservation-reminder:'.$reservation->reservation_id, 60);

            try {
                $lock->block(5, function() use ($reservation, $settings, &$result): void {
                    $reminder = ReservationReminder::query()->firstOrCreate(
                        [
                            'reservation_id' => $reservation->reservation_id,
                            'reserve_date' => $reservation->reserve_date->toDateString(),
                        ],
                        [
                            'token' => $this->uniqueToken(),
                            'email' => $reservation->email ?: null,
                            'telephone' => $reservation->telephone ?: null,
                        ],
                    );

                    $reminder->email = $reservation->email ?: null;
                    $reminder->telephone = $reservation->telephone ?: null;
                    $manageUrl = url('/r/'.$reminder->token);

                    if ($settings->email_enabled && $reservation->email && $reminder->email_status !== 'sent') {
                        try {
                            $this->sendEmail($reservation, $settings, $manageUrl);
                            $reminder->email_status = 'sent';
                            $reminder->email_error = null;
                            $result['email_sent']++;
                        } catch (\Throwable $exception) {
                            $reminder->email_status = 'failed';
                            $reminder->email_error = mb_substr($exception->getMessage(), 0, 1000);
                            $result['failed']++;
                            Log::warning('Rappel réservation: email non envoyé.', [
                                'reservation_id' => $reservation->reservation_id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    } elseif (!$settings->email_enabled || !$reservation->email) {
                        $reminder->email_status = 'skipped';
                    }

                    if ($settings->sms_enabled && $reservation->telephone && $reminder->sms_status !== 'sent') {
                        try {
                            app(TwilioSmsService::class)->send(
                                (string)$reservation->telephone,
                                $this->smsMessage($settings->sms_message, $reservation, $manageUrl),
                            );
                            $reminder->sms_status = 'sent';
                            $reminder->sms_error = null;
                            $result['sms_sent']++;
                        } catch (\Throwable $exception) {
                            $reminder->sms_status = 'failed';
                            $reminder->sms_error = mb_substr($exception->getMessage(), 0, 1000);
                            $result['failed']++;
                            Log::warning('Rappel réservation: SMS non envoyé.', [
                                'reservation_id' => $reservation->reservation_id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    } elseif (!$settings->sms_enabled || !$reservation->telephone) {
                        $reminder->sms_status = 'skipped';
                    }

                    if (in_array('sent', [$reminder->email_status, $reminder->sms_status], true)) {
                        $reminder->sent_at ??= now();
                    }

                    $reminder->save();
                });
            } catch (\Throwable $exception) {
                $result['failed']++;
                Log::warning('Rappel réservation: traitement impossible.', [
                    'reservation_id' => $reservation->reservation_id,
                    'error' => $exception->getMessage(),
                ]);
            } finally {
                optional($lock)->release();
            }
        });

        return $result;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(12);
        } while (ReservationReminder::query()->where('token', $token)->exists());

        return $token;
    }

    private function smsMessage(string $template, Reservation $reservation, string $manageUrl): string
    {
        $message = strtr($template, [
            '{date}' => $reservation->reserve_date->format('d/m/Y'),
            '{time}' => substr((string)$reservation->reserve_time, 0, 5),
            '{guests}' => (string)$reservation->guest_num,
            '{url}' => $manageUrl,
        ]);

        if (!str_contains($message, $manageUrl)) {
            $message = rtrim($message).' '.$manageUrl;
        }

        return $message;
    }

    private function sendEmail(
        Reservation $reservation,
        ReservationReminderSetting $settings,
        string $manageUrl,
    ): void {
        $name = trim((string)$reservation->first_name.' '.(string)$reservation->last_name) ?: 'Client';
        $date = $reservation->reserve_date->format('d/m/Y');
        $time = substr((string)$reservation->reserve_time, 0, 5);
        $guests = (int)$reservation->guest_num;
        $html = $this->emailHtml($name, $date, $time, $guests, (string)$settings->email_message, $manageUrl);

        try {
            $this->sendViaResend((string)$reservation->email, $name, (string)$settings->email_subject, $html);
        } catch (\Throwable $resendException) {
            try {
                $this->sendViaOwnMailbox(
                    (string)$reservation->email,
                    $name,
                    (string)$settings->email_subject,
                    $html,
                );
                Log::warning('Rappel envoyé par le secours SMTP La Grande Muraille de Chine après échec Resend.', [
                    'reservation_id' => $reservation->reservation_id,
                    'resend_error' => $resendException->getMessage(),
                ]);
            } catch (\Throwable $smtpException) {
                throw new RuntimeException(
                    'Resend: '.$resendException->getMessage().' | SMTP La Grande Muraille de Chine: '.$smtpException->getMessage(),
                );
            }
        }
    }

    private function sendViaResend(string $email, string $name, string $subject, string $html): void
    {
        $apiKey = (string)PrivateEnvironment::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            throw new RuntimeException('Clé API Resend absente.');
        }

        $fromEmail = (string)PrivateEnvironment::get('RESEND_FROM_EMAIL', config('mail.from.address', 'jinzhu1031@gmail.com'));
        $fromName = (string)PrivateEnvironment::get('RESEND_FROM_NAME', config('mail.from.name', "La Grande Muraille de Chine"));
        $response = Http::withToken($apiKey)->acceptJson()->timeout(20)->post('https://api.resend.com/emails', [
            'from' => $fromName.' <'.$fromEmail.'>',
            'to' => [$name !== '' ? $name.' <'.$email.'>' : $email],
            'subject' => $subject,
            'html' => $html,
        ]);

        if (!$response->successful() || !(string)$response->json('id')) {
            throw new RuntimeException('Resend HTTP '.$response->status().' '.$response->body());
        }
    }

    private function sendViaOwnMailbox(string $email, string $name, string $subject, string $html): void
    {
        if (!(string)config('mail.mailers.wokdasie.password')) {
            throw new RuntimeException('Mot de passe SMTP La Grande Muraille de Chine absent.');
        }

        Mail::mailer('wokdasie')->html($html, fn($message) => $message
            ->from('jinzhu1031@gmail.com', "La Grande Muraille de Chine")
            ->to($email, $name)
            ->subject($subject));
    }

    private function emailHtml(string $name, string $date, string $time, int $guests, string $message, string $url): string
    {
        return '<!doctype html><html lang="fr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;"><tr><td style="padding:30px 34px;">'
            .'<h1 style="margin:0 0 20px;color:#17211c;font-family:Georgia,serif;font-size:29px;font-weight:500;">Rappel de votre réservation</h1>'
            .'<p style="color:#4b5563;line-height:1.7;">Bonjour '.e($name).',</p>'
            .'<p style="color:#4b5563;line-height:1.7;">'.e($message).'</p>'
            .'<div style="margin:24px 0;padding:20px;background:#f4f6f3;border-left:4px solid #215b45;">'
            .'<strong style="font-size:20px;color:#17211c;">'.e((string)$guests).' personnes</strong>'
            .'<span style="display:block;margin-top:8px;color:#4b5563;">'.e($date).' à '.e($time).'</span></div>'
            .'<a href="'.e($url).'" style="display:inline-block;padding:14px 20px;background:#215b45;color:#fff;text-decoration:none;font-weight:700;">Vérifier ou modifier ma réservation</a>'
            .'<p style="margin-top:28px;color:#6b7280;font-size:13px;line-height:1.6;">Vous pouvez également utiliser ce lien pour annuler la réservation si nécessaire.</p>'
            .'</td></tr></table></td></tr></table></body></html>';
    }
}
