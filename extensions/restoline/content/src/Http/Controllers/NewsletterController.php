<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Restoline\Content\Models\NewsletterSubscriber;
use Restoline\Content\Services\TurnstileService;

class NewsletterController
{
    public function subscribe(Request $request, TurnstileService $turnstile): RedirectResponse
    {
        if (trim((string)$request->input('website')) !== '') {
            return back()->with('newsletter_status', 'Votre inscription est enregistrée.');
        }

        if (!$turnstile->verifyRequest($request)) {
            return back()
                ->withErrors(['newsletter' => 'La vérification anti-spam a échoué. Réessayez.'])
                ->withInput();
        }

        $data = Validator::make($request->all(), [
            'first_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'birthday_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'marketing_channel' => ['required', 'in:email,sms'],
            'privacy_consent' => ['accepted'],
        ], [
            'birthday_day.integer' => 'Choisissez un jour anniversaire valide.',
            'birthday_month.integer' => 'Choisissez un mois anniversaire valide.',
            'marketing_channel.required' => 'Choisissez email ou SMS pour recevoir les offres.',
            'privacy_consent.accepted' => 'Vous devez accepter la politique de confidentialité.',
        ])->after(function($validator) use ($request): void {
            $day = $request->input('birthday_day');
            $month = $request->input('birthday_month');

            if (($day === null || $day === '') && ($month === null || $month === '')) {
                return;
            }

            if ($day === null || $day === '' || $month === null || $month === '') {
                $validator->errors()->add('birthday_day', 'Choisissez le jour et le mois de votre anniversaire.');
                return;
            }

            if (!checkdate((int)$month, (int)$day, 2000)) {
                $validator->errors()->add('birthday_day', 'Choisissez une date anniversaire valide.');
            }
        })->validate();

        $emailConsent = $data['marketing_channel'] === 'email';
        $smsConsent = $data['marketing_channel'] === 'sms';

        if ($emailConsent && empty($data['email'])) {
            return back()->withErrors(['email' => 'L’email est obligatoire pour recevoir les offres par email.'])->withInput();
        }

        if ($smsConsent && empty($data['phone'])) {
            return back()->withErrors(['phone' => 'Le téléphone est obligatoire pour recevoir les offres par SMS.'])->withInput();
        }

        if ($limited = $this->enforceRateLimit($request, 'subscribe', (string)($data['email'] ?? ''), (string)($data['phone'] ?? ''))) {
            return $limited;
        }

        $day = ($data['birthday_day'] ?? '') !== '' ? (int)$data['birthday_day'] : null;
        $month = ($data['birthday_month'] ?? '') !== '' ? (int)$data['birthday_month'] : null;
        $subscriber = $this->findOrCreateSubscriber((string)($data['email'] ?? ''), (string)($data['phone'] ?? ''));
        $subscriber->fill([
            'first_name' => $data['first_name'] ?? null,
            'email' => $data['email'] ?? $subscriber->email,
            'phone' => $data['phone'] ?? $subscriber->phone,
            'birthday_day' => $day,
            'birthday_month' => $month,
            'email_consent' => $emailConsent,
            'sms_consent' => $smsConsent,
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => substr((string)$request->userAgent(), 0, 1000),
            'consent_source' => 'site_web',
            'consent_url' => $request->fullUrl(),
            'consent_version' => 'newsletter_wokdasie_2026_06',
            'consent_text' => $this->consentText($emailConsent, $smsConsent),
            'status' => true,
            'unsubscribed_at' => null,
        ]);
        $subscriber->save();

        return back()->with('newsletter_status', 'Votre inscription est enregistrée.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $subscriber->delete();

        return redirect('/')->with('newsletter_status', 'Vous êtes désinscrit des offres La Grande Muraille de Chine.');
    }

    public function unsubscribeByContact(Request $request, TurnstileService $turnstile): RedirectResponse
    {
        if (!$turnstile->verifyRequest($request)) {
            return back()
                ->withErrors(['newsletter' => 'La vérification anti-spam a échoué. Réessayez.'])
                ->withInput();
        }

        $data = Validator::make($request->all(), [
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Saisissez votre email ou votre téléphone.',
        ])->validate();

        $identifier = trim((string)$data['identifier']);
        $normalizedPhone = preg_replace('/[^\d+]/', '', $identifier);

        if ($limited = $this->enforceRateLimit($request, 'unsubscribe', $identifier, $normalizedPhone)) {
            return $limited;
        }

        NewsletterSubscriber::query()
            ->active()
            ->where(function($query) use ($identifier, $normalizedPhone): void {
                $query->whereRaw('LOWER(email) = ?', [mb_strtolower($identifier)]);
                if ($normalizedPhone !== '') {
                    $query->orWhere('phone', $identifier)
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '.', ''), '-', ''), '/', '') = ?", [$normalizedPhone]);
                }
            })
            ->get()
            ->each(function(NewsletterSubscriber $subscriber): void {
                $subscriber->delete();
            });

        return back()->with('newsletter_unsubscribe_status', 'Votre demande de désinscription est prise en compte.');
    }

    private function findOrCreateSubscriber(string $email, string $phone): NewsletterSubscriber
    {
        $query = NewsletterSubscriber::query();
        if ($email !== '') {
            $query->where('email', $email);
        }
        if ($phone !== '') {
            $query->{$email !== '' ? 'orWhere' : 'where'}('phone', $phone);
        }

        return $query->first() ?: new NewsletterSubscriber();
    }

    private function enforceRateLimit(Request $request, string $action, string $email, string $phone): ?RedirectResponse
    {
        $email = mb_strtolower(trim($email));
        $phone = $this->normalizePhone($phone);

        $limits = [
            ['newsletter:'.$action.':ip:'.sha1((string)$request->ip()), 8, 3600],
        ];

        if ($email !== '') {
            $limits[] = ['newsletter:'.$action.':email:'.sha1($email), 3, 86400];
        }

        if ($phone !== '') {
            $limits[] = ['newsletter:'.$action.':phone:'.sha1($phone), 3, 86400];
        }

        foreach ($limits as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return back()
                    ->withErrors(['newsletter' => 'Trop de demandes ont été envoyées. Réessayez plus tard.'])
                    ->withInput();
            }
        }

        foreach ($limits as [$key, $max, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    private function consentText(bool $emailConsent, bool $smsConsent): string
    {
        $channel = $emailConsent ? 'email' : ($smsConsent ? 'SMS' : 'aucun canal');

        return "Inscription volontaire aux offres La Grande Muraille de Chine par {$channel}. "
            ."Le client choisit un seul canal, accepte la politique de confidentialite, "
            ."et peut se desinscrire a tout moment via la page de desinscription.";
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', trim($phone)) ?: '';
    }
}
