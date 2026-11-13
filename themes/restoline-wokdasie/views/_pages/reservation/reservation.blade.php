---
title: "Réserver une table | La Grande Muraille de Chine"
description: "Réservez votre table au La Grande Muraille de Chine en quelques étapes."
layout: default
permalink: ':location/reservation'
bodyClass: page-booking

'[igniter-orange::booking]': []
---
@php
    $turnstileSiteKey = app(\Restoline\Content\Services\TurnstileService::class)->siteKey();

    // Garde-fou : si la date demandee n'ouvre aucun creneau (fermeture, complet, date hors fenetre),
    // on affiche un message plutot que de laisser le composant calculer des creneaux vides.
    $onlineBookingEnabled = \Restoline\Content\Support\OnlineBooking::isEnabled();
    $bookingUnavailableMessage = null;
    $requestedDate = (string)request('date');

    if ($requestedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
        try {
            $bookingLocation = \Igniter\Local\Facades\Location::current();
            if (!$bookingLocation instanceof \Igniter\Local\Models\Location) {
                $bookingLocation = \Igniter\Local\Models\Location::query()->where('location_status', 1)->first();
            }

            $bookingManager = resolve(\Igniter\Reservation\Classes\BookingManager::class);
            $bookingManager->useLocation($bookingLocation);

            $slots = $bookingManager->makeTimeSlots(\Carbon\Carbon::parse($requestedDate)->startOfDay());
            if (count($slots) === 0) {
                $bookingUnavailableMessage = lang('restoline.content::frontend.booking.unavailable');
            }
        } catch (\Throwable $exception) {
            $bookingUnavailableMessage = lang('restoline.content::frontend.booking.unavailable_other');
        }
    }
@endphp
<section class="wok-booking-page">
    <div class="wok-booking-heading">
        <a href="{{ url('/') }}">← @lang('restoline.content::frontend.booking.back')</a>
        <p class="wok-eyebrow">La Grande Muraille de Chine</p>
        <h1>@lang('restoline.content::frontend.booking.title')</h1>
        <p class="wok-booking-note">@lang('restoline.content::frontend.booking.note')</p>
        <div class="wok-booking-actions">
            <a class="wok-button wok-button-dark" href="{{ url('/reservation/gerer') }}">@lang('restoline.content::frontend.nav.manage')</a>
            <p>@lang('restoline.content::frontend.booking.already')</p>
        </div>
    </div>
    @if($turnstileSiteKey)
        <div class="wok-turnstile-booking">
            <div id="wok-booking-turnstile"></div>
        </div>
        <script>
            (() => {
                const siteKey = @json($turnstileSiteKey);
                const renderTurnstile = () => {
                    const target = document.getElementById('wok-booking-turnstile');
                    const livewireRoot = document.querySelector('.wok-booking-native [wire\\:id]');
                    if (!target || !livewireRoot || !window.turnstile || !window.Livewire) {
                        window.setTimeout(renderTurnstile, 250);
                        return;
                    }

                    if (target.dataset.rendered) return;
                    target.dataset.rendered = 'true';

                    window.turnstile.render(target, {
                        sitekey: siteKey,
                        callback: (token) => {
                            window.Livewire.find(livewireRoot.getAttribute('wire:id'))?.set('turnstileToken', token);
                        },
                        'expired-callback': () => {
                            window.Livewire.find(livewireRoot.getAttribute('wire:id'))?.set('turnstileToken', null);
                        },
                    });
                };

                renderTurnstile();
            })();
        </script>
    @endif
    <div class="wok-booking-native">
        @if(!$onlineBookingEnabled)
            <div class="wok-otp-card">
                <strong>@lang('restoline.content::frontend.booking.closed_title')</strong>
                <p>@lang('restoline.content::frontend.booking.closed_text')</p>
                <p><a class="wok-button wok-button-dark" href="tel:+3287220888">087 22 08 88</a></p>
                <p>@lang('restoline.content::frontend.booking.closed_manage')</p>
            </div>
        @elseif($bookingUnavailableMessage)
            <div class="wok-otp-card">
                <p>{{ $bookingUnavailableMessage }}</p>
                <p><a class="wok-button wok-button-dark" href="{{ url('/grande-muraille/reservation') }}">@lang('restoline.content::frontend.booking.other_date')</a></p>
            </div>
        @else
            <livewire:igniter-orange::booking/>
        @endif
    </div>
</section>
