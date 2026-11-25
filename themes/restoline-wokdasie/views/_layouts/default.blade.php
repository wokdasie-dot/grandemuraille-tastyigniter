<!doctype html>
<html lang="{{ App::getLocale() }}" class="h-100" data-motion="on">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $canonicalUrl = url()->current();
        $requestPath = trim(request()->path(), '/');
        $pageTitle = lang('restoline.content::frontend.meta.title');
        $pageDescription = lang('restoline.content::frontend.meta.description');
        if (str_contains($requestPath, 'reservation/success')) {
            $pageTitle = lang('restoline.content::frontend.success.meta_title');
            $pageDescription = lang('restoline.content::frontend.success.meta_description');
        } elseif (str_contains($requestPath, 'reservation')) {
            $pageTitle = lang('restoline.content::frontend.booking.meta_title');
            $pageDescription = lang('restoline.content::frontend.booking.meta_description');
        } elseif (str_contains($requestPath, 'confidentialite')) {
            $pageTitle = lang('restoline.content::frontend.privacy.meta_title');
            $pageDescription = lang('restoline.content::frontend.privacy.meta_description');
        } elseif ($requestPath === 'carte' || str_ends_with($requestPath, '/carte')) {
            $pageTitle = \Restoline\Content\Support\PublicCopy::line('carte.meta.title') ?: 'Carte | La Grande Muraille de Chine';
            $pageDescription = \Restoline\Content\Support\PublicCopy::line('carte.meta.description');
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:wght@400;500;600&family=Noto+Serif+SC:wght@600&display=swap" rel="stylesheet">
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
            'servesCuisine' => ['Cuisine chinoise', 'Buffet'],
            'priceRange' => '€€',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Avenue Reine Astrid 98',
                'postalCode' => '4900',
                'addressLocality' => 'Spa',
                'addressRegion' => 'Liege',
                'addressCountry' => 'BE',
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '12:00',
                    'closes' => '14:00',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '18:00',
                    'closes' => '22:00',
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($restaurantSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @if($turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @themeStyles
    @if (str_contains((string)($this->page->bodyClass ?? ''), 'page-home'))
        <link rel="preload" as="image" href="{{ asset('vendor/restoline-wokdasie/images/gm/hero-dimsum-m.webp') }}" type="image/webp" media="(max-width: 760px)" fetchpriority="high">
        <link rel="preload" as="image" href="{{ asset('vendor/restoline-wokdasie/images/gm/hero-dimsum.webp') }}" type="image/webp" media="(min-width: 761px)" fetchpriority="high">
    @endif
    <link rel="stylesheet" href="{{ asset('vendor/restoline-wokdasie/css/gm.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/restoline-wokdasie/css/gm-ti.css') }}">
    @livewireStyles
</head>
<body class="gm-site {{ $this->page->bodyClass ?? '' }}">
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


        window.GM = @json(['t' => \Restoline\Content\Support\PublicCopy::script()]);
    </script>
    <script src="{{ asset('vendor/restoline-wokdasie/js/embla-carousel.umd.js') }}" defer></script>
    <script src="{{ asset('vendor/restoline-wokdasie/js/embla-carousel-auto-scroll.umd.js') }}" defer></script>
    <script src="{{ asset('vendor/restoline-wokdasie/js/gm.js') }}" defer></script>
    <script>
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
