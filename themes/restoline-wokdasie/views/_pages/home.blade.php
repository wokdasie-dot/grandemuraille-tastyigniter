---
title: "La Grande Muraille de Chine | Buffet asiatique à Spa"
description: "La Grande Muraille de Chine : buffet asiatique à volonté, à la Avenue Reine Astrid 98. Consultez les horaires et réservez votre table en ligne."
layout: default
permalink: /
bodyClass: page-home
---
@php
    // Widget de réservation express : dates ouvertes, nombre de convives et créneaux réels.
    $quickBooking = null;
    $onlineBookingEnabled = \Restoline\Content\Support\OnlineBooking::isEnabled();

    $galleryItems = collect();
    if (class_exists(\Restoline\Content\Models\GalleryItem::class)
        && \Illuminate\Support\Facades\Schema::hasTable('restoline_gallery_items')) {
        $galleryItems = \Restoline\Content\Models\GalleryItem::listForDisplay(8);
    }
    try {
        $quickBookingLocation = \Igniter\Local\Facades\Location::current();
        $quickBookingModel = $quickBookingLocation instanceof \Igniter\Local\Models\Location
            ? $quickBookingLocation
            : (is_object($quickBookingLocation) && method_exists($quickBookingLocation, 'getModel')
                ? $quickBookingLocation->getModel()
                : null);

        if (!$quickBookingModel && class_exists(\Igniter\Local\Models\Location::class)) {
            $quickBookingModel = \Igniter\Local\Models\Location::query()->where('location_status', 1)->first();
        }

        if ($quickBookingModel) {
            $quickBookingMinAdvance = (int)$quickBookingModel->getMinReservationAdvanceTime();
            $quickBookingMaxAdvance = max($quickBookingMinAdvance + 1, (int)$quickBookingModel->getMaxReservationAdvanceTime());
            $quickBookingSchedule = $quickBookingModel->newWorkingSchedule('opening', [$quickBookingMinAdvance, $quickBookingMaxAdvance]);

            $quickBookingDefaultDate = null;
            $quickBookingClosedWeekdays = [];
            $quickBookingCursor = now()->addDays($quickBookingMinAdvance)->startOfDay();

            for ($i = 0; $i <= ($quickBookingMaxAdvance - $quickBookingMinAdvance); $i++) {
                if (count($quickBookingSchedule->forDate($quickBookingCursor)) > 0) {
                    $quickBookingDefaultDate ??= $quickBookingCursor->copy();
                } else {
                    $quickBookingClosedWeekdays[$quickBookingCursor->dayOfWeek] = true;
                }

                $quickBookingCursor->addDay();
            }

            if ($quickBookingDefaultDate) {
                $quickBookingManager = resolve(\Igniter\Reservation\Classes\BookingManager::class);
                $quickBookingManager->useLocation($quickBookingModel);

                $quickBookingTimes = collect($quickBookingManager->makeTimeSlots($quickBookingDefaultDate->copy()->startOfDay()))
                    ->map(fn($slot) => $slot->format('H:i'))
                    ->unique()
                    ->values();

                $quickBooking = [
                    'min_date' => now()->addDays($quickBookingMinAdvance)->toDateString(),
                    'max_date' => now()->addDays($quickBookingMaxAdvance)->toDateString(),
                    'date' => $quickBookingDefaultDate->toDateString(),
                    'times' => $quickBookingTimes->all(),
                    'default_time' => $quickBookingTimes->first(fn($time) => $time >= '19:00') ?? $quickBookingTimes->first(),
                    'min_guest' => (int)($quickBookingModel->getMinReservationGuestCount() ?: 1),
                    'max_guest' => (int)($quickBookingModel->getMaxReservationGuestCount() ?: 20),
                    'closed_weekdays' => array_keys($quickBookingClosedWeekdays),
                ];
            }
        }
    } catch (\Throwable $exception) {
        $quickBooking = null;
    }

    $buffetPricings = collect();
    if (class_exists(\Restoline\Content\Models\BuffetPricing::class)
        && \Illuminate\Support\Facades\Schema::hasTable('restoline_buffet_pricings')) {
        $buffetPricings = \Restoline\Content\Models\BuffetPricing::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get();
    }

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

    $drinkCategories = collect();
    if (class_exists(\Igniter\Cart\Models\Category::class)
        && \Illuminate\Support\Facades\Schema::hasTable('categories')
        && \Illuminate\Support\Facades\Schema::hasTable('menus')) {
        $drinksRoot = \Igniter\Cart\Models\Category::query()
            ->where('permalink_slug', 'boissons')
            ->where('status', true)
            ->first();

        if ($drinksRoot) {
            $drinkCategories = \Igniter\Cart\Models\Category::query()
                ->where('parent_id', $drinksRoot->getKey())
                ->where('status', true)
                ->with(['menus' => fn($query) => $query
                    ->where('menu_status', true)
                    ->orderBy('menu_priority')
                    ->orderBy('menu_name')])
                ->orderBy('priority')
                ->get()
                ->filter(fn($category) => $category->menus->isNotEmpty());
        }
    }
