<nav class="wok-lang-switch" aria-label="@lang('restoline.content::frontend.lang.label')">
    @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
        @if (app()->getLocale() === $code)
            <span aria-current="true">{{ $label }}</span>
        @else
            <a href="{{ url('/locale/'.$code) }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
