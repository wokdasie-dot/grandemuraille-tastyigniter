---
title: "Carte à la carte | La Grande Muraille de Chine"
description: "La carte à la carte de La Grande Muraille de Chine, à Spa."
layout: default
permalink: /carte
bodyClass: page-carte
---
@php
    $gm = \Restoline\Content\Support\PublicCopy::class;
    $categories = $gm::carte();
    $promo = $gm::cartePromo();
@endphp

<section class="gm-section gm-alacarte" id="alacarte">
    <div class="gm-wrap">
        <div class="gm-section__head" data-reveal="up">
            <p class="gm-kicker">{{ $gm::line('carte.kicker') }}</p>
            <h1>{{ $gm::line('carte.title') }} <em>{{ $gm::line('carte.title.em') }}</em></h1>
            <p>{{ $gm::line('carte.lead') }}</p>
            @if($promo !== '')
                <p class="gm-alacarte__promo">{{ $promo }}</p>
            @endif
        </div>

        @if($categories)
            <nav class="gm-alacarte__toc" aria-label="{{ $gm::line('carte.toc') }}" data-reveal="up">
                @foreach($categories as $category)
                    <a href="#carte-{{ $category['id'] }}" data-scrollscale data-reveal="pop">{{ $category['title'] }}</a>
                @endforeach
            </nav>
        @endif

        @foreach($categories as $category)
            <section class="gm-alacarte__section" id="carte-{{ $category['id'] }}">
                <h2 data-reveal="up">{{ $category['title'] }}</h2>
                <ul class="gm-alacarte__list">
                    @foreach($category['items'] as $item)
                        <li data-reveal="up" style="--stagger: {{ ($loop->index % 8) * 40 }}ms">
                            <span class="gm-alacarte__num">{{ $item['num'] }}</span>
                            <span class="gm-alacarte__name">{{ $item['name'] }}</span>
                            <span class="gm-alacarte__price">{{ $item['price_label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <div class="gm-alacarte__cta" data-reveal="up">
            <a class="gm-btn gm-btn--gold" href="tel:+3287220888" data-scrollscale data-reveal="pop">{{ $gm::line('resa.cta') }}</a>
            <a class="gm-link" href="{{ url('/') }}">{{ $gm::line('carte.back') }}</a>
        </div>
    </div>
</section>
