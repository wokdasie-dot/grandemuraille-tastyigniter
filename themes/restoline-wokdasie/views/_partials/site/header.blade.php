<a class="wok-skip-link" href="#contenu">@lang('restoline.content::frontend.nav.skip')</a>
<header class="wok-header" data-header>
    <div class="wok-header-inner">
        <a class="wok-brand" href="{{ url('/') }}" aria-label="@lang('restoline.content::frontend.nav.home_aria')">
            <span class="brand-seal" aria-hidden="true">长城</span>
            <span><strong>La Grande Muraille</strong><small>@lang('restoline.content::frontend.brand.tagline')</small></span>
        </a>

        <button class="wok-menu-toggle" type="button" aria-expanded="false" aria-controls="wok-navigation" aria-label="@lang('restoline.content::frontend.nav.open')" data-menu-toggle>
            <span></span><span></span><span></span>
            <span class="visually-hidden">@lang('restoline.content::frontend.nav.open')</span>
        </button>

        <nav id="wok-navigation" class="wok-nav" aria-label="Navigation principale" data-navigation>
            <a href="{{ url('/') }}#menus">@lang('restoline.content::frontend.nav.menus')</a>
            <a href="{{ url('/') }}#tarifs">@lang('restoline.content::frontend.nav.buffet')</a>
            <a href="{{ url('/') }}#horaires">@lang('restoline.content::frontend.nav.hours')</a>
            <a href="{{ url('/') }}#contact">@lang('restoline.content::frontend.nav.contact')</a>
            <div class="wok-lang-switch wok-lang-switch-mobile">
                @themePartial('site.language-switch')
            </div>
        </nav>

        <div class="wok-header-actions">
            @themePartial('site.language-switch')
            <a class="wok-manage-button" href="{{ url('/reservation/gerer') }}">@lang('restoline.content::frontend.nav.manage')</a>
            <a class="wok-reserve-button" href="{{ url('/grande-muraille/reservation') }}">@lang('restoline.content::frontend.nav.reserve')</a>
        </div>
    </div>
</header>
