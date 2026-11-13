@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $home = url('/');
@endphp
<a class="gm-skip" href="{{ $home }}#reservation">{{ $gm::line('skip') }}</a>
<header class="gm-nav" id="nav">
    <a class="gm-brand" href="{{ $home }}#accueil" aria-label="{{ $gm::line('brand.aria') }}">
        <span class="gm-seal" lang="zh" aria-hidden="true">长城</span>
        <span class="gm-brand__text"><b>La Grande Muraille</b><small>de Chine · Spa</small></span>
    </a>
    <nav class="gm-nav__links" aria-label="{{ $gm::line('nav.main') }}">
        <a href="{{ $home }}#maison">{{ $gm::line('nav.maison') }}</a>
        <a href="{{ $home }}#galerie">{{ $gm::line('nav.galerie') }}</a>
        <a href="{{ $home }}#buffet">{{ $gm::line('nav.buffet') }}</a>
        <a href="{{ $home }}#carte">{{ $gm::line('nav.menus') }}</a>
        <a href="{{ $home }}#infos">{{ $gm::line('nav.infos') }}</a>
    </nav>
    <div class="gm-nav__end">
        <nav class="gm-langs gm-langs--header" aria-label="{{ $gm::line('lang.label') }}">
            @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
                @if (app()->getLocale() === $code)
                    <a href="{{ url('/locale/'.$code) }}" aria-current="true" lang="{{ $code }}">{{ $label }}</a>
                @else
                    <a href="{{ url('/locale/'.$code) }}" lang="{{ $code }}">{{ $label }}</a>
                @endif
            @endforeach
        </nav>
        <a class="gm-btn gm-btn--gold gm-btn--sm" href="{{ $home }}#reservation" data-scrollscale data-reveal="pop">{{ $gm::line('nav.reserver') }}</a>
        <button type="button" class="gm-nav__toggle" id="nav-toggle" aria-expanded="false" aria-controls="nav-drawer" aria-label="{{ $gm::line('menu.open') }}">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
<div class="gm-nav__drawer" id="nav-drawer" hidden>
    <nav aria-label="{{ $gm::line('nav.main') }}">
        <a href="{{ $home }}#maison">{{ $gm::line('nav.maison') }}</a>
        <a href="{{ $home }}#galerie">{{ $gm::line('nav.galerie') }}</a>
        <a href="{{ $home }}#buffet">{{ $gm::line('nav.buffet') }}</a>
        <a href="{{ $home }}#carte">{{ $gm::line('nav.menus') }}</a>
        <a href="{{ $home }}#infos">{{ $gm::line('nav.infos') }}</a>
        <a class="gm-btn gm-btn--gold" href="{{ $home }}#reservation">{{ $gm::line('nav.reserver') }}</a>
    </nav>
    <nav class="gm-langs gm-langs--drawer" aria-label="{{ $gm::line('lang.label') }}">
        @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
            @if (app()->getLocale() === $code)
                <a href="{{ url('/locale/'.$code) }}" aria-current="true" lang="{{ $code }}">{{ $label }}</a>
            @else
                <a href="{{ url('/locale/'.$code) }}" lang="{{ $code }}">{{ $label }}</a>
            @endif
        @endforeach
    </nav>
</div>