@endphp

<style>
    .wok-pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-top: 28px;
    }
    .wok-pricing-grid article {
        display: grid;
        gap: 6px;
        padding: 22px;
        border: 1px solid var(--wok-line);
        border-radius: var(--wok-radius);
        background: var(--wok-white);
    }
    .wok-pricing-grid article .wok-eyebrow {
        font-size: .72rem;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--wok-gold);
        font-weight: 700;
    }
    .wok-pricing-grid article h3 {
        font-family: "Playfair Display", Georgia, serif;
        font-size: 1.15rem;
        margin: 0;
    }
    .wok-pricing-grid article .wok-price-adult {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--wok-green);
    }
    .wok-pricing-grid article .wok-price-child {
        font-size: .85rem;
        color: var(--wok-muted);
    }
    .wok-pricing-grid article .wok-price-child strong {
        color: var(--wok-ink);
    }
    .wok-drinks-section {
        margin-top: 22px;
    }
    .wok-drinks-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }
    .wok-drinks-tab {
        padding: 8px 16px;
        border: 1px solid var(--wok-line);
        border-radius: 999px;
        background: var(--wok-white);
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
    }
    .wok-drinks-tab[aria-selected="true"] {
        background: var(--wok-green);
        color: var(--wok-white);
        border-color: var(--wok-green);
    }
    .wok-drinks-panel {
        display: none;
    }
    .wok-drinks-panel.is-active {
        display: block;
    }
    .wok-drinks-table {
        width: 100%;
        border-collapse: collapse;
    }
    .wok-drinks-table th, .wok-drinks-table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid var(--wok-line);
    }
    .wok-drinks-table th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--wok-muted);
        font-weight: 700;
    }
    .wok-drinks-table td:last-child {
        text-align: right;
        font-weight: 700;
        color: var(--wok-green);
        white-space: nowrap;
    }
    @media (max-width: 680px) {
        .wok-pricing-grid { grid-template-columns: 1fr; }
    }
</style>

<section class="wok-hero wok-hero-plain">
    <div class="wok-hero-content">
        <div class="wok-hero-frame">
            <div class="wok-hero-copy">
                <p class="wok-eyebrow">Cuisine chinoise</p>
                <h1>La Grande Muraille de Chine</h1>
                <p>Une invitation à découvrir les saveurs de la cuisine chinoise, au cœur de Spa.</p>
                <a class="wok-button wok-button-primary" href="{{ url('/grande-muraille/reservation') }}">Réserver une table</a>
            </div>
        </div>
    </div>
</section>

@if($quickBooking)
<section id="reservation-rapide" class="wok-quick-booking" aria-labelledby="reservation-rapide-titre">
    <div class="wok-quick-booking-card">
        <div class="wok-quick-booking-intro">
            <p class="wok-eyebrow">Réservation en ligne</p>
            <h2 id="reservation-rapide-titre">Réserver</h2>
        </div>
        @if(!$onlineBookingEnabled)
            <p class="wok-quick-booking-closed">Les réservations en ligne sont momentanément fermées. Pour réserver, appelez-nous au <a href="tel:+3287220888">087 22 08 88</a>.</p>
        @else
        <form class="wok-quick-booking-form" action="{{ url('/grande-muraille/reservation') }}" method="get" data-quick-booking>
            <input type="hidden" name="step" value="timeslot">
            <label class="wok-booking-field">
                <span>Date</span>
                <input
                    type="date"
                    name="date"
                    value="{{ $quickBooking['date'] }}"
                    min="{{ $quickBooking['min_date'] }}"
                    max="{{ $quickBooking['max_date'] }}"
                    required
                    aria-label="Date de réservation"
                    data-quick-booking-date
                >
            </label>
            <label class="wok-booking-field">
                <span>Personnes</span>
                <select name="guest" required aria-label="Nombre de personnes">
                    @for($guest = $quickBooking['min_guest']; $guest <= $quickBooking['max_guest']; $guest++)
                        <option value="{{ $guest }}" @selected($guest === min(max(2, $quickBooking['min_guest']), $quickBooking['max_guest']))>{{ $guest }}</option>
                    @endfor
                </select>
            </label>
            <label class="wok-booking-field">
                <span>Heure</span>
                <select name="time" required aria-label="Heure de réservation">
                    @foreach($quickBooking['times'] as $time)
                        <option value="{{ $time }}" @selected($time === $quickBooking['default_time'])>{{ $time }}</option>
                    @endforeach
                </select>
            </label>
            <button class="wok-button wok-button-primary" type="submit">Trouver une table</button>
            <p class="wok-quick-booking-hint" data-quick-booking-hint hidden>Le restaurant est fermé ce jour-là. Choisissez une autre date.</p>
        </form>
        @endif
    </div>
