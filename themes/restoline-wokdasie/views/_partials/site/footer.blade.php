@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $home = url('/');
@endphp
<footer class="gm-footer">
    <div class="gm-wrap gm-footer__top">
        <div class="gm-footer__brand">
            <span class="gm-seal" lang="zh" aria-hidden="true">长城</span>
            <span>La Grande Muraille de Chine<small>{{ $gm::line('footer.tag') }}</small></span>
        </div>
        <nav class="gm-footer__nav" aria-label="{{ $gm::line('nav.main') }}">
            <a href="{{ $home }}#carte">{{ $gm::line('nav.menus') }}</a>
            <a href="{{ $home }}#buffet">{{ $gm::line('nav.buffet') }}</a>
            <a href="{{ $home }}#infos">{{ $gm::line('footer.access') }}</a>
            <div class="gm-langs gm-langs--footer" role="navigation" aria-label="{{ $gm::line('lang.label') }}">
                @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
                    @if (app()->getLocale() === $code)
                        <a href="{{ url('/locale/'.$code) }}" aria-current="true" lang="{{ $code }}">{{ $label }}</a>
                    @else
                        <a href="{{ url('/locale/'.$code) }}" lang="{{ $code }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            <a class="gm-btn gm-btn--gold gm-btn--sm" href="{{ $home }}#reservation" data-scrollscale data-reveal="pop">{{ $gm::line('nav.reserver') }}</a>
        </nav>
    </div>
    <div class="gm-wrap gm-footer__grid">
        <span>Avenue Reine Astrid 98 · 4900 Spa · <a href="tel:+3287220888">087 22 08 88</a></span>
        <span>SRL JIN-ZHOU · TVA BE 0812011942</span>
        <span><a href="{{ url('/confidentialite') }}">@lang('restoline.content::frontend.footer.privacy')</a></span>
        <button type="button" id="motion-toggle" aria-pressed="false" hidden>{{ $gm::line('motion.pause') }}</button>
    </div>
</footer>
