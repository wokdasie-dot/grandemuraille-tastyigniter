---
title: "La Grande Muraille de Chine · Restaurant chinois à Spa"
description: "Restaurant chinois à Spa : menus de la maison, spécialités du chef et grand buffet à volonté le week-end. Réservation en ligne."
layout: default
permalink: /
bodyClass: page-home

'[igniter-orange::booking]': []
---
@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $img = asset('vendor/restoline-wokdasie/images/gm');

    $buffetPricings = collect();
    if (class_exists(\Restoline\Content\Models\BuffetPricing::class)
        && \Illuminate\Support\Facades\Schema::hasTable('restoline_buffet_pricings')) {
        $buffetPricings = \Restoline\Content\Models\BuffetPricing::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get();
    }
    $priceWithout = optional($buffetPricings->firstWhere('category', 'weekend_dinner'))->adult_price ?? 31.80;
    $priceWith = optional($buffetPricings->firstWhere('category', 'weekend_drinks'))->adult_price ?? 48.80;

    $carteMenus = collect();
    try {
        if (class_exists(\Igniter\Cart\Models\Category::class)
            && \Illuminate\Support\Facades\Schema::hasTable('categories')
            && \Illuminate\Support\Facades\Schema::hasTable('menus')) {
            $menusCategory = \Igniter\Cart\Models\Category::query()
                ->where('permalink_slug', 'menus')
                ->where('status', true)
                ->first();
            if ($menusCategory) {
                $carteMenus = $menusCategory->menus()
                    ->where('menu_status', true)
                    ->orderBy('menu_priority')
                    ->orderBy('menu_name')
                    ->get();
            }
        }
    } catch (\Throwable $exception) {
        $carteMenus = collect();
    }
@endphp

<section class="gm-hero" id="accueil">
    <div class="gm-hero__media" aria-hidden="true">
        <picture>
            <source media="(max-width: 760px)" srcset="{{ $img }}/hero-dimsum-m.webp" type="image/webp">
            <source media="(max-width: 760px)" srcset="{{ $img }}/hero-dimsum-m.jpg">
            <source srcset="{{ $img }}/hero-dimsum.webp" type="image/webp">
            <img class="gm-hero__bg" src="{{ $img }}/hero-dimsum.jpg" alt="" width="1920" height="1251" decoding="async" fetchpriority="high">
        </picture>
        <div class="gm-steam">
            <span style="--x:0%;--d:0s;--s:1"></span>
            <span style="--x:18%;--d:1.6s;--s:.8"></span>
            <span style="--x:36%;--d:3.1s;--s:1.15"></span>
            <span style="--x:54%;--d:.8s;--s:.9"></span>
            <span style="--x:72%;--d:2.4s;--s:1.05"></span>
            <span style="--x:88%;--d:4s;--s:.75"></span>
        </div>
        <figure class="gm-duck" data-speed="-0.08">
            <div class="gm-duck__plate"><img src="{{ $img }}/canard-laque.webp" alt="" width="560" height="560"></div>
            <figcaption class="gm-duck__tag">{{ $gm::line('hero.duck') }}</figcaption>
        </figure>
    </div>
    <div class="gm-wrap gm-hero__grid">
        <div class="gm-hero__text">
            <p class="gm-kicker gm-kicker--light gm-load" style="--i:0">{{ $gm::line('hero.kicker') }}</p>
            <h1 class="gm-hero__title">
                <span class="gm-line"><span class="gm-load" style="--i:1">{{ $gm::line('hero.title1') }}</span></span>
                <span class="gm-line"><span class="gm-load" style="--i:2">{{ $gm::line('hero.title2') }}</span></span>
                <span class="gm-line"><em class="gm-load" style="--i:3">{{ $gm::line('hero.title3') }}</em></span>
            </h1>
            <p class="gm-hero__lead gm-load" style="--i:4">{{ $gm::line('hero.lead') }}</p>
            <div class="gm-actions gm-load" style="--i:5">
                <a class="gm-btn gm-btn--gold" href="#reservation" data-scrollscale data-reveal="pop">{{ $gm::line('hero.cta.book') }}</a>
                <a class="gm-btn gm-btn--ghost" href="#carte" data-scrollscale data-reveal="pop">{{ $gm::line('hero.cta.menus') }}</a>
            </div>
            <ul class="gm-hero__facts gm-load" style="--i:6" aria-label="{{ $gm::line('nav.infos') }}">
                <li><span>{{ $gm::line('hero.fact.address') }}</span>{{ $gm::line('hero.fact.address.value') }}</li>
                <li><span>{{ $gm::line('hero.fact.service') }}</span>{{ $gm::line('hero.fact.service.value') }}</li>
                <li><span>{{ $gm::line('hero.fact.phone') }}</span><a href="tel:+3287220888">087 22 08 88</a></li>
            </ul>
        </div>
    </div>
    <a class="gm-scrollcue" href="#galerie" aria-label="{{ $gm::line('hero.scroll') }}"><span></span></a>
