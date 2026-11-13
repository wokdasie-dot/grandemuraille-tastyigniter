<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\PrivateEnvironment;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Restoline\Content\Models\ReservationFeedback;
use Restoline\Content\Models\ReservationFeedbackSetting;

class ReservationFeedbackService
{
    public function sendDueFeedbackEmails(?int $daysAfterVisit = null, int $limit = 30): array
    {
        $settings = ReservationFeedbackSetting::current();
        if (!$settings->enabled) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $daysAfterVisit ??= max(1, (int)$settings->days_after_visit);
        $targetDate = now()->subDays($daysAfterVisit)->toDateString();
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $reservations = Reservation::query()
            ->where('location_id', 1)
            ->whereDate('reserve_date', $targetDate)
            ->where('status_id', \Restoline\Content\Support\ReservationStatusIds::confirmed())
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('reserve_time')
            ->limit($limit * 5)
            ->get();

        foreach ($reservations as $reservation) {
            if (!$this->isSelected($reservation, (int)$settings->sample_percent)) {
                $skipped++;
                continue;
            }

            if (ReservationFeedback::query()->where('reservation_id', $reservation->reservation_id)->exists()) {
                $skipped++;
                continue;
            }

            if ($this->wasRecentlyContacted($reservation, (int)$settings->cooldown_days)) {
                $skipped++;
                continue;
            }

            $feedback = $this->createFeedback($reservation);
            if (!$feedback) {
                $skipped++;
                continue;
            }

            try {
                $this->sendFeedbackEmail($feedback, $settings);
                $feedback->sent_at = now();
                $feedback->save();
                $sent++;
            } catch (\Throwable $exception) {
                $failed++;
                Log::warning('Email retour réservation non envoyé.', [
                    'reservation_id' => $reservation->reservation_id,
                    'feedback_id' => $feedback->feedback_id,
                    'error' => $exception->getMessage(),
                ]);
            }

            if ($sent >= $limit) {
                break;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    public function googleReviewUrl(ReservationFeedback $feedback): string
    {
        $url = trim((string)PrivateEnvironment::get(
            'RESTOLINE_GOOGLE_REVIEW_URL',
            env('RESTOLINE_GOOGLE_REVIEW_URL', ''),
        ));
        if ($url !== '') {
            return $url;
        }

        return 'https://www.google.com/search?q=Wok+d%27Asie+Hornu+avis+Google';
    }

    private function isSelected(Reservation $reservation, int $samplePercent): bool
    {
        $samplePercent = max(0, min(100, $samplePercent));
        if ($samplePercent === 100) {
            return true;
        }

        if ($samplePercent % 20 === 0) {
            return ((int)$reservation->reservation_id % 5) < intdiv($samplePercent, 20);
        }

        $hash = sprintf('%u', crc32($reservation->reservation_id.'|'.$reservation->reserve_date));
        return ((int)$hash % 100) < $samplePercent;
    }

    private function wasRecentlyContacted(Reservation $reservation, int $cooldownDays): bool
    {
        if ($cooldownDays <= 0) {
            return false;
        }

        $email = mb_strtolower(trim((string)$reservation->email));
        $telephone = trim((string)$reservation->telephone);
        if ($email === '' && $telephone === '') {
            return false;
        }

        return ReservationFeedback::query()
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->where(function($query) use ($email, $telephone): void {
                if ($email !== '') {
                    $query->whereRaw('LOWER(email) = ?', [$email]);
                }
                if ($telephone !== '') {
                    $email !== '' ? $query->orWhere('telephone', $telephone) : $query->where('telephone', $telephone);
                }
            })
            ->exists();
    }

    private function createFeedback(Reservation $reservation): ?ReservationFeedback
    {
        return DB::transaction(function() use ($reservation): ?ReservationFeedback {
            // Lock the reservation while checking and creating its single feedback request.
            Reservation::query()
                ->whereKey($reservation->reservation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (ReservationFeedback::query()
                ->where('reservation_id', $reservation->reservation_id)
                ->exists()) {
                return null;
            }

            return ReservationFeedback::query()->create([
                'reservation_id' => $reservation->reservation_id,
                'token' => Str::random(48),
                'customer_name' => trim((string)$reservation->first_name.' '.(string)$reservation->last_name),
                'email' => $reservation->email,
                'telephone' => $reservation->telephone,
                'reserve_date' => $reservation->reserve_date?->format('Y-m-d'),
                'reserve_time' => substr((string)$reservation->reserve_time, 0, 8),
                'guest_num' => (int)$reservation->guest_num,
                'reservation_channel' => $this->detectReservationChannel($reservation),
            ]);
        });
    }

    private function sendFeedbackEmail(ReservationFeedback $feedback, ReservationFeedbackSetting $settings): void
    {
        $feedbackUrl = route('restoline.reservation-feedback.show', ['token' => $feedback->token]);
        $googleUrl = route('restoline.reservation-feedback.google', ['token' => $feedback->token]);
        $name = trim((string)$feedback->customer_name) ?: 'bonjour';

        Mail::html($this->emailHtml($name, $feedbackUrl, $googleUrl, $settings), function($message) use ($feedback, $settings): void {
            $message
                ->to((string)$feedback->email, (string)$feedback->customer_name)
                ->subject((string)$settings->email_subject);
        });
    }

    private function emailHtml(
        string $name,
        string $feedbackUrl,
        string $googleUrl,
        ReservationFeedbackSetting $settings,
    ): string
    {
        $button = 'display:inline-block;padding:13px 18px;border-radius:6px;text-decoration:none;font-weight:700;';
        $googleBlock = $settings->google_review_enabled
            ? '<p>Vous pouvez aussi laisser un avis Google si vous souhaitez partager votre expérience publiquement.</p>'
                .'<p style="margin:18px 0;"><a href="'.e($googleUrl).'" style="'.$button.'background:#f3efe7;color:#17211d;border:1px solid #d8c7a3;">Laisser un avis Google</a></p>'
            : '';

        return '<div style="font-family:Arial,sans-serif;color:#17211d;line-height:1.55;max-width:620px;margin:0 auto;padding:22px;">'
            .'<h1 style="font-family:Georgia,serif;font-size:28px;margin:0 0 16px;">'.e((string)$settings->email_heading).'</h1>'
            .'<p>Bonjour '.e($name).',</p>'
            .'<p>'.nl2br(e((string)$settings->email_message)).'</p>'
            .'<p style="margin:24px 0;"><a href="'.e($feedbackUrl).'" style="'.$button.'background:#215b45;color:#fff;">Donner mon retour</a></p>'
            .$googleBlock
            .'<p style="color:#6b7280;font-size:13px;margin-top:26px;">Cet email est envoyé seulement à une partie des réservations afin de ne pas déranger nos clients.</p>'
            .'</div>';
    }

    private function detectReservationChannel(Reservation $reservation): string
    {
        $comment = mb_strtolower((string)$reservation->comment);
        if (str_contains($comment, 'message vocal') || str_contains($comment, 'répondeur') || str_contains($comment, 'repondeur')) {
            return 'phone';
        }

        return 'online';
    }
}
