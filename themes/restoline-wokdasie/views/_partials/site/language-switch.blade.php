@php($gm = \Restoline\Content\Support\PublicCopy::class)
<nav class="gm-langs" aria-label="{{ $gm::line('lang.label') }}">
    @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
        @if (app()->getLocale() === $code)
            <a href="{{ \Restoline\Content\Http\Controllers\PublicLocaleController::switchUrl($code) }}" aria-current="true" lang="{{ $code }}">{{ $label }}</a>
        @else
            <a href="{{ \Restoline\Content\Http\Controllers\PublicLocaleController::switchUrl($code) }}" lang="{{ $code }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
