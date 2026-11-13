---
title: "Confirmation de votre réservation | La Grande Muraille de Chine"
description: "Votre demande de réservation au La Grande Muraille de Chine a bien été enregistrée."
layout: default
permalink: ':location/reservation/success/:hash?'
bodyClass: page-booking
---
@php
    $hash = (string)request()->route('hash');
    $reservation = app(\Restoline\Content\Services\ReservationOtpService::class)->findReservation($hash);
    $confirmationError = null;

    if ($reservation) {
        try {
            app(\Restoline\Content\Services\ReservationOtpService::class)->ensureConfirmedWithoutCodeIfAllowed($reservation);
        } catch (\RuntimeException $exception) {
            $confirmationError = $exception->getMessage();
        }
    }
@endphp

<section class="wok-booking-page">
    <div class="wok-booking-heading">
        <a href="{{ url('/') }}">← Retour à l'accueil</a>
        <p class="wok-eyebrow">La Grande Muraille de Chine</p>
        <h1>{{ ($reservation && (int)$reservation->guest_num >= (int)config('restoline.reservation.phone_confirm_from', 12)) ? 'Demande reçue' : 'Réservation confirmée' }}</h1>
    </div>

    @if(!$reservation)
        <div class="wok-otp-card">
            <p>Cette réservation est introuvable. Contactez le restaurant au <a href="tel:+3287220888">087 22 08 88</a>.</p>
        </div>
    @elseif($confirmationError)
        <div class="wok-otp-card">
            <p>{{ $confirmationError }}</p>
            <p>Pour toute question, contactez le restaurant au <a href="tel:+3287220888">087 22 08 88</a>.</p>
        </div>
    @else
        <div class="wok-otp-card wok-otp-success">
            @if((int)$reservation->guest_num >= (int)config('restoline.reservation.phone_confirm_from', 12))
                <strong>Votre demande est reçue.</strong>
                <p>À partir de 12 personnes, le restaurant vous rappelle pour confirmer. Cette réservation n'est pas encore confirmée.</p>
            @else
                <strong>Votre réservation est confirmée.</strong>
                <p>Une confirmation vient de vous être envoyée par e-mail si une adresse a été renseignée.</p>
            @endif
            <p>Pour toute question, contactez le restaurant au <a href="tel:+3287220888">087 22 08 88</a>.</p>
            <dl class="wok-otp-summary">
                <div><dt>Nom</dt><dd>{{ $reservation->first_name }} {{ $reservation->last_name }}</dd></div>
                <div><dt>Personnes</dt><dd>{{ $reservation->guest_num }}</dd></div>
                <div><dt>Date</dt><dd>{{ $reservation->reserve_date->format('d/m/Y') }}</dd></div>
                <div><dt>Heure</dt><dd>{{ substr((string)$reservation->reserve_time, 0, 5) }}</dd></div>
            </dl>
        </div>
    @endif
</section>
