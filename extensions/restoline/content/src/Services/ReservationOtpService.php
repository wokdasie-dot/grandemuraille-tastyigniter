<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\TwilioSmsService;
use App\Services\Restoline\GroupBenefitSmsService;
use Igniter\Admin\Models\Status;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Restoline\Content\Models\ReservationClientNotificationSetting;
use Restoline\Content\Support\ReservationStatusIds;
use RuntimeException;

class ReservationOtpService
{
    private const int CODE_TTL_SECONDS = 600;
    private const int PHONE_VALIDATION_MIN_GUESTS = 7;

    public function findReservation(string $hash): ?Reservation
    {
        return Reservation::query()->whereHash($hash)->first();
    }

    public function requiresPhoneValidation(Reservation $reservation): bool
    {
        return (bool)config('restoline.features.sms', false)
            && (int)$reservation->guest_num >= self::PHONE_VALIDATION_MIN_GUESTS;
    }

    public function requiresGroupPhoneConfirmation(Reservation $reservation): bool
    {
        $threshold = (int)config('restoline.reservation.phone_confirm_from', 12);

        return $threshold > 0 && (int)$reservation->guest_num >= $threshold;
    }

    public function ensureConfirmedWithoutCodeIfAllowed(Reservation $reservation): bool
    {
        if ($this->requiresGroupPhoneConfirmation($reservation)) {
            $this->sendGroupPendingNotifications($reservation);

            return false;
        }

        if ($this->requiresPhoneValidation($reservation)) {
            return false;
        }

        $this->confirmReservation($reservation);

        return true;
    }

