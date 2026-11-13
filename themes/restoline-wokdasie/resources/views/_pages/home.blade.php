---
title: "La Grande Muraille de Chine | Buffet asiatique près de Mons"
description: "Buffet asiatique à volonté, ramen, wok et teppanyaki à Hornu près de Mons. Découvrez nos tarifs, fêtes, plats à emporter et réservez votre table."
layout: default
permalink: /
bodyClass: page-home
---
@php
    $restolineNews = collect();
    $restolineHomeIntro = null;
    $restolineHoliday = null;
    $restolineTakeaway = null;
    $takeawayMenuImages = collect();
    $quickBookingMinDate = now()->toDateString();
    $quickBookingMaxDate = now()->copy()->addMonths(3)->toDateString();

    if (class_exists(\Restoline\Content\Models\ContentItem::class)
        && \Illuminate\Support\Facades\Schema::hasTable('restoline_content_items')) {
        $restolineNews = \Restoline\Content\Models\ContentItem::query()
            ->published()
            ->where('type', 'news')
            ->latest('published_at')
            ->limit(4)
            ->get();

        $restolineHomeIntro = \Restoline\Content\Models\ContentItem::query()
            ->published()
            ->where('type', 'home')
            ->latest('published_at')
            ->first();

        $restolineHoliday = \Restoline\Content\Models\ContentItem::query()
            ->published()
            ->where('type', 'holiday')
            ->latest('published_at')
            ->first();

        $restolineTakeaway = \Restoline\Content\Models\ContentItem::query()
            ->published()
            ->where('type', 'takeaway')
            ->latest('published_at')
            ->first();

        $takeawayContent = $restolineTakeaway?->content ?: $restolineHoliday?->content;
        if ($takeawayContent) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $takeawayContent, $matches);
            $takeawayMenuImages = collect($matches[1] ?? [])->unique()->take(2)->values();
        }
    }
@endphp

<style>
    .wok-site #reservation-rapide.wok-quick-booking {
        position: relative;
        z-index: 4;
        width: min(1080px, calc(100% - 48px));
        margin: -46px auto 0;
    }
    .wok-site #reservation-rapide .wok-quick-booking-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 14px;
        padding: 12px;
        border: 1px solid rgba(179,138,75,.22);
        border-radius: 14px;
        background: rgba(255,255,255,.98);
        box-shadow: 0 18px 55px rgba(20,40,30,.14);
        backdrop-filter: blur(12px);
    }
    .wok-site #reservation-rapide .wok-quick-booking-intro {
        padding: 0 10px 0 6px;
    }
    .wok-site #reservation-rapide h2 {
        margin: 0;
        font-size: clamp(1.55rem, 2.4vw, 2.35rem);
        line-height: 1;
        white-space: nowrap;
    }
    .wok-site #reservation-rapide .wok-quick-booking-form {
        display: grid;
        grid-template-columns: minmax(155px, 1fr) minmax(118px, .72fr) minmax(118px, .72fr) minmax(190px, .9fr);
        align-items: end;
        gap: 8px;
        min-width: 0;
    }
    .wok-site #reservation-rapide .wok-booking-field {
        display: grid !important;
        gap: 5px;
        min-height: 62px;
        margin: 0 !important;
        padding: 8px 11px;
        border: 1px solid rgba(27,33,30,.1);
        border-radius: 8px;
        background: #f7f7f4;
    }
    .wok-site #reservation-rapide .wok-booking-field span {
        color: #626963;
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .1em;
        line-height: 1;
        text-transform: uppercase;
    }
    .wok-site #reservation-rapide .wok-booking-field input,
    .wok-site #reservation-rapide .wok-booking-field select {
        width: 100%;
        min-height: 32px;
        border: 0 !important;
        padding: 0;
        color: #1b211e;
        background: transparent !important;
        font: inherit;
        font-size: 1.05rem;
        font-weight: 900;
        outline: none;
        box-shadow: none !important;
    }
    .wok-site #reservation-rapide .wok-button {
        min-height: 62px;
        border-radius: 8px;
        white-space: nowrap;
    }
    @media (max-width: 920px) {
        .wok-site #reservation-rapide .wok-quick-booking-card { grid-template-columns: 1fr; }
        .wok-site #reservation-rapide .wok-quick-booking-form { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 680px) {
        .wok-site #reservation-rapide.wok-quick-booking {
            width: calc(100% - 22px);
            margin-top: -24px;
        }
        .wok-site #reservation-rapide .wok-quick-booking-card {
            gap: 10px;
            padding: 12px;
        }
        .wok-site #reservation-rapide h2 {
            font-size: 1.8rem;
        }
        .wok-site #reservation-rapide .wok-quick-booking-form {
            grid-template-columns: 1fr;
            gap: 7px;
        }
        .wok-site #reservation-rapide .wok-booking-field {
            min-height: 60px;
        }
        .wok-site #reservation-rapide .wok-button {
            min-height: 54px;
            width: 100%;
        }
    }
