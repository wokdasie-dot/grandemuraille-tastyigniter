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
        <a href="{{ url('/') }}">← @lang('restoline.content::frontend.booking.back')</a>
        <p class="wok-eyebrow">La Grande Muraille de Chine</p>
        <h1>{{ ($reservation && (int)$reservation->guest_num >= (int)config('restoline.reservation.phone_confirm_from', 12)) ? lang('restoline.content::frontend.success.received_title') : lang('restoline.content::frontend.success.confirmed_title') }}</h1>
    </div>

    @if(!$reservation)
        <div class="wok-otp-card">
            <p>@lang('restoline.content::frontend.success.missing', ['phone' => '087 22 08 88'])</p>
        </div>
    @elseif($confirmationError)
        <div class="wok-otp-card">
            <p>{{ $confirmationError }}</p>
            <p>@lang('restoline.content::frontend.success.contact', ['phone' => '087 22 08 88'])</p>
        </div>
    @else
        <div class="wok-otp-card wok-otp-success">
            @if((int)$reservation->guest_num >= (int)config('restoline.reservation.phone_confirm_from', 12))
                <strong>@lang('restoline.content::frontend.success.received')</strong>
                <p>@lang('restoline.content::frontend.success.received_text')</p>
            @else
                <strong>@lang('restoline.content::frontend.success.confirmed')</strong>
                <p>@lang('restoline.content::frontend.success.confirmed_email')</p>
            @endif
            <p>@lang('restoline.content::frontend.success.contact', ['phone' => '087 22 08 88'])</p>
            <dl class="wok-otp-summary">
                <div><dt>@lang('restoline.content::frontend.success.name')</dt><dd>{{ $reservation->first_name }} {{ $reservation->last_name }}</dd></div>
                <div><dt>@lang('restoline.content::frontend.success.people')</dt><dd>{{ $reservation->guest_num }}</dd></div>
                <div><dt>@lang('restoline.content::frontend.success.date')</dt><dd>{{ $reservation->reserve_date->format('d/m/Y') }}</dd></div>
                <div><dt>@lang('restoline.content::frontend.success.time')</dt><dd>{{ substr((string)$reservation->reserve_time, 0, 5) }}</dd></div>
            </dl>
        </div>
    @endif
</section>
