<footer class="wok-footer">
    <div id="contact" class="wok-footer-grid">
        <div>
            <p class="wok-eyebrow">La Grande Muraille de Chine</p>
            <h2>@lang('restoline.content::frontend.footer.tagline')</h2>
        </div>
        <div>
            <h3>@lang('restoline.content::frontend.footer.address')</h3>
            <p>
                <span>Avenue Reine Astrid 98</span><br>
                <span>4900 Spa</span>
            </p>
            <p><a class="wok-footer-map-link" href="https://www.google.com/maps/dir/?api=1&destination=Avenue+Reine+Astrid+98%2C+4900+Spa" target="_blank" rel="noopener noreferrer">@lang('restoline.content::frontend.footer.directions')</a></p>
        </div>
        <div>
            <h3>@lang('restoline.content::frontend.footer.contact')</h3>
            <p><a href="tel:+3287220888">087 22 08 88</a></p>
        </div>
        <div>
            <h3>@lang('restoline.content::frontend.footer.hours')</h3>
            <p>
                <span>@lang('restoline.content::frontend.footer.days')</span><br>
                <span>@lang('restoline.content::frontend.footer.slots')</span><br>
                <span>@lang('restoline.content::frontend.footer.closed')</span>
            </p>
        </div>
    </div>
    <div class="wok-footer-bottom">
        <span>© {{ date('Y') }} La Grande Muraille de Chine · SRL JIN-ZHOU · TVA BE 0812011942</span>
        <span><a href="{{ url('/confidentialite') }}">@lang('restoline.content::frontend.footer.privacy')</a> · <a href="https://www.restoline.be/" target="_blank" rel="noopener noreferrer">@lang('restoline.content::frontend.footer.powered')</a></span>
    </div>
</footer>