</section>

<section class="gm-strip" id="galerie" aria-label="{{ $gm::line('gal.aria') }}">
    <div class="gm-wrap gm-strip__head" data-reveal="up">
        <p class="gm-kicker">{{ $gm::line('gal.kicker') }}</p>
        <div class="gm-arrows">
            <button type="button" class="gm-arrow" id="gal-prev" aria-label="{{ $gm::line('gal.prev') }}" data-reveal="pop"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M15 5l-7 7 7 7M8 12h13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            <button type="button" class="gm-arrow" id="gal-next" aria-label="{{ $gm::line('gal.next') }}" data-reveal="pop"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M9 5l7 7-7 7M16 12H3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
    </div>
    <div class="gm-strip__viewport" id="strip" role="region" aria-roledescription="carousel" aria-label="{{ $gm::line('gal.carousel') }}">
        <div class="gm-strip__track">
            @foreach ([
                ['salle.jpg', 'gal.salle', 720, 477],
                ['plat-crevettes.jpg', 'gal.specialites', 675, 900],
                ['dim-sum.jpg', 'gal.bouchees', 720, 480],
                ['facade.jpg', 'gal.facade', 720, 720],
                ['plat-cajou-boeuf.jpg', 'gal.partager', 720, 490],
                ['plat-rouleaux.jpg', 'gal.rouleaux', 675, 900],
                ['buffet.jpg', 'gal.buffet', 720, 482],
                ['potage.jpg', 'gal.potages', 675, 900],
                ['plat-assortiment.jpg', 'gal.assortiment', 720, 682],
                ['lampions.jpg', 'gal.ambiance', 720, 540],
            ] as [$file, $key, $width, $height])
                <figure class="gm-strip__item">
                    <div class="gm-soft"><img src="{{ $img }}/{{ $file }}" alt="{{ $gm::line($key) }}" loading="lazy" width="{{ $width }}" height="{{ $height }}"></div>
                    <figcaption>{{ $gm::line($key) }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

<section class="gm-section gm-intro" id="maison">
    <div class="gm-wrap gm-intro__grid">
        <div data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('maison.kicker') }}</p>
            <h2>{{ $gm::line('maison.title') }} <em>{{ $gm::line('maison.title.em') }}</em></h2>
            <p class="gm-lead">{{ $gm::line('maison.lead') }}</p>
        </div>
        <ul class="gm-highlights" data-stagger>
            <li data-reveal="up"><span class="gm-highlights__n">01</span><b>{{ $gm::line('maison.1.t') }}</b><p>{{ $gm::line('maison.1.p') }}</p></li>
            <li data-reveal="up"><span class="gm-highlights__n">02</span><b>{{ $gm::line('maison.2.t') }}</b><p>{{ $gm::line('maison.2.p') }}</p></li>
            <li data-reveal="up"><span class="gm-highlights__n">03</span><b>{{ $gm::line('maison.3.t') }}</b><p>{{ $gm::line('maison.3.p') }}</p></li>
        </ul>
    </div>
</section>

