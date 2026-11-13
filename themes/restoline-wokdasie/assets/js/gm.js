const GM = window.GM || { t: {} };
const t = (key, fallback) => {
    const value = GM.t && GM.t[key];
    return (value == null || value === '') ? (fallback ?? key) : value;
};

const root = document.documentElement;
const motionButton = document.querySelector('#motion-toggle');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let motionPaused = false;
try { motionPaused = localStorage.getItem('muraille-motion') === 'off'; } catch (error) {}
const motionOn = () => root.dataset.motion === 'on';
let strip = null;

function syncMotion() {
    const off = motionPaused || reducedMotion.matches;
    root.dataset.motion = off ? 'off' : 'on';
    if (motionButton) {
        motionButton.hidden = false;
        motionButton.disabled = reducedMotion.matches;
        motionButton.setAttribute('aria-pressed', String(off));
        motionButton.textContent = reducedMotion.matches
            ? t('motion.reduced', 'Animations réduites (préférence système)')
            : off
                ? t('motion.play', 'Activer les animations')
                : t('motion.pause', 'Mettre les animations en pause');
    }
    const auto = strip && strip.plugins().autoScroll;
    if (auto) {
        off ? auto.stop() : auto.play();
    }
    if (off) {
        document.querySelectorAll('[data-speed],[data-scrollscale]').forEach((el) => {
            el.style.transform = '';
            el.style.removeProperty('--sc');
        });
    } else {
        requestTick();
    }
}

if (motionButton) {
    motionButton.addEventListener('click', () => {
        motionPaused = !motionPaused;
        try { localStorage.setItem('muraille-motion', motionPaused ? 'off' : 'on'); } catch (error) {}
        syncMotion();
    });
}
reducedMotion.addEventListener('change', syncMotion);

document.querySelectorAll('[data-stagger]').forEach((group) => {
    [...group.querySelectorAll('[data-reveal]')].forEach((el, index) => {
        el.style.setProperty('--stagger', `${index * 120}ms`);
    });
});

const revealAll = () => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-in'));
if ('IntersectionObserver' in window && !reducedMotion.matches) {
    const io = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
        }
    }, { threshold: 0.12, rootMargin: '0px 0px -4% 0px' });
    document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
} else {
    revealAll();
}

const nav = document.querySelector('#nav');
const parallax = [...document.querySelectorAll('[data-speed]')];
const scalers = [...document.querySelectorAll('[data-scrollscale]')];
let ticking = false;

function onFrame() {
    ticking = false;
    const y = window.scrollY;
    const vh = window.innerHeight;
    nav?.classList.toggle('is-scrolled', y > 40);
    if (!motionOn()) return;
    for (const el of parallax) {
        const rect = el.getBoundingClientRect();
        if (rect.bottom < -200 || rect.top > vh + 200) continue;
        const center = rect.top + rect.height / 2 - vh / 2;
        el.style.transform = `translate3d(0,${(center * parseFloat(el.dataset.speed)).toFixed(1)}px,0)`;
    }
    for (const el of scalers) {
        if (el.matches(':hover')) continue;
        const rect = el.getBoundingClientRect();
        const center = Math.abs(rect.top + rect.height / 2 - vh * 0.55) / (vh * 0.55);
        const scale = 1.06 - Math.min(center, 1) * 0.1;
        el.style.setProperty('--sc', scale.toFixed(3));
    }
}

function requestTick() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(onFrame);
}

window.addEventListener('scroll', requestTick, { passive: true });
window.addEventListener('resize', requestTick);
scalers.forEach((el) => {
    el.addEventListener('mouseenter', () => el.style.removeProperty('--sc'));
    el.addEventListener('mouseleave', requestTick);
});

function initStrip() {
    const viewport = document.querySelector('#strip');
    if (!viewport || typeof EmblaCarousel !== 'function') return;
    const plugins = typeof EmblaCarouselAutoScroll === 'function'
        ? [EmblaCarouselAutoScroll({
            speed: 0.7,
            startDelay: 600,
            stopOnInteraction: false,
            stopOnMouseEnter: true,
            playOnInit: motionOn(),
        })]
        : [];
    strip = EmblaCarousel(viewport, { loop: true, dragFree: true, align: 'start', containScroll: false }, plugins);
    viewport.classList.add('embla-on');
    strip.on('pointerDown', () => viewport.classList.add('is-dragging'));
    strip.on('pointerUp', () => viewport.classList.remove('is-dragging'));
    const auto = strip.plugins().autoScroll;
    const step = (dir) => {
        if (auto) auto.stop();
        dir < 0 ? strip.scrollPrev() : strip.scrollNext();
        if (auto && motionOn()) {
            setTimeout(() => { if (!viewport.matches(':hover')) auto.play(); }, 2500);
        }
    };
    document.querySelector('#gal-prev')?.addEventListener('click', () => step(-1));
    document.querySelector('#gal-next')?.addEventListener('click', () => step(1));
    viewport.addEventListener('focusin', () => { if (auto) auto.stop(); });
    viewport.addEventListener('focusout', () => { if (auto && motionOn()) auto.play(); });
    viewport.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') step(-1);
        if (event.key === 'ArrowRight') step(1);
    });
    viewport.tabIndex = 0;
}

const toggle = document.querySelector('#nav-toggle');
const drawer = document.querySelector('#nav-drawer');

function setDrawer(open) {
    if (!toggle || !drawer) return;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? t('menu.close', 'Fermer le menu') : t('menu.open', 'Ouvrir le menu'));
    drawer.classList.toggle('is-open', open);
    if (open) drawer.removeAttribute('hidden');
    else drawer.setAttribute('hidden', '');
    document.body.classList.toggle('gm-nav-open', open);
}

if (toggle && drawer) {
    toggle.addEventListener('click', () => setDrawer(toggle.getAttribute('aria-expanded') !== 'true'));
    drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setDrawer(false)));
    window.addEventListener('keydown', (event) => { if (event.key === 'Escape') setDrawer(false); });
}

document.querySelectorAll('.gm-langs a').forEach((link) => {
    link.addEventListener('click', () => {
        const hash = location.hash;
        if (!hash) return;
        const url = new URL(link.href, location.origin);
        url.hash = hash;
        link.href = url.pathname + url.search + url.hash;
    });
});

syncMotion();
initStrip();
requestTick();
