@php($gm = \Restoline\Content\Support\PublicCopy::class)
<nav class="gm-langs" aria-label="{{ $gm::line('lang.label') }}">
    @foreach (['fr' => 'FR', 'en' => 'EN', 'nl' => 'NL'] as $code => $label)
        @if (app()->getLocale() === $code)
            <a href="{{ url('/locale/'.$code) }}" aria-current="true" lang="{{ $code }}">{{ $label }}</a>
        @else
            <a href="{{ url('/locale/'.$code) }}" lang="{{ $code }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
