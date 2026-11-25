@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
@endphp
<div class="gm-booking gm-booking--phone">
    <p class="gm-booking__phone-lead">{{ $gm::line('resa.lead') }}</p>
    <a class="gm-btn gm-btn--gold gm-btn--phone" href="tel:+3287220888">{{ $gm::line('nav.reserver') }}</a>
</div>