</section>
<script>
    (() => {
        const form = document.querySelector('[data-quick-booking]');
        if (!form) return;

        const dateInput = form.querySelector('[data-quick-booking-date]');
        const hint = form.querySelector('[data-quick-booking-hint]');
        const closedWeekdays = @json($quickBooking['closed_weekdays']);
        const closedMessage = 'Le restaurant est fermé ce jour-là. Choisissez une autre date.';

        const checkClosedDay = () => {
            if (!dateInput?.value) return;
            const day = new Date(dateInput.value + 'T12:00:00').getDay();
            const isClosed = closedWeekdays.includes(day);
            dateInput.setCustomValidity(isClosed ? closedMessage : '');
            if (hint) hint.hidden = !isClosed;
        };

        dateInput?.addEventListener('change', checkClosedDay);
        dateInput?.addEventListener('input', checkClosedDay);
        checkClosedDay();
    })();
</script>
@endif

<section id="buffet" class="wok-section wok-intro">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">Buffet à volonté</p>
        <h2>Une table pour toutes les envies.</h2>
        <p>Menus chinois, spécialités du chef et buffet à volonté : la Grande Muraille de Chine vous accueille à Spa pour partager un moment gourmand, en famille ou entre amis.</p>
    </div>
    <div class="wok-buffet-details">
        <article>
            <p class="wok-eyebrow">Adresse</p>
            <h3>Au cœur de Spa</h3>
            <p>Avenue Reine Astrid 98<br>4900 Spa</p>
            <a class="wok-text-link" href="https://www.google.com/maps/dir/?api=1&destination=Avenue+Reine+Astrid+98%2C+4900+Spa" target="_blank" rel="noopener noreferrer">Ouvrir l'itinéraire <span>→</span></a>
        </article>
        <article id="horaires">
            <p class="wok-eyebrow">Horaires</p>
            <h3>Ouvert sauf le mardi</h3>
            <p><strong>Lundi, mercredi à dimanche</strong><br>12:00 à 14:30<br>18:00 à 22:30</p>
            <p>Fermeture hebdomadaire le mardi.</p>
        </article>
    </div>
</section>

@if($carteMenus->isNotEmpty())
<section id="menus" class="wok-section">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">La carte</p>
        <h2>Menus</h2>
        <p>Ces menus se consultent sur place. La réservation en ligne ne prend pas de commande.</p>
    </div>
    <div class="wok-menu-list">
        @foreach($carteMenus as $menu)
        <article>
            <h3>{{ $menu->menu_name }}</h3>
            <span class="wok-price-adult">{{ number_format((float)$menu->menu_price, 2, ',', '') }} &euro;</span>
            @if($menu->menu_description)
                <p>{{ $menu->menu_description }}</p>
            @endif
        </article>
        @endforeach
    </div>
</section>
@endif

@if($buffetPricings->isNotEmpty())
<section id="tarifs" class="wok-section">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">Nos tarifs</p>
        <h2>Buffet à volonté</h2>
        <p>Les boissons ne sont pas comprises dans les formules presentees ci-dessous.</p>
    </div>
    <div class="wok-pricing-grid">
        @foreach($buffetPricings as $pricing)
        <article>
            <span class="wok-eyebrow">{{ $pricing->label }}</span>
            <h3>{{ $pricing->category === 'weekday_lunch' ? 'Midi' : ($pricing->category === 'sunday_holiday' ? 'Toute la journee' : 'Soir') }}</h3>
            <span class="wok-price-adult">{{ number_format($pricing->adult_price, 2, ',', '') }} &euro;</span>
            <span class="wok-price-child">Adulte</span>
            <span class="wok-price-child"><strong>{{ number_format($pricing->child_4_8_price, 2, ',', '') }} &euro;</strong> &middot; Enfant 4 à 9 ans</span>
            <span class="wok-price-child"><strong>{{ number_format($pricing->child_9_13_price, 2, ',', '') }} &euro;</strong> &middot; Enfant 10 à 12 ans</span>
        </article>
        @endforeach
    </div>
    <div class="wok-buffet-notice" style="margin-top:22px;">
        <p>Un supplement peut etre applique en cas de gaspillage de nourriture ou de boissons.</p>
        <p>Pour toute demande particuliere, veuillez vous adresser au personnel.</p>
    </div>