</style>

<section class="wok-hero">
    <img class="wok-hero-image" src="{{ asset('vendor/restoline-wokdasie/images/hero-wokdasie.jpg') }}" alt="Grande salle du restaurant La Grande Muraille de Chine à Hornu">
    <div class="wok-hero-content">
        <div class="wok-hero-frame">
            <div class="wok-hero-copy">
            <h1>La Grande Muraille de Chine</h1>
                <p class="wok-hero-kicker"><span>Buffet</span><span>Ramen</span><span>Wok</span><span>Teppanyaki</span></p>
            </div>
            <img class="wok-hero-logo" src="{{ asset('vendor/restoline-wokdasie/images/logo-wokdasie-hero-transparent.png') }}" alt="Logo La Grande Muraille de Chine">
        </div>
    </div>
    <a class="wok-scroll" href="#actualites" aria-label="Faire défiler vers le contenu"><span></span><i></i></a>
</section>

<section id="reservation-rapide" class="wok-quick-booking">
    <div class="wok-quick-booking-card">
        <div class="wok-quick-booking-intro">
            <h2>Réserver</h2>
        </div>
        <form class="wok-quick-booking-form" action="{{ url('/wok-d-asie-hornu/reservation') }}" method="get">
            <input type="hidden" name="step" value="timeslot">
            <label class="wok-booking-field">
                <span>Date</span>
                <input type="date" name="date" value="{{ $quickBookingMinDate }}" min="{{ $quickBookingMinDate }}" max="{{ $quickBookingMaxDate }}" required>
            </label>
            <label class="wok-booking-field">
                <span>Personnes</span>
                <select name="guest" required>
                    @for($i = 2; $i <= 20; $i++)
                        <option value="{{ $i }}" {{ $i === 2 ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </label>
            <label class="wok-booking-field">
                <span>Heure</span>
                <select name="time" required>
                    <option value="12:00">12:00</option>
                    <option value="12:30">12:30</option>
                    <option value="13:00">13:00</option>
                    <option value="13:30">13:30</option>
                    <option value="14:00">14:00</option>
                    <option value="18:00">18:00</option>
                    <option value="18:30">18:30</option>
                    <option value="19:00" selected>19:00</option>
                    <option value="19:30">19:30</option>
                    <option value="20:00">20:00</option>
                    <option value="20:30">20:30</option>
                    <option value="21:00">21:00</option>
                </select>
            </label>
            <button class="wok-button wok-button-primary" type="submit">Trouver une table</button>
        </form>
    </div>
</section>

@if($restolineNews->isNotEmpty())
    <section id="actualites" class="wok-section wok-intro">
        <div class="wok-section-heading">
            <p class="wok-eyebrow">Actualités</p>
            <h2>Les nouveautés du moment</h2>
            <p>Retrouvez nos nouveautés, menus spéciaux et inspirations du moment.</p>
        </div>
        <div class="wok-feature-grid">
            @foreach($restolineNews as $post)
            <article class="wok-feature {{ $loop->first ? 'wok-feature-large' : '' }}">
                <img src="{{ $post->image_url ?? asset('vendor/restoline-wokdasie/images/buffet-sushi-lumineux.jpg') }}" alt="{{ $post->title }}">
                <div>
                    <span>Actualité</span>
                    <h3>{{ $post->title }}</h3>
                    @if($post->summary)<p>{{ $post->summary }}</p>@endif
                </div>
            </article>
            @endforeach
        </div>
    </section>
@endif

<section id="buffet" class="wok-section wok-buffet">
    <div class="wok-buffet-intro">
        <img class="wok-buffet-logo" src="{{ asset('vendor/restoline-wokdasie/images/logo-wokdasie-square.jpg') }}" alt="Logo officiel du restaurant La Grande Muraille de Chine à Hornu, près de Mons">
        <div>
            <p class="wok-eyebrow">{{ $restolineHomeIntro?->title ?? "Restaurant La Grande Muraille de Chine à Hornu, près de Mons" }}</p>
            <h2>{{ $restolineHomeIntro?->summary ?? "Buffet d'exception, ramen, wok & teppanyaki" }}</h2>
            @if($restolineHomeIntro)
                <div class="wok-home-intro-text">{!! $restolineHomeIntro->content !!}</div>
            @else
                <p>Découvrez un buffet asiatique à volonté dans un cadre élégant à Hornu, près de Mons.</p>
                <p>Ramen, wok, teppanyaki, spécialités chinoises et japonaises : une adresse généreuse pour les familles, groupes et anniversaires, avec grand parking privé.</p>
            @endif
        </div>
    </div>
    <div class="wok-price-heading">
        <p class="wok-eyebrow">Nos formules</p>
        <h3>Tarifs buffet</h3>
    </div>
    <div class="wok-price-grid">
        <article><span>Du mardi au samedi midi</span><h3>Midi</h3><strong>21 €</strong><p>Hors boisson</p><p class="wok-price-option">ou 27 € avec boissons à volonté*</p></article>
        <article><span>Du mardi au jeudi soir</span><h3>Soir</h3><strong>29 €</strong><p>Hors boisson</p><p class="wok-price-option">ou 37 € avec boissons à volonté*</p></article>
        <article><span>Vendredi et samedi soir</span><h3>Soir</h3><strong>33 €</strong><p>Hors boisson</p><p class="wok-price-option">ou 41 € avec boissons à volonté*</p></article>
        <article><span>Dimanche et jours fériés</span><h3>Midi & soir</h3><strong>31 €</strong><p>Hors boisson</p><p class="wok-price-option">ou 39 € avec boissons à volonté*</p></article>
    </div>
    <aside class="wok-table-buffet-callout" aria-labelledby="table-buffet-title">
        <div>
            <p class="wok-eyebrow">Nouveau · Tous les soirs et le dimanche midi</p>
            <h3 id="table-buffet-title">Le buffet continue à votre table</h3>
            <p>Commandez une sélection de sushis, accompagnements chauds, petites bouchées et suggestions du moment, incluse sans supplément dans votre formule buffet.</p>
        </div>
        <a class="wok-button wok-button-dark" href="{{ url('/commander') }}">Découvrir le buffet à table <span>→</span></a>
    </aside>
    <article class="wok-family-price">
        <div>
            <p class="wok-eyebrow">Tarifs famille</p>
            <h3>Enfants & pensionnés</h3>
        </div>
        <p>Enfants jusque 3 ans : gratuit. Enfants de moins de 11 ans : moitié prix.</p>
        <p><strong>Pensionnés, le dimanche midi uniquement :</strong> 29 € sans boisson ou 37 € avec boissons à volonté*, par table entière.</p>
    </article>
    <div class="wok-buffet-details">
        <article>
            <p class="wok-eyebrow">Horaires</p>
            <h3>Profitez pleinement du buffet</h3>
            <p><strong>Midi :</strong> de 12:00 à 14:30, le dimanche jusque 15:00.</p>
            <p><strong>Soir :</strong> de 18:00 à 22:00, le vendredi et le samedi jusque 23:00.</p>
            <p>Le buffet doit être pris avec au minimum une boisson.</p>
        </article>
        <article>
            <p class="wok-eyebrow">Boissons à volonté*</p>
            <h3>La formule comprend</h3>
            <p>Vin en pichet rouge, rosé ou blanc, softs, granita et bière Jupiler pendant les horaires du buffet.</p>
            <p>La formule doit être prise par la table entière.</p>
        </article>
    </div>
    <div class="wok-buffet-notice">
        <p>Un supplément de 8 € à 20 € peut être facturé en cas de gaspillage de nourriture ou de boissons.</p>
        <p>Une allergie ? Demandez plus d'informations à un responsable avant de vous servir.</p>
    </div>
</section>

<section id="fetes" class="wok-story wok-story-text-only">
    <div class="wok-story-content">
        <p class="wok-eyebrow">Fêtes & événements</p>
        <h2>{{ $restolineHoliday?->title ?? 'Les grandes occasions se partagent.' }}</h2>
        @if($restolineHoliday)
            <div class="wok-rich-content">{!! $restolineHoliday->content !!}</div>
        @else
            <p>Menus et buffets spéciaux pour Noël, Nouvel An, fêtes familiales et événements. Consultez régulièrement nos actualités avant de réserver.</p>
        @endif
        <a class="wok-text-link" href="{{ url('/wok-d-asie-hornu/reservation') }}">Préparer votre venue <span>→</span></a>
    </div>
</section>

<section id="plats-emporter" class="wok-section wok-takeaway-menu">
    <div class="wok-section-heading wok-heading-row">
        <div>
            <p class="wok-eyebrow">Plats à emporter</p>
            <h2>{{ $restolineTakeaway?->title ?? 'La carte à consulter en grand.' }}</h2>
        </div>
        <p>{{ $restolineTakeaway?->summary ?? 'Appuyez sur une image pour l’agrandir.' }}</p>
    </div>
    <div class="wok-takeaway-menu-grid">
        @forelse($takeawayMenuImages as $menuImage)
            <button class="wok-menu-image" type="button" data-lightbox-src="{{ $menuImage }}" aria-label="Agrandir la carte à emporter">
                <img src="{{ $menuImage }}" alt="Carte des plats à emporter La Grande Muraille de Chine">
                <span>Agrandir</span>
            </button>
        @empty
            <button class="wok-menu-image" type="button" data-lightbox-src="{{ asset('vendor/restoline-wokdasie/images/emporter-wokdasie.jpg') }}" aria-label="Agrandir la photo des plats à emporter">
                <img src="{{ asset('vendor/restoline-wokdasie/images/emporter-wokdasie.jpg') }}" alt="Plats asiatiques proposés à emporter">
                <span>Agrandir</span>
            </button>
        @endforelse
    </div>
    <a class="wok-button wok-button-dark" href="tel:065783378">Commander au 065 78 33 78</a>
</section>

<section id="groupes" class="wok-section wok-groups">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">Groupes & anniversaires</p>
        <h2>Une grande salle.<br aria-hidden="true"> De grands moments.</h2>
    </div>
    <div class="wok-groups-grid">
        <img src="{{ asset('vendor/restoline-wokdasie/images/salle-groupes.jpg') }}" alt="Salle du La Grande Muraille de Chine pour les groupes">
        <div class="wok-group-offers">
            <article>
                <strong>−20%</strong>
                <div class="wok-group-copy">
                    <span>Votre anniversaire</span>
                    <p>Sur présentation de la carte d’identité, dans les 5 jours avant ou après votre anniversaire.</p>
                    <p>Réservation obligatoire au moins un jour à l’avance en précisant « ANNIVERSAIRE ». Offre non cumulable, hors fêtes et événements spéciaux.</p>
                    <p>La maison offre également un dessert à la personne fêtée.</p>
                    <p>Avantage anniversaire personnalisé si vous vous inscrivez à la newsletter.</p>
                </div>
            </article>
            <article>
                <strong>15+</strong>
                <div class="wok-group-copy">
                    <span>Un buffet gratuit</span>
                    <p>Avec notre formule All in, un buffet est offert pour les groupes à partir de 15 adultes.</p>
                    <p>Une façon simple d’organiser votre repas et de profiter pleinement de votre événement.</p>
                </div>
            </article>
            <article>
                <strong>180</strong>
                <div class="wok-group-copy">
                    <span>couverts</span>
                    <p>Une grande capacité, une décoration soignée et un buffet chaud et froid généreux et varié.</p>
                    <p>Anniversaires, mariages, séminaires ou repas d’entreprise : réunissez facilement tous vos invités.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="wok-section wok-gallery">
    <div class="wok-section-heading wok-heading-row">
        <div><p class="wok-eyebrow">L’expérience</p><h2>Un aperçu<br aria-hidden="true"> du restaurant.</h2></div>
        <a class="wok-text-link" href="{{ url('/wok-d-asie-hornu/reservation') }}">Réserver une table <span>→</span></a>
    </div>
    <div class="wok-gallery-grid">
        <img src="{{ asset('vendor/restoline-wokdasie/images/hero-wokdasie.jpg') }}" alt="Grande salle du restaurant La Grande Muraille de Chine à Hornu pour familles et groupes">
        <img src="{{ asset('vendor/restoline-wokdasie/images/buffet-sushi-selection-20260823.jpg') }}" alt="Sélection de sushis frais au buffet asiatique La Grande Muraille de Chine à Hornu">
        <img src="{{ asset('vendor/restoline-wokdasie/images/buffet-chaud-20260823.jpg') }}" alt="Buffet chaud asiatique, wok et teppanyaki au restaurant La Grande Muraille de Chine">
    </div>
</section>
