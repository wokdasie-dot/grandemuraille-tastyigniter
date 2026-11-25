@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
@endphp
<section class="gm-section gm-reservation" id="reservation">
    <div class="gm-wrap reservation-grid">
        <div class="gm-reservation__text" data-reveal="left">
            <p class="gm-kicker gm-kicker--light">{{ $gm::line('resa.kicker') }}</p>
            <h2>{{ $gm::line('resa.title') }} <em>{{ $gm::line('resa.title.em') }}</em></h2>
            <p>{{ $gm::line('resa.lead') }}</p>
            <p>{{ $gm::line('resa.groups') }}</p>
            <ul class="gm-practical" aria-label="{{ $gm::line('nav.infos') }}">
                <li><span>{{ $gm::line('resa.midi') }}</span>{{ $gm::line('resa.midi.val') }}</li>
                <li><span>{{ $gm::line('resa.soir') }}</span>{{ $gm::line('resa.soir.val') }}</li>
                <li><span>{{ $gm::line('resa.closed') }}</span>{{ $gm::line('resa.closed.val') }}</li>
            </ul>
        </div>
        <div class="gm-form gm-booking gm-booking--phone" data-reveal="right">
            <p class="gm-booking__phone-lead">{{ $gm::line('resa.lead') }}</p>
            <a class="gm-btn gm-btn--gold gm-btn--phone" href="tel:+3287220888" data-scrollscale data-reveal="pop">{{ $gm::line('resa.cta') }}</a>
        </div>
    </div>
</section>
