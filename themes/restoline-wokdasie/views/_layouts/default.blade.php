<!doctype html>
<html lang="{{ App::getLocale() }}" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $canonicalUrl = url()->current();
        $requestPath = trim(request()->path(), '/');
        $pageTitle = lang('restoline.content::frontend.home.meta_title');
        $pageDescription = lang('restoline.content::frontend.home.meta_description');
        if (str_contains($requestPath, 'reservation/success')) {
            $pageTitle = lang('restoline.content::frontend.success.meta_title');
            $pageDescription = lang('restoline.content::frontend.success.meta_description');
        } elseif (str_contains($requestPath, 'reservation')) {
            $pageTitle = lang('restoline.content::frontend.booking.meta_title');
            $pageDescription = lang('restoline.content::frontend.booking.meta_description');
        } elseif (str_contains($requestPath, 'confidentialite')) {
            $pageTitle = lang('restoline.content::frontend.privacy.meta_title');
            $pageDescription = lang('restoline.content::frontend.privacy.meta_description');
        }
        $shareImage = null;
    @endphp
    <title>{{ $pageTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="La Grande Muraille de Chine">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($shareImage)
        <meta property="og:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="description" content="{{ $pageDescription }}">
    @if(request()->is('reservation/gerer*'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('apple-touch-icon-precomposed.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @php
        $turnstileSiteKey = app(\Restoline\Content\Services\TurnstileService::class)->siteKey();
        $restaurantSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            '@id' => url('/').'#restaurant',
            'name' => 'La Grande Muraille de Chine',
            'url' => url('/'),
            'image' => url('/'),
            'logo' => url('/'),
            'telephone' => '+3287220888',
            'servesCuisine' => ['Cuisine asiatique', 'Cuisine chinoise', 'Cuisine japonaise', 'Buffet', 'Wok', 'Teppanyaki'],
            'priceRange' => '€€',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Avenue Reine Astrid 98',
                'postalCode' => '4040',
                'addressLocality' => 'Spa',
                'addressRegion' => 'Liege',
                'addressCountry' => 'BE',
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '12:00',
                    'closes' => '14:30',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '18:00',
                    'closes' => '22:30',
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($restaurantSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @if($turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @if (request()->is('*/reservation'))
        @php
            // Le composant Livewire ajoute ces styles pendant le rendu de la page,
            // apres @themeStyles. Les enregistrer ici garantit que le calendrier
            // Flatpickr est deja style lorsqu'il est affiche.
            \Igniter\System\Facades\Assets::addCss('$/igniter/css/vendor.css', 'vendor-css');
            \Igniter\System\Facades\Assets::addCss('igniter.admin::css/formwidgets/datepicker.css', 'datepicker-css');
            \Igniter\System\Facades\Assets::addCss('igniter-orange::/css/booking.css', 'booking-css');
        @endphp
    @endif
    @themeStyles
    @livewireStyles
</head>
<body class="wok-site {{ $this->page->bodyClass ?? '' }}">
    @themePartial('site.header')

    <main id="contenu" class="wok-main">
        @themePage
    </main>

    @themePartial('site.footer')

    <livewire:igniter-orange::utils.modal/>
    <livewire:igniter-orange::utils.flash-message/>
    @themePartial('site.cookie')
    @livewireScripts
    @include('igniter-orange::includes.scripts')
    <script>
        window.initWokDasieTheme?.();

        (() => {
            const revealTargets = document.querySelectorAll('.wok-section, .wok-story, .wok-gallery');
            if (!revealTargets.length || !('IntersectionObserver' in window)) return;

            document.body.classList.add('wok-animate-ready');
            revealTargets.forEach((element) => element.classList.add('wok-reveal'));

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.16 });

            revealTargets.forEach((element) => observer.observe(element));
        })();

        (() => {
            const hero = document.querySelector('.wok-hero');
            const heroCopy = document.querySelector('.wok-hero-copy');
            const heroLogo = document.querySelector('.wok-hero-logo');
            if (!hero || !heroCopy || !heroLogo) return;

            let ticking = false;
            const animateHero = () => {
                const distance = Math.min(window.scrollY, hero.offsetHeight);
                const progress = distance / Math.max(hero.offsetHeight, 1);

                heroCopy.style.transform = `translateY(${progress * -34}px)`;
                heroCopy.style.opacity = String(Math.max(0.72, 1 - progress * 0.38));
                heroLogo.style.setProperty('--wok-logo-scroll', `${progress * -28}px`);
                heroLogo.style.setProperty('--wok-logo-scale', String(1 + progress * 0.12));
                heroLogo.style.opacity = String(Math.max(0.82, 1 - progress * 0.18));
                ticking = false;
            };

            const requestAnimation = () => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(animateHero);
            };

            animateHero();
            window.addEventListener('scroll', requestAnimation, { passive: true });
        })();

        (() => {
            const to24Hour = (value) => value.replace(
                /\b(\d{1,2}):(\d{2})[\s\u00a0]*(a\.?m\.?|p\.?m\.?)\b/gi,
                (_, hour, minutes, period) => {
                    let parsedHour = Number(hour);
                    const normalizedPeriod = period.replace(/\./g, '').toUpperCase();
                    if (normalizedPeriod === 'PM' && parsedHour !== 12) parsedHour += 12;
                    if (normalizedPeriod === 'AM' && parsedHour === 12) parsedHour = 0;
                    return `${String(parsedHour).padStart(2, '0')}:${minutes}`;
                },
            );

            const force24HourText = () => {
                document.querySelectorAll('.wok-booking-native button, .wok-booking-native p, .wok-booking-native h1, .wok-booking-native h4, .wok-booking-native h5, .wok-booking-native label, .wok-booking-native option, .wok-booking-native a, .wok-booking-native span')
                    .forEach((element) => {
                        if (!/(a\.?m\.?|p\.?m\.?)/i.test(element.textContent || '')) return;

                        element.childNodes.forEach((node) => {
                            if (node.nodeType !== Node.TEXT_NODE) return;
                            const converted = to24Hour(node.textContent);
                            if (converted !== node.textContent) node.textContent = converted;
                        });

                        if (!element.children.length) {
                            const converted = to24Hour(element.textContent);
                            if (converted !== element.textContent) element.textContent = converted;
                        }
                    });
            };

            const localizeBooking = () => {
                const guestSelect = document.querySelector('.wok-booking-native #noOfGuests');
                guestSelect?.childNodes.forEach((node) => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.includes('{')) node.remove();
                });
                if (guestSelect && /^1\s/.test(guestSelect.selectedOptions?.[0]?.textContent?.trim() || '')) {
                    const twoGuestsOption = Array.from(guestSelect.options).find((option) => /^2\s/.test(option.textContent.trim()));
                    if (twoGuestsOption) {
                        guestSelect.value = twoGuestsOption.value;
                        guestSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                const bookingLocale = @json(app()->getLocale());
                const localizeBookingText = (value) => {
                        let text = value;
                        if (bookingLocale !== 'fr') {
                            return to24Hour(text);
                        }
                        text = text.replace(/\bReserve A Table\b/gi, 'Réserver une table');
                        text = text.replace(/\bNumber of guests\b/gi, 'Nombre de personnes');
                        text = text.replace(/\bFirst Name\b/gi, 'Prénom');
                        text = text.replace(/\bLast Name\b/gi, 'Nom');
                        text = text.replace(/\bEmail Address\b/gi, 'Adresse e-mail');
                        text = text.replace(/\bTelephone\b/gi, 'Téléphone');
                        text = text.replace(/\bSpecial Requests\b/gi, 'Demandes particulières');
                        text = text.replace(/\bLocation\b/gi, 'Restaurant');
                        text = text.replace(/\bFind Table\b/gi, 'Voir les disponibilités');
                        text = text.replace(/\bSelect time\b/gi, 'Sélectionner une heure');
                        text = text.replace(/\bSélectionner time\b/gi, 'Sélectionner une heure');
                        text = text.replace(/\bChoose a time\b/gi, 'Choisir une heure');
                        text = text.replace(/\bChoisir l heure de retrait\b/gi, 'Choisir une heure');
                        if (/^\s*Time\s*$/i.test(text)) text = 'Heure';
                        text = text.replace(/\bBooking Summary\b/gi, 'Récapitulatif de réservation');
                        text = text.replace(/\bReservation Summary\b/gi, 'Récapitulatif de réservation');
                        text = text.replace(/\bYour Details\b/gi, 'Vos coordonnées');
                        text = text.replace(/\bComplete Reservation\b/gi, 'Envoyer ma demande');
                        text = text.replace(/\bConfirm Reservation\b/gi, 'Confirmer ma réservation');
                        text = text.replace(/\bStart again\b/gi, 'Recommencer');
                        text = text.replace(/\bAlready have an account\?\b/gi, 'Déjà client ?');
                        text = text.replace(/\bLogin Here\b/gi, 'Se connecter');
                        text = text.replace(/\bReserving your table\. Please wait\.\.\./gi, 'Réservation en cours. Merci de patienter...');
                        text = text.replace(/\bNo reservation times available\b/gi, 'Aucun créneau disponible');
                        text = text.replace(/\b1 person\b/gi, '1 personne');
                        text = text.replace(/\b(\d+) people\b/gi, '$1 personnes');
                        text = text.replace(/\b1 guest\b/gi, '1 personne');
                        text = text.replace(/\b(\d+) guests\b/gi, '$1 personnes');
                        text = to24Hour(text);
                        if (/Available reservation time slots on/i.test(text)) {
                            text = text.replace(/Available reservation time slots on/gi, 'Créneaux disponibles le');
                            text = text.replace(/\s+at\s+/gi, ' à ');
                            text = text.replace(/\s+for\s+/gi, ' pour ');
                        }
                        if (/Créneaux disponibles le/i.test(text)) {
                            text = text.replace(/\s+at\s+/gi, ' à ');
                            text = text.replace(/\s+à\s+00:00(?=\s+pour\b)/gi, '');
                        }

                        return text;
                    };

                document.querySelectorAll('.wok-booking-native label, .wok-booking-native option, .wok-booking-native button, .wok-booking-native a, .wok-booking-native p, .wok-booking-native h1, .wok-booking-native h4, .wok-booking-native h5')
                    .forEach((element) => {
                        element.childNodes.forEach((node) => {
                            if (node.nodeType !== Node.TEXT_NODE) return;

                            const text = localizeBookingText(node.textContent);
                            if (text !== node.textContent) node.textContent = text;
                        });
                    });

                force24HourText();

                document.querySelector('.wok-booking-native [aria-label="Month"]')?.setAttribute('aria-label', 'Mois');
                document.querySelector('.wok-booking-native [aria-label="Year"]')?.setAttribute('aria-label', 'Année');
                document.querySelectorAll('.wok-booking-native [aria-label="Telephone country code"]')
                    .forEach((element) => element.setAttribute('aria-label', 'Indicatif téléphonique du pays'));

                document.querySelectorAll('.wok-booking-native input[placeholder="First Name"]')
                    .forEach((element) => element.setAttribute('placeholder', 'Prénom'));
                document.querySelectorAll('.wok-booking-native input[placeholder="Last Name"]')
                    .forEach((element) => element.setAttribute('placeholder', 'Nom'));
                document.querySelectorAll('.wok-booking-native input[placeholder="Email Address"]')
                    .forEach((element) => element.setAttribute('placeholder', 'Adresse e-mail'));
                document.querySelectorAll('.wok-booking-native textarea[placeholder="Special Requests"]')
                    .forEach((element) => element.setAttribute('placeholder', 'Demandes particulières'));
                document.querySelectorAll('.wok-booking-native select[aria-label="Time"]')
                    .forEach((element) => element.setAttribute('aria-label', 'Heure souhaitée'));
                document.querySelectorAll('.wok-booking-native input[aria-label="Email Address"]')
                    .forEach((element) => element.setAttribute('aria-label', 'Adresse e-mail'));
                document.querySelectorAll('.wok-booking-native textarea[aria-label="Special Requests"]')
                    .forEach((element) => element.setAttribute('aria-label', 'Demandes particulières'));

                const telephoneInput = document.querySelector('.wok-booking-native input[type="tel"]');
                const telephoneInstance = telephoneInput
                    ? window.intlTelInputGlobals?.getInstance(telephoneInput)
                    : null;

                if (telephoneInstance && !telephoneInput.dataset.restolineCountrySet) {
                    telephoneInstance.setCountry('be');
                    telephoneInput.dataset.restolineCountrySet = 'true';
                }
            };

            localizeBooking();
            new MutationObserver(localizeBooking).observe(document.body, { childList: true, subtree: true });
            [250, 750, 1500, 3000, 5000].forEach((delay) => setTimeout(localizeBooking, delay));
            document.addEventListener('livewire:init', () => {
                window.Livewire?.hook?.('morph.updated', () => setTimeout(localizeBooking, 50));
                window.Livewire?.hook?.('commit', ({ succeed }) => succeed(() => setTimeout(localizeBooking, 50)));
            });
        })();


        (() => {
            const channels = document.querySelectorAll('[data-newsletter-channel]');
            const emailField = document.querySelector('[data-newsletter-email-field]');
            const phoneField = document.querySelector('[data-newsletter-phone-field]');
            if (!channels.length || !emailField || !phoneField) return;

            const sync = () => {
                const selected = document.querySelector('[data-newsletter-channel]:checked')?.value || 'email';
                const showEmail = selected === 'email';

                emailField.hidden = !showEmail;
                phoneField.hidden = showEmail;
                emailField.querySelector('input')?.toggleAttribute('required', showEmail);
                phoneField.querySelector('input')?.toggleAttribute('required', !showEmail);
            };

            channels.forEach((channel) => channel.addEventListener('change', sync));
            sync();
        })();


        (() => {
            const popup = document.querySelector('[data-home-popup]');
            if (!popup) return;

            const popupId = popup.dataset.popupId || 'default';
            const dismissHours = Number(popup.dataset.dismissHours || 12);
            const storageKey = `wok-home-popup-${popupId}`;
            const now = Date.now();

            if (dismissHours > 0) {
                try {
                    const dismissedUntil = Number(localStorage.getItem(storageKey) || 0);
                    if (dismissedUntil && dismissedUntil > now) {
                        popup.hidden = true;
                        return;
                    }
                } catch (error) {}
            } else {
                try {
                    localStorage.removeItem(storageKey);
                } catch (error) {}
            }

            document.body.classList.add('wok-popup-open');

            const close = () => {
                popup.hidden = true;
                document.body.classList.remove('wok-popup-open');
                if (dismissHours > 0) {
                    try {
                        localStorage.setItem(storageKey, String(now + dismissHours * 60 * 60 * 1000));
                    } catch (error) {}
                }
            };

            popup.querySelectorAll('[data-home-popup-close]').forEach((element) => {
                element.addEventListener('click', close);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !popup.hidden) close();
            });
        })();
    </script>
</body>
</html>
