window.initWokDasieTheme = () => {
  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('[data-menu-toggle]');
  const navigation = document.querySelector('[data-navigation]');

  const initNewsletterChannel = () => {
    const channels = document.querySelectorAll('[data-newsletter-channel]');
    const emailField = document.querySelector('[data-newsletter-email-field]');
    const phoneField = document.querySelector('[data-newsletter-phone-field]');
    if (!channels.length || !emailField || !phoneField) return;

    const sync = () => {
      const selected = document.querySelector('[data-newsletter-channel]:checked')?.value || 'email';
      emailField.hidden = selected !== 'email';
      phoneField.hidden = selected !== 'sms';
      emailField.querySelector('input')?.toggleAttribute('required', selected === 'email');
      phoneField.querySelector('input')?.toggleAttribute('required', selected === 'sms');
    };

    channels.forEach((channel) => channel.addEventListener('change', sync));
    sync();
  };

  const initLightbox = () => {
    if (document.body.dataset.lightboxReady === 'true') return;
    document.body.dataset.lightboxReady = 'true';

    const lightbox = document.createElement('div');
    lightbox.className = 'wok-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Image agrandie');
    lightbox.innerHTML = '<button type="button" aria-label="Fermer">x</button><img alt="">';
    document.body.appendChild(lightbox);

    const image = lightbox.querySelector('img');
    const closeButton = lightbox.querySelector('button');
    let lastFocus = null;

    const open = (src, alt, trigger) => {
      lastFocus = trigger;
      image.src = src;
      image.alt = alt || 'Image agrandie';
      lightbox.classList.add('is-open');
      closeButton.focus();
    };

    const close = () => {
      lightbox.classList.remove('is-open');
      image.removeAttribute('src');
      lastFocus?.focus?.();
    };

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-lightbox-src], .wok-rich-content img');
      if (!trigger) return;

      event.preventDefault();
      const src = trigger.dataset.lightboxSrc || trigger.currentSrc || trigger.src;
      const alt = trigger.querySelector?.('img')?.alt || trigger.alt || '';
      if (src) open(src, alt, trigger);
    });

    closeButton.addEventListener('click', close);
    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox) close();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && lightbox.classList.contains('is-open')) close();
    });
  };

  initLightbox();
  initNewsletterChannel();

  if (!toggle || !navigation || toggle.dataset.ready === 'true') return;

  toggle.dataset.ready = 'true';

  const closeMenu = () => {
    navigation.classList.remove('is-open');
    toggle.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Ouvrir le menu');
  };

  const updateHeader = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 24);
  };

  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = navigation.classList.toggle('is-open');
    toggle.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
  });

  navigation.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', (event) => {
    if (!navigation.contains(event.target) && !toggle.contains(event.target)) closeMenu();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
      toggle.focus();
    }
  });

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
};

window.initWokDasieTheme();
document.addEventListener('DOMContentLoaded', window.initWokDasieTheme, { once: true });