<section class="gm-section gm-buffet" id="buffet">
    <div class="gm-wrap gm-buffet__card" data-reveal="zoom">
        <div class="gm-buffet__media">
            <div class="gm-buffet__photo gm-soft" data-speed="-0.05"><img src="{{ $img }}/buffet.jpg" alt="{{ $gm::line('gal.buffet') }}" loading="lazy" width="720" height="482"></div>
            <div class="gm-buffet__photo gm-buffet__photo--sm gm-soft" data-speed="0.07"><img src="{{ $img }}/dim-sum.jpg" alt="" loading="lazy" width="720" height="480"></div>
        </div>
        <div class="gm-buffet__text">
            <p class="gm-kicker gm-kicker--light">{{ $gm::line('buffet.kicker') }}</p>
            <h2>{{ $gm::line('buffet.title') }} <em>{{ $gm::line('buffet.title.em') }}</em></h2>
            <p class="gm-buffet__days">{{ $gm::line('buffet.days') }}</p>
            <p>{{ $gm::line('buffet.lead') }}</p>
            <div class="gm-prices" data-stagger>
                <article data-reveal="up"><p>{{ $gm::line('buffet.p1') }}</p><strong>{{ number_format((float)$priceWithout, 2, ',', '') }} €</strong><small>{{ $gm::line('buffet.p1.note') }}</small></article>
                <article data-reveal="up"><p>{{ $gm::line('buffet.p2') }}</p><strong>{{ number_format((float)$priceWith, 2, ',', '') }} €</strong><small>{{ $gm::line('buffet.p2.note') }}</small></article>
            </div>
        </div>
    </div>
</section>

<section class="gm-section gm-menus" id="carte">
    <div class="gm-wrap">
        <div class="gm-section__head" data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('menus.kicker') }}</p>
            <h2>{{ $gm::line('menus.title') }} <em>{{ $gm::line('menus.title.em') }}</em></h2>
            <p>{{ $gm::line('menus.lead') }}</p>
        </div>
        <div id="menus" class="menu-grid">
            @foreach($carteMenus as $menu)
                @php
                    $rawName = (string)($menu->getRawOriginal('menu_name') ?: $menu->menu_name);
                    $lines = $gm::menuLines($rawName);
                    if ($lines === [] && filled($menu->menu_description)) {
                        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$menu->menu_description) ?: [])));
                    }
                @endphp
                <details class="menu-item" data-reveal="up" style="--stagger: {{ ($loop->index % 6) * 70 }}ms">
                    <summary>
                        <b>{{ $rawName }}</b>
                        <span>{{ number_format((float)$menu->menu_price, 2, ',', '') }} €</span>
                    </summary>
                    @foreach($lines as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </details>
            @endforeach
        </div>
    </div>
</section>

@themePartial('site.reservation')

<section class="gm-section gm-infos" id="infos">
    <div class="gm-wrap gm-infos__grid" data-stagger>
        <div class="gm-infos__card" data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('infos.find.kicker') }}</p>
            <h3>{{ $gm::line('infos.find.title') }} <em>{{ $gm::line('infos.find.title.em') }}</em></h3>
            <p>{!! $gm::html('infos.find.addr') !!}</p>
            <a class="gm-link" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=Avenue+Reine+Astrid+98+4900+Spa">{{ $gm::line('infos.maps') }}</a>
        </div>
        <div class="gm-infos__card" data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('infos.hours.kicker') }}</p>
            <h3>{{ $gm::line('infos.hours.title') }}</h3>
            <p>{!! $gm::html('infos.hours.p1') !!}</p>
            <p>{{ $gm::line('infos.hours.p2') }}</p>
        </div>
        <div class="gm-infos__card" data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('infos.contact.kicker') }}</p>
            <h3><a href="tel:+3287220888">087 22 08 88</a></h3>
            <p>{{ $gm::line('infos.contact.p') }}</p>
            <a class="gm-link" href="#reservation">{{ $gm::line('infos.contact.link') }}</a>
        </div>
    </div>
</section>
