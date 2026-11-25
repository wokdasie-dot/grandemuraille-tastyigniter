---
title: "Carte à la carte | La Grande Muraille de Chine"
description: "La carte à la carte de La Grande Muraille de Chine, à Spa."
layout: default
permalink: /carte
bodyClass: page-carte
---
@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $img = asset('vendor/restoline-wokdasie/images/gm');
    $categories = array_values(array_filter(
        $gm::carte(),
        static fn (array $category): bool => $category['items'] !== [],
    ));
    $promo = $gm::cartePromo();
    $photos = [
        'potages' => ['potage.jpg', 'gal.potages', 675, 900],
        'entree-chaude' => ['plat-rouleaux.jpg', 'gal.rouleaux', 675, 900],
        'entree-vapeur' => ['dim-sum.jpg', 'gal.bouchees', 720, 480],
        'canard' => ['canard-laque.webp', 'hero.duck', 560, 560],
        'boeuf' => ['plat-cajou-boeuf.jpg', 'gal.partager', 720, 490],
        'poisson' => ['plat-crevettes.jpg', 'gal.specialites', 675, 900],
        'specialites' => ['plat-assortiment.jpg', 'gal.assortiment', 720, 682],
    ];
    $strip = [
        ['dim-sum.jpg', 'gal.bouchees', 720, 480],
        ['canard-laque.webp', 'hero.duck', 560, 560],
        ['potage.jpg', 'gal.potages', 675, 900],
        ['plat-rouleaux.jpg', 'gal.rouleaux', 675, 900],
        ['plat-crevettes.jpg', 'gal.specialites', 675, 900],
        ['plat-cajou-boeuf.jpg', 'gal.partager', 720, 490],
        ['plat-assortiment.jpg', 'gal.assortiment', 720, 682],
        ['salle.jpg', 'gal.salle', 720, 477],
    ];
@endphp

<section class="gm-carte-hero">
    <div class="gm-carte-hero__media" aria-hidden="true">
        <img class="gm-carte-hero__bg" src="{{ $img }}/hero-dimsum.jpg" alt="" width="1920" height="1251" decoding="async" fetchpriority="high">
        <figure class="gm-carte-hero__plate">
            <img src="{{ $img }}/canard-laque.webp" alt="" width="560" height="560">
        </figure>
    </div>
    <div class="gm-wrap gm-carte-hero__grid">
        <div class="gm-carte-hero__text">
            <p class="gm-kicker gm-kicker--light gm-load" style="--i:0">{{ $gm::line('carte.kicker') }}</p>
            <h1 class="gm-carte-hero__title">
                <span class="gm-line"><span class="gm-load" style="--i:1">{{ $gm::line('carte.title') }}</span></span>
                <span class="gm-line"><em class="gm-load" style="--i:2">{{ $gm::line('carte.title.em') }}</em></span>
            </h1>
            <p class="gm-carte-hero__lead gm-load" style="--i:3">{{ $gm::line('carte.lead') }}</p>
            @if($promo !== '')
                <p class="gm-carte-hero__promo gm-load" style="--i:4">{{ $promo }}</p>
            @endif
            <div class="gm-actions gm-load" style="--i:5">
                <a class="gm-btn gm-btn--gold" href="tel:+3287220888" data-scrollscale data-reveal="pop">{{ $gm::line('resa.cta') }}</a>
                <a class="gm-btn gm-btn--ghost" href="#alacarte" data-scrollscale data-reveal="pop">{{ $gm::line('carte.toc') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="gm-strip gm-strip--carte" aria-label="{{ $gm::line('gal.aria') }}">
    <div class="gm-wrap gm-strip__head" data-reveal="up">
        <p class="gm-kicker">{{ $gm::line('gal.kicker') }}</p>
        <div class="gm-arrows">
            <button type="button" class="gm-arrow" id="carte-prev" aria-label="{{ $gm::line('gal.prev') }}" data-reveal="pop"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M15 5l-7 7 7 7M8 12h13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            <button type="button" class="gm-arrow" id="carte-next" aria-label="{{ $gm::line('gal.next') }}" data-reveal="pop"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M9 5l7 7-7 7M16 12H3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
    </div>
    <div class="gm-strip__viewport" id="carte-strip" data-gm-strip data-prev="#carte-prev" data-next="#carte-next" role="region" aria-roledescription="carousel" aria-label="{{ $gm::line('gal.carousel') }}">
        <div class="gm-strip__track">
            @foreach ($strip as [$file, $key, $width, $height])
                <figure class="gm-strip__item">
                    <div class="gm-soft"><img src="{{ $img }}/{{ $file }}" alt="{{ $gm::line($key) }}" loading="lazy" width="{{ $width }}" height="{{ $height }}"></div>
                    <figcaption>{{ $gm::line($key) }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

<section class="gm-section gm-alacarte" id="alacarte">
    <div class="gm-wrap">
        @if($categories)
            <nav class="gm-alacarte__toc" aria-label="{{ $gm::line('carte.toc') }}">
                @foreach($categories as $category)
                    <a href="#carte-{{ $category['id'] }}">{{ $category['title'] }}</a>
                @endforeach
            </nav>
        @endif

        @foreach($categories as $category)
            @php $photo = $photos[$category['id']] ?? null; @endphp
            <article class="gm-carte-block{{ $category['items'] === [] ? ' gm-carte-block--empty' : '' }}{{ $photo ? ' gm-carte-block--photo' : '' }}" id="carte-{{ $category['id'] }}" data-reveal="up">
                @if($photo)
                    <figure class="gm-carte-block__photo gm-soft">
                        <img src="{{ $img }}/{{ $photo[0] }}" alt="{{ $gm::line($photo[1]) }}" loading="lazy" width="{{ $photo[2] }}" height="{{ $photo[3] }}">
                    </figure>
                @endif
                <div class="gm-carte-block__body">
                    <h2>{{ $category['title'] }}</h2>
                    @if($category['items'] !== [])
                        <ul class="gm-alacarte__list">
                            @foreach($category['items'] as $item)
                                <li>
                                    <span class="gm-alacarte__num">{{ $item['num'] }}</span>
                                    <span class="gm-alacarte__name">{{ $item['name'] }}</span>
                                    <span class="gm-alacarte__price">{{ $item['price_label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>
        @endforeach

        <div class="gm-alacarte__cta" data-reveal="up">
            <a class="gm-btn gm-btn--gold" href="tel:+3287220888" data-scrollscale data-reveal="pop">{{ $gm::line('resa.cta') }}</a>
            <a class="gm-link" href="{{ url('/') }}">{{ $gm::line('carte.back') }}</a>
        </div>
    </div>
</section>