</section>
@endif

@if($drinkCategories->isNotEmpty())
<section id="boissons" class="wok-section">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">Carte des boissons</p>
        <h2>Apéritifs, bières, vins et digestifs</h2>
        <p>Consultez notre sélection de boissons pour accompagner votre repas.</p>
    </div>

    <div class="wok-drinks-section" data-drinks-tabs>
        <div class="wok-drinks-tabs" role="tablist">
            @foreach($drinkCategories as $category)
                <button class="wok-drinks-tab" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-tab="drink-category-{{ $category->category_id }}">{{ $category->name }}</button>
            @endforeach
        </div>

        @foreach($drinkCategories as $category)
        <div class="wok-drinks-panel {{ $loop->first ? 'is-active' : '' }}" role="tabpanel" data-panel="drink-category-{{ $category->category_id }}">
            <table class="wok-drinks-table">
                <thead><tr><th>{{ $category->name }}</th><th>Prix</th></tr></thead>
                <tbody>
                    @foreach($category->menus as $drink)
                        <tr><td>{{ $drink->menu_name }}</td><td>{{ number_format((float)$drink->menu_price, 2, ',', '') }} &euro;</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</section>
@endif

<section id="formule" class="wok-section">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">Réservation</p>
        <h2>Jusqu'à 20 personnes en ligne.</h2>
        <p>À partir de 12 personnes, nous confirmons en général par téléphone. Fermé le mardi, sauf jours fériés.</p>
    </div>
</section>

@if($galleryItems->isNotEmpty())
<section id="galerie" class="wok-section wok-gallery-section">
    <div class="wok-section-heading">
        <p class="wok-eyebrow">En images</p>
        <h2>Le restaurant en photos.</h2>
        <p>Le buffet, le bar et la salle du La Grande Muraille de Chine. Cliquez sur une photo pour l’agrandir.</p>
    </div>
    <div class="wok-gallery-grid">
        @foreach($galleryItems as $item)
            <button class="wok-gallery-item" type="button" data-lightbox-src="{{ $item->image_url }}" aria-label="Agrandir la photo : {{ $item->title ?: 'La Grande Muraille de Chine' }}">
                <img src="{{ $item->image_url }}" alt="{{ $item->title ?: 'Photo du restaurant La Grande Muraille de Chine' }}" loading="lazy" decoding="async" width="675" height="900">
                @if($item->title)
                    <span>{{ $item->title }}</span>
                @endif
            </button>
        @endforeach
    </div>
</section>
@endif

<section class="wok-story wok-story-text-only">
    <div class="wok-story-content">
        <p class="wok-eyebrow">Reservation</p>
        <h2>Preparez votre venue.</h2>
        <p>Reservez votre table en ligne. Les disponibilites affichees sont mises a jour selon les horaires et la capacite du restaurant.</p>
        <a class="wok-button wok-button-dark" href="{{ url('/grande-muraille/reservation') }}">Réserver une table</a>
    </div>
</section>

<div class="wok-lightbox" data-image-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Image en grand format">
    <button type="button" data-image-lightbox-close aria-label="Fermer l'image">×</button>
    <img src="" alt="">
</div>
<script>
    (() => {
        const lightbox = document.querySelector('[data-image-lightbox]');
        if (!lightbox) return;
        const image = lightbox.querySelector('img');
        const close = () => {
            lightbox.hidden = true;
            lightbox.setAttribute('aria-hidden', 'true');
            image.src = '';
        };
        document.querySelectorAll('[data-lightbox-src]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                image.src = trigger.dataset.lightboxSrc;
                image.alt = trigger.querySelector('img')?.alt || '';
                lightbox.hidden = false;
                lightbox.setAttribute('aria-hidden', 'false');
                lightbox.querySelector('[data-image-lightbox-close]')?.focus();
            });
        });
        lightbox.querySelector('[data-image-lightbox-close]')?.addEventListener('click', close);
        lightbox.addEventListener('click', (event) => { if (event.target === lightbox) close(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !lightbox.hidden) close(); });

        // Tabs boissons
        const tabsContainer = document.querySelector('[data-drinks-tabs]');
        if (tabsContainer) {
            const tabs = tabsContainer.querySelectorAll('[data-tab]');
            const panels = tabsContainer.querySelectorAll('[data-panel]');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.tab;
                    tabs.forEach(t => { t.setAttribute('aria-selected', 'false'); });
                    tab.setAttribute('aria-selected', 'true');
                    panels.forEach(p => { p.classList.toggle('is-active', p.dataset.panel === target); });
                });
            });
        }
    })();
</script>
