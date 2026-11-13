<a class="wok-skip-link" href="#contenu">Aller au contenu</a>
<header class="wok-header" data-header>
    <div class="wok-header-inner">
        <a class="wok-brand" href="{{ url('/') }}" aria-label="La Grande Muraille de Chine, accueil">
            <span class="brand-seal" aria-hidden="true">长城</span>
            <span><strong>La Grande Muraille</strong><small>Cuisine chinoise · Spa</small></span>
        </a>

        <button class="wok-menu-toggle" type="button" aria-expanded="false" aria-controls="wok-navigation" aria-label="Ouvrir le menu" data-menu-toggle>
            <span></span><span></span><span></span>
            <span class="visually-hidden">Ouvrir le menu</span>
        </button>

        <nav id="wok-navigation" class="wok-nav" aria-label="Navigation principale" data-navigation>
            <a href="{{ url('/') }}#menus">Menus</a>
            <a href="{{ url('/') }}#tarifs">Buffet</a>
            <a href="{{ url('/') }}#horaires">Horaires</a>
            <a href="{{ url('/') }}#contact">Contact</a>
        </nav>

        <div class="wok-header-actions">
            <a class="wok-manage-button" href="{{ url('/reservation/gerer') }}">Gérer ma réservation</a>
            <a class="wok-reserve-button" href="{{ url('/grande-muraille/reservation') }}">Réserver</a>
        </div>
    </div>
</header>
