@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $turnstileSiteKey = app(\Restoline\Content\Services\TurnstileService::class)->siteKey();
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
<section class="gm-section gm-reservation" id="reservation">
    <div class="gm-wrap reservation-grid">
        <div class="gm-reservation__text" data-reveal="left">
            <p class="gm-kicker gm-kicker--light">{{ $gm::line('resa.kicker') }}</p>
            <h2>{{ $gm::line('resa.title') }} <em>{{ $gm::line('resa.title.em') }}</em></h2>
            <p>{{ $gm::line('resa.lead') }}</p>
            <p>{{ $gm::line('resa.question') }} <a href="tel:+3287220888">087 22 08 88</a></p>
            <p>{{ $gm::line('resa.groups') }}</p>
            <p class="notice">{{ $gm::line('resa.notice') }}</p>
            <ul class="gm-practical" aria-label="{{ $gm::line('nav.infos') }}">
                <li><span>{{ $gm::line('resa.midi') }}</span>{{ $gm::line('resa.midi.val') }}</li>
                <li><span>{{ $gm::line('resa.soir') }}</span>{{ $gm::line('resa.soir.val') }}</li>
                <li><span>{{ $gm::line('resa.closed') }}</span>{{ $gm::line('resa.closed.val') }}</li>
            </ul>
            <p class="gm-manage">
                <a href="{{ url('/reservation/gerer') }}">@lang('restoline.content::frontend.nav.manage')</a>
            </p>
        </div>
        <div class="gm-form gm-booking wok-booking-native" data-reveal="right">
            @if($turnstileSiteKey)
                <div id="wok-booking-turnstile"></div>
                <script>
                    (() => {
                        const siteKey = @json($turnstileSiteKey);
                        const renderTurnstile = () => {
                            const target = document.getElementById('wok-booking-turnstile');
                            const livewireRoot = document.querySelector('.gm-booking [wire\\:id]');
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
            @if(!$onlineBookingEnabled)
                <p><strong>@lang('restoline.content::frontend.booking.closed_title')</strong></p>
                <p>@lang('restoline.content::frontend.booking.closed_text')</p>
                <p><a class="gm-btn gm-btn--red" href="tel:+3287220888">087 22 08 88</a></p>
                <p>@lang('restoline.content::frontend.booking.closed_manage')</p>
            @elseif($bookingUnavailableMessage)
                <p>{{ $bookingUnavailableMessage }}</p>
                <p><a class="gm-btn gm-btn--red" href="{{ url('/grande-muraille/reservation') }}">@lang('restoline.content::frontend.booking.other_date')</a></p>
            @else
                <livewire:igniter-orange::booking/>
            @endif
        </div>
    </div>
</section>