    public function sendGroupPendingNotifications(Reservation $reservation): void
    {
        $notificationKey = 'restoline:reservation:group-pending:'.$reservation->reservation_id;
        if (Cache::has($notificationKey)) {
            return;
        }

        $name = $this->customerName($reservation);
        $date = $reservation->reserve_date->format('d/m/Y');
        $time = substr((string)$reservation->reserve_time, 0, 5);
        $guests = (int)$reservation->guest_num;
        $threshold = (int)config('restoline.reservation.phone_confirm_from', 12);
        $restaurant = $this->restaurantName();
        $phone = $this->restaurantPhoneLabel();

        try {
            if ($reservation->email && (bool)config('restoline.features.client_email', true)) {
                $this->sendEmailWithFallback(
                    (string)$reservation->email,
                    $name,
                    $restaurant.' - Demande de réservation reçue',
                    '<p>Bonjour '.e($name).',</p>'
                    .'<p>Votre demande pour <strong>'.$guests.' personnes</strong> le '.$date.' à '.$time.' est bien reçue.</p>'
                    .'<p>À partir de '.$threshold.' personnes, le restaurant vous rappelle pour confirmer. Cette réservation n\'est pas encore confirmée.</p>'
                    .'<p>Téléphone : '.e($phone).'</p>',
                );
            }

            if ((bool)config('restoline.features.restaurant_email', true)) {
                $this->sendEmailWithFallback(
                    $this->restaurantNotificationEmail(),
                    $restaurant,
                    'GROUPE ≥ '.$threshold.' : rappeler le client — réservation #'.$reservation->reservation_id,
                    '<p><strong>GROUPE ≥ '.$threshold.' : rappeler le client</strong></p>'
                    .'<p>'.e($name).' — '.$guests.' personnes — '.$date.' à '.$time.'</p>'
                    .'<p>Téléphone : '.e((string)$reservation->telephone).'<br>E-mail : '.e((string)$reservation->email).'</p>'
                    .'<p>La demande reste en attente, sans confirmation automatique.</p>',
                );
            }

            Cache::put($notificationKey, true, now()->addDays(30));
        } catch (\Throwable $exception) {
            Log::warning('Échec des notifications de groupe.', [
                'reservation_id' => $reservation->reservation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function ensureCodeSent(Reservation $reservation): void
    {
        if (!$this->requiresPhoneValidation($reservation)) {
            return;
        }

        if (Cache::has($this->cacheKey($reservation))) {
            return;
        }

        $this->sendCode($reservation);
    }

    public function sendCode(Reservation $reservation): void
    {
        if (!$this->requiresPhoneValidation($reservation)) {
            throw new RuntimeException('Le code SMS n’est pas nécessaire pour cette réservation.');
        }

        if (!$reservation->telephone) {
            throw new RuntimeException('Aucun numéro de téléphone n’est associé à cette réservation.');
        }

        $rateKey = 'restoline:otp:send:'.$reservation->reservation_id;
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            throw new RuntimeException('Trop de SMS ont été demandés. Réessayez dans 10 minutes.');
        }

        RateLimiter::hit($rateKey, self::CODE_TTL_SECONDS);

        $code = (string)random_int(1000, 9999);
        Cache::put($this->cacheKey($reservation), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], self::CODE_TTL_SECONDS);

        app(TwilioSmsService::class)->send(
            $reservation->telephone,
            sprintf(
                '%s : votre code de validation est %s. Valable 10 minutes.',
                $this->restaurantName(),
                $code,
            ),
        );
    }

    public function verify(Reservation $reservation, string $code): bool
    {
        $payload = Cache::get($this->cacheKey($reservation));
        if (!$payload) {
            throw new RuntimeException('Le code a expiré. Demandez un nouveau code.');
        }

        $attempts = (int)($payload['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($this->cacheKey($reservation));
            throw new RuntimeException('Trop de tentatives incorrectes. Demandez un nouveau code.');
        }

        if (!Hash::check($code, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->cacheKey($reservation), $payload, self::CODE_TTL_SECONDS);
            throw new RuntimeException('Code incorrect. Vérifiez le SMS reçu.');
        }

        Cache::forget($this->cacheKey($reservation));
        $this->confirmReservation($reservation);

        return true;
    }

    public function isVerified(Reservation $reservation): bool
    {
        return Cache::has($this->verifiedKey($reservation));
    }

    private function confirmReservation(Reservation $reservation): void
    {
        Cache::put($this->verifiedKey($reservation), true, now()->addDay());

        $confirmedStatusId = ReservationStatusIds::confirmed();

        if ((int)$reservation->status_id !== $confirmedStatusId) {
            $confirmedStatus = $confirmedStatusId > 0 ? Status::find($confirmedStatusId) : null;
            if (!$confirmedStatus || !$reservation->addStatusHistory($confirmedStatus, ['notify' => false])) {
                throw new RuntimeException('Impossible de confirmer automatiquement la réservation.');
            }
        }
    }

    public function sendStatusNotifications(Reservation $reservation): void
    {
        $statusId = (int)$reservation->status_id;
        $confirmedStatusId = ReservationStatusIds::confirmed();
        $canceledStatusId = ReservationStatusIds::canceled();

        if (!in_array($statusId, array_filter([$confirmedStatusId, $canceledStatusId]), true)) {
            return;
        }

        if ($this->shouldSkipAutomaticStatusNotifications($reservation)) {
            Log::info('Notifications de statut ignorees pour une reservation geree en interne.', [
                'reservation_id' => $reservation->reservation_id,
                'status_id' => $statusId,
            ]);

            return;
        }

        $lock = Cache::lock(
            'restoline:reservation:status-notifications-lock:'.$reservation->reservation_id.':'.$statusId,
            30,
        );

        try {
            $lock->block(5, function() use ($reservation, $statusId, $confirmedStatusId): void {
                $clientReachedByEmail = $this->sendStatusEmails($reservation, $statusId);
                if ((bool)config('restoline.features.sms', false)
                    && (!$clientReachedByEmail || $this->shouldSendSmsAfterSuccessfulEmail())) {
                    $this->sendStatusSms($reservation, $statusId);
                }
                if ($statusId === $confirmedStatusId && (bool)config('restoline.features.sms', false)) {
                    app(GroupBenefitSmsService::class)->sendIfEligible($reservation);
                }
            });
        } catch (\Throwable $exception) {
            Log::warning('Échec des notifications de réservation.', [
                'reservation_id' => $reservation->reservation_id,
                'status_id' => $statusId,
                'error' => $exception->getMessage(),
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    private function shouldSkipAutomaticStatusNotifications(Reservation $reservation): bool
    {
        $comment = mb_strtolower((string)$reservation->comment);

        return str_contains($comment, 'message vocal')
            || str_contains($comment, 'répondeur téléphonique')
            || str_contains($comment, 'repondeur telephonique')
            || str_contains($comment, 'restoline service');
    }

    private function shouldSendSmsAfterSuccessfulEmail(): bool
    {
        try {
            if (!Schema::hasTable('restoline_reservation_client_notification_settings')) {
                return false;
            }

            return ReservationClientNotificationSetting::current()->sendsSmsAfterSuccessfulEmail();
        } catch (\Throwable $exception) {
            Log::warning('Réglage confirmation client indisponible, mode actuel conservé.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendStatusSms(Reservation $reservation, int $statusId): void
    {
        if (!$reservation->telephone) {
            return;
        }

        $notificationKey = 'restoline:reservation:status-sms:'.$reservation->reservation_id.':'.$statusId;
        if (Cache::has($notificationKey)) {
            return;
        }

        $name = trim((string)$reservation->first_name.' '.(string)$reservation->last_name);
        $hasName = $name !== '' && !in_array(mb_strtolower($name), ['client', 'client sms'], true);
        $identity = $hasName ? ' au nom de '.mb_substr($name, 0, 35) : ' liee a ce numero';

        app(TwilioSmsService::class)->send(
            $reservation->telephone,
            $statusId === ReservationStatusIds::confirmed()
                ? sprintf(
                    "%s: Reservation confirmee%s. %d pers - %s a %s. Gerer: %s",
                    $this->restaurantName(),
                    $identity,
                    (int)$reservation->guest_num,
                    $reservation->reserve_date->format('d/m/Y'),
                    substr((string)$reservation->reserve_time, 0, 5),
                    $this->publicManageUrl(),
                )
                : sprintf(
                    "%s: Reservation annulee. %d pers - %s a %s. Gerer: %s",
                    $this->restaurantName(),
                    (int)$reservation->guest_num,
                    $reservation->reserve_date->format('d/m/Y'),
                    substr((string)$reservation->reserve_time, 0, 5),
                    $this->publicManageUrl(),
                ),
        );

        Cache::put($notificationKey, true, now()->addDays(30));
    }

    public function sendModificationNotifications(Reservation $reservation): void
    {
        $reservation->refresh();
        $fingerprint = sha1(implode('|', [
            $this->customerName($reservation),
            (string)$reservation->guest_num,
            $reservation->reserve_date->format('Y-m-d'),
            substr((string)$reservation->reserve_time, 0, 5),
            (string)$reservation->updated_at,
        ]));
        $notificationKey = 'restoline:reservation:modified:'.$reservation->reservation_id.':'.$fingerprint;

        if (Cache::has($notificationKey)) {
            return;
        }

        $name = $this->customerName($reservation);
        $date = $reservation->reserve_date->format('d/m/Y');
        $time = substr((string)$reservation->reserve_time, 0, 5);
        $guests = (int)$reservation->guest_num;
        $guestLabel = $guests === 1 ? 'personne' : 'personnes';
        $manageUrl = $this->publicManageUrl();
        $privateManageUrl = $this->privateManageUrl($reservation);
        $directionsUrl = $this->directionsUrl();
        $dashboardUrl = $this->dashboardUrl();

        if ((bool)config('restoline.features.sms', false) && $reservation->telephone) {
            app(TwilioSmsService::class)->send(
                $reservation->telephone,
                sprintf(
                    "%s: Reservation modifiee. %d pers - %s a %s. Gerer: %s",
                    $this->restaurantName(),
                    $guests,
                    $date,
                    $time,
                    $manageUrl,
                ),
            );
        }

        if ($reservation->email) {
            $this->sendEmailWithFallback(
                (string)$reservation->email,
                $name,
                $this->restaurantName().' - Réservation modifiée',
                $this->clientEmailHtml(
                    $this->publicLogoUrl(),
                    'Votre réservation a été modifiée',
                    'Vos nouvelles informations sont enregistrées. Vous pouvez encore modifier ou annuler votre réservation via le bouton ci-dessous.',
                    $name,
                    $guests,
                    $guestLabel,
                    $date,
                    $time,
                    $privateManageUrl,
                    $directionsUrl,
                    true,
                ),
            );
        }

        $this->sendEmailWithFallback(
            $this->restaurantNotificationEmail(),
            $this->restaurantName(),
            "Réservation modifiée #{$reservation->reservation_id} - ".$this->restaurantName(),
            $this->restaurantEmailHtml(
                $this->publicLogoUrl(),
                $reservation->reservation_id,
                $name,
                (string)$reservation->telephone,
                (string)$reservation->email,
                $guests,
                $guestLabel,
                $date,
                $time,
                $dashboardUrl,
                'Réservation modifiée',
            ),
        );

        Cache::put($notificationKey, true, now()->addDays(30));
    }

    public function sendManagementAccessCode(string $email, string $code): void
    {
        $restaurant = $this->restaurantName();

        $this->sendEmailWithFallback(
            $email,
            '',
            $restaurant.' - Code d’accès à votre réservation',
            '<p>Bonjour,</p>'
            .'<p>Votre code d’accès à votre réservation <strong>'.e($restaurant).'</strong> est : '
            .'<strong style="font-size:24px;letter-spacing:4px">'.e($code).'</strong></p>'
            .'<p>Il est valable pendant 10 minutes.</p>'
            .'<p>À bientôt,<br>'.e($restaurant).'</p>',
        );
    }

    private function sendStatusEmails(Reservation $reservation, int $statusId): bool
    {
        $reservation->refresh();
        $date = $reservation->reserve_date->format('d/m/Y');
        $time = substr((string)$reservation->reserve_time, 0, 5);
        $guests = (int)$reservation->guest_num;
        $guestLabel = $guests === 1 ? 'personne' : 'personnes';
        $name = trim((string)$reservation->first_name.' '.(string)$reservation->last_name);
        $manageUrl = $this->privateManageUrl($reservation);
        $dashboardUrl = $this->dashboardUrl();
        $directionsUrl = $this->directionsUrl();
        $clientReachedByEmail = false;

        if ($reservation->email) {
            $clientKey = 'restoline:reservation:status-email-client:'.$reservation->reservation_id.':'.$statusId;
            if (Cache::has($clientKey)) {
                $clientReachedByEmail = true;
            } else {
                $confirmed = $statusId === ReservationStatusIds::confirmed();
                $subject = $confirmed
                    ? $this->restaurantName().' - Réservation confirmée'
                    : $this->restaurantName().' - Réservation annulée';
                $heading = $confirmed ? 'Votre réservation est confirmée' : 'Votre réservation est annulée';
                $intro = $confirmed
                    ? 'Votre table est bien réservée. Vous pouvez modifier ou annuler votre réservation via le bouton ci-dessous.'
                    : 'Votre réservation a bien été annulée.';

                $clientHtmlFactory = fn(string $logoUrl) => $this->clientEmailHtml(
                    $logoUrl,
                    $heading,
                    $intro,
                    $name,
                    $guests,
                    $guestLabel,
                    $date,
                    $time,
                    $manageUrl,
                    $directionsUrl,
                    $confirmed,
                );

                try {
                    $providerMessageId = $this->sendEmailWithFallback(
                        (string)$reservation->email,
                        $name,
                        $subject,
                        $clientHtmlFactory($this->publicLogoUrl()),
                    );

                    Cache::put($clientKey, true, now()->addDays(30));
                    $this->recordReservationEmailNotification(
                        $reservation,
                        $statusId,
                        (string)$reservation->email,
                        $providerMessageId,
                        'resend',
                    );
                    $clientReachedByEmail = true;
                } catch (\Throwable $exception) {
                    Log::warning('Échec email client via Resend.', [
                        'reservation_id' => $reservation->reservation_id,
                        'status_id' => $statusId,
                        'email' => $reservation->email,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        if (!in_array($statusId, [ReservationStatusIds::confirmed(), ReservationStatusIds::canceled()], true)) {
            return $clientReachedByEmail;
        }

        $restaurantKey = 'restoline:reservation:status-email-restaurant:'.$reservation->reservation_id.':'.$statusId;
        if (Cache::has($restaurantKey)) {
            return $clientReachedByEmail;
        }

        $restaurantSubject = $statusId === ReservationStatusIds::confirmed()
            ? "Reservation confirmee #{$reservation->reservation_id} - ".$this->restaurantName()
            : "Reservation annulee #{$reservation->reservation_id} - ".$this->restaurantName();
        $restaurantHeading = $statusId === ReservationStatusIds::confirmed() ? 'Reservation confirmee' : 'Reservation annulee';

        $this->sendEmailWithFallback(
            $this->restaurantNotificationEmail(),
            $this->restaurantName(),
            $restaurantSubject,
            $this->restaurantEmailHtml(
                $this->publicLogoUrl(),
                $reservation->reservation_id,
                $name,
                (string)$reservation->telephone,
                (string)$reservation->email,
                $guests,
                $guestLabel,
                $date,
                $time,
                $dashboardUrl,
                $restaurantHeading,
            ),
        );

        Cache::put($restaurantKey, true, now()->addDays(30));

        return $clientReachedByEmail;
    }

    private function recordReservationEmailNotification(
        Reservation $reservation,
        int $statusId,
        string $email,
        string $providerMessageId = '',
        string $provider = '',
    ): void
    {
        if ($email === '' || !Schema::hasTable('restoline_reservation_email_notifications')) {
            return;
        }

        DB::table('restoline_reservation_email_notifications')->updateOrInsert(
            [
                'reservation_id' => $reservation->reservation_id,
                'status_id' => $statusId,
                'message_type' => 'client_status',
            ],
            [
                'email' => strtolower($email),
                'telephone' => (string)$reservation->telephone,
                'provider_message_id' => $providerMessageId !== '' ? $providerMessageId : null,
                'email_status' => 'pending',
                'last_payload' => $provider !== '' ? json_encode(['provider' => $provider]) : null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        if ($provider !== '' && Schema::hasTable('restoline_email_provider_events')) {
            $notification = $this->findEmailNotification($email, $providerMessageId);
            DB::table('restoline_email_provider_events')->insert([
                'provider' => $provider,
                'event_type' => 'accepted',
                'email' => strtolower($email),
                'provider_message_id' => $providerMessageId !== '' ? $providerMessageId : null,
                'reservation_email_notification_id' => $notification?->notification_id,
                'payload' => json_encode([
                    'reservation_id' => $reservation->reservation_id,
                    'status_id' => $statusId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function recordSendLayerEvent(string $event, string $email, string $messageId, array $payload): ?int
    {
        return $this->recordEmailProviderEvent('sendlayer', $event, $email, $messageId, $payload);
    }

    public function recordEmailProviderEvent(string $provider, string $event, string $email, string $messageId, array $payload): ?int
    {
        $event = strtolower(trim($event));
        $email = strtolower(trim($email));
        $messageId = trim($messageId);
        $notification = $this->findEmailNotification($email, $messageId);
        $notificationId = $notification ? (int)$notification->notification_id : null;

        if (Schema::hasTable('restoline_email_provider_events')) {
            DB::table('restoline_email_provider_events')->insert([
                'provider' => $provider,
                'event_type' => $event !== '' ? $event : 'unknown',
                'email' => $email !== '' ? $email : null,
                'provider_message_id' => $messageId !== '' ? $messageId : null,
                'reservation_email_notification_id' => $notificationId,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($notification && Schema::hasTable('restoline_reservation_email_notifications')) {
            DB::table('restoline_reservation_email_notifications')
                ->where('notification_id', $notificationId)
                ->update([
                    'provider_message_id' => $messageId !== '' ? $messageId : $notification->provider_message_id,
                    'email_status' => $this->normalizeSendLayerEventStatus($event),
                    'last_event_at' => now(),
                    'last_payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        }

        return $notificationId;
    }

    public function sendSmsFallbackForBouncedEmail(string $email, string $messageId, array $payload): bool
    {
        $notification = $this->findEmailNotification($email, $messageId);
        if (!$notification || $notification->sms_fallback_sent_at) {
            return false;
        }

        $reservation = Reservation::query()->find((int)$notification->reservation_id);
        if (!$reservation || !$reservation->telephone) {
            Log::warning('Bounce SenderLayer sans téléphone pour fallback SMS.', [
                'notification_id' => $notification->notification_id,
                'reservation_id' => $notification->reservation_id ?? null,
            ]);

            return false;
        }

        $this->sendStatusSms($reservation, (int)$notification->status_id);

        if (Schema::hasTable('restoline_reservation_email_notifications')) {
            DB::table('restoline_reservation_email_notifications')
                ->where('notification_id', (int)$notification->notification_id)
                ->update([
                    'email_status' => 'bounced',
                    'sms_fallback_sent_at' => now(),
                    'last_payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        }

        Log::info('SMS fallback envoyé après bounce SenderLayer.', [
            'notification_id' => $notification->notification_id,
            'reservation_id' => $reservation->reservation_id,
        ]);

        return true;
    }

    public function markStalePendingEmailNotificationsAsAssumedSent(int $afterHours = 3): int
    {
        if (!Schema::hasTable('restoline_reservation_email_notifications')) {
            return 0;
        }

        $afterHours = max(1, $afterHours);

        return DB::table('restoline_reservation_email_notifications')
            ->where('message_type', 'client_status')
            ->where('email_status', 'pending')
            ->whereNull('sms_fallback_sent_at')
            ->where('created_at', '<=', now()->subHours($afterHours))
            ->update([
                'email_status' => 'sent_assumed',
                'last_event_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function findEmailNotification(string $email, string $messageId): ?object
    {
        if (!Schema::hasTable('restoline_reservation_email_notifications')) {
            return null;
        }

        $messageId = trim($messageId);
        $email = strtolower(trim($email));

        if ($messageId !== '') {
            $notification = DB::table('restoline_reservation_email_notifications')
                ->where('message_type', 'client_status')
                ->where('provider_message_id', $messageId)
                ->orderByDesc('notification_id')
                ->first();

            if ($notification) {
                return $notification;
            }
        }

        if ($email === '') {
            return null;
        }

        return DB::table('restoline_reservation_email_notifications')
            ->where('message_type', 'client_status')
            ->where('email', $email)
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByRaw("provider_message_id = ? desc", [$messageId])
            ->orderByDesc('notification_id')
            ->first();
    }

    private function normalizeSendLayerEventStatus(string $event): string
    {
        return match (strtolower(trim($event))) {
            'delivered', 'delivery' => 'delivered',
            'opened', 'open' => 'opened',
            'clicked', 'click' => 'clicked',
            'sent', 'send', 'accepted', 'processed' => 'sent',
            'deferred', 'soft_bounced' => 'pending',
            'bounce', 'bounced', 'failed', 'dropped', 'rejected' => 'bounced',
            'complaint', 'complained', 'unsubscribe', 'unsubscribed' => 'attention',
            default => 'event',
        };
    }

    private function clientEmailHtml(
        string $logoUrl,
        string $heading,
        string $intro,
        string $name,
        int $guests,
        string $guestLabel,
        string $date,
        string $time,
        string $manageUrl,
        string $directionsUrl,
        bool $confirmed,
    ): string {
        $managementButton = $confirmed
            ? $this->button($manageUrl, 'Modifier ou annuler ma réservation', '#215b45')
            : '';

        return $this->emailFrame($logoUrl, $heading, '
            <p style="margin:0 0 22px;color:#4b5563;line-height:1.7;">Bonjour '.e($name).',</p>
            <p style="margin:0 0 24px;color:#4b5563;line-height:1.7;">'.e($intro).'</p>
            <div style="margin:0 0 26px;padding:22px;background:#f4f6f3;border-left:4px solid #215b45;">
                <strong style="display:block;font-size:20px;color:#17211c;">'.e((string)$guests).' '.e($guestLabel).'</strong>
                <span style="display:block;margin-top:8px;color:#4b5563;">'.e($date).' à '.e($time).'</span>
            </div>
            '.$managementButton.'
            <div style="margin-top:30px;padding-top:24px;border-top:1px solid #e5e7eb;">
                <strong style="color:#17211c;">'.e($this->restaurantName()).'</strong>
                <p style="margin:8px 0;color:#4b5563;line-height:1.6;">'.e($this->restaurantAddress()).'</p>
                <p style="margin:12px 0;color:#4b5563;line-height:1.6;">Erreur ou remarque ? Contactez-nous au '.e($this->restaurantPhoneLabel()).'.</p>
                '.$this->button($directionsUrl, 'Itinéraire', '#b45309').'
                '.$this->button('tel:'.$this->restaurantPhone(), 'Appeler le restaurant', '#1f2937').'
            </div>
        ');
    }

    private function privateManageUrl(Reservation $reservation): string
    {
        if (!$reservation->hash) {
            return $this->publicManageUrl();
        }

        return URL::temporarySignedRoute(
            'restoline.manage.direct',
            now()->addDays(30),
            ['hash' => $reservation->hash],
        );
    }

    private function restaurantEmailHtml(
        string $logoUrl,
        int $reservationId,
        string $name,
        string $phone,
        string $email,
        int $guests,
        string $guestLabel,
        string $date,
        string $time,
        string $dashboardUrl,
        string $heading = 'Nouvelle réservation confirmée',
    ): string {
        return $this->emailFrame($logoUrl, $heading, '
            <p style="margin:0 0 24px;color:#4b5563;line-height:1.7;">La réservation #'.e((string)$reservationId).' est confirmée et enregistrée dans le tableau de bord '.e($this->restaurantName()).'.</p>
            <div style="margin:0 0 26px;padding:22px;background:#f4f6f3;border-left:4px solid #215b45;line-height:1.8;">
                <strong style="color:#17211c;">'.e($name).'</strong><br>
                '.e((string)$guests).' '.e($guestLabel).' · '.e($date).' à '.e($time).'<br>
                Téléphone : '.e($phone).'<br>
                Email : '.e($email !== '' ? $email : 'Non renseigné').'
            </div>
            '.$this->button($dashboardUrl, 'Ouvrir le tableau de bord', '#215b45').'
            '.$this->button('tel:'.$phone, 'Appeler le client', '#1f2937').'
        ');
    }

    private function emailFrame(string $logoUrl, string $heading, string $content): string
    {
        return '<!doctype html><html lang="fr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
                <tr><td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;">
                        <tr><td style="padding:28px 34px 16px;text-align:center;">
                            <img src="'.e($logoUrl).'" width="86" height="86" alt="'.e($this->restaurantName()).'" style="display:inline-block;border-radius:50%;object-fit:cover;">
                            <h1 style="margin:20px 0 8px;color:#17211c;font-family:Georgia,serif;font-size:30px;font-weight:500;">'.e($heading).'</h1>
                        </td></tr>
                        <tr><td style="padding:18px 34px 36px;">'.$content.'</td></tr>
                    </table>
                </td></tr>
            </table>
        </body></html>';
    }

    private function restaurantNotificationEmail(): string
    {
        return (string)env('RESTAURANT_NOTIFICATION_EMAIL', config('mail.from.address'));
    }

    private function sendEmailViaResend(string $email, string $name, string $subject, string $html): string
    {
        $apiKey = (string)\App\Services\Restoline\PrivateEnvironment::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            throw new RuntimeException('Clé API Resend absente.');
        }

        $fromEmail = (string)\App\Services\Restoline\PrivateEnvironment::get(
            'RESEND_FROM_EMAIL',
            config('mail.from.address'),
        );
        $fromName = (string)\App\Services\Restoline\PrivateEnvironment::get(
            'RESEND_FROM_NAME',
            config('mail.from.name', $this->restaurantName()),
        );

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post('https://api.resend.com/emails', [
                'from' => $fromName.' <'.$fromEmail.'>',
                'to' => [$name !== '' ? $name.' <'.$email.'>' : $email],
                'subject' => $subject,
                'html' => $html,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Resend HTTP '.$response->status().' '.$response->body());
        }

        $messageId = (string)($response->json('id') ?? '');
        if ($messageId === '') {
            throw new RuntimeException('Resend accepté sans identifiant message.');
        }

        return $messageId;
    }

    private function usesSmtp(): bool
    {
        $mailer = (string)config('mail.default');
        $host = (string)config('mail.mailers.smtp.host');

        return $mailer === 'smtp' && $host !== '' && $host !== 'smtp.mailgun.org';
    }

    private function sendEmailViaSmtp(string $email, string $name, string $subject, string $html): string
    {
        Mail::mailer('smtp')->html($html, function ($message) use ($email, $name, $subject): void {
            $message->to($email, $name !== '' ? $name : null)->subject($subject);
            $replyTo = (string)config('restoline.restaurant.notification_email', '');
            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $message->replyTo($replyTo, (string)config('restoline.restaurant.name'));
            }
        });

        return 'smtp';
    }

    private function sendEmailWithFallback(string $email, string $name, string $subject, string $html): string
    {
        if ($this->usesSmtp()) {
            return $this->sendEmailViaSmtp($email, $name, $subject, $html);
        }

        return $this->sendEmailViaResend($email, $name, $subject, $html);
    }

    private function publicLogoUrl(): string
    {
        return url('/');
    }

    private function restaurantName(): string
    {
        return (string)config('restoline.restaurant.name', 'La Grande Muraille de Chine');
    }

    private function restaurantAddress(): string
    {
        return (string)config('restoline.restaurant.address', 'Avenue Reine Astrid 98, 4900 Spa');
    }

    private function restaurantPhone(): string
    {
        return (string)config('restoline.restaurant.phone', '+3287220888');
    }

    private function restaurantPhoneLabel(): string
    {
        return (string)config('restoline.restaurant.phone_display', '087 22 08 88');
    }

    private function publicManageUrl(): string
    {
        return rtrim((string)config('app.url'), '/').'/'.ltrim((string)config('restoline.restaurant.manage_reservation_path'), '/');
    }

    private function dashboardUrl(): string
    {
        return rtrim((string)config('app.url'), '/').'/admin/reservations';
    }

    private function directionsUrl(): string
    {
        $destination = rawurlencode((string)config('restoline.restaurant.address', 'Avenue Reine Astrid 98, 4900 Spa'));

        return 'https://www.google.com/maps/dir/?api=1&destination='.$destination;
    }

    private function button(string $url, string $label, string $color): string
    {
        return '<a href="'.e($url).'" style="display:inline-block;margin:5px 8px 5px 0;padding:13px 18px;background:'.e($color).';color:#ffffff;text-decoration:none;font-weight:700;">'.e($label).'</a>';
    }

    private function cacheKey(Reservation $reservation): string
    {
        return 'restoline:otp:reservation:'.$reservation->reservation_id;
    }

    private function verifiedKey(Reservation $reservation): string
    {
        return 'restoline:otp:verified:'.$reservation->reservation_id;
    }

    private function customerName(Reservation $reservation): string
    {
        return trim((string)$reservation->first_name.' '.(string)$reservation->last_name) ?: 'Client';
    }

}
