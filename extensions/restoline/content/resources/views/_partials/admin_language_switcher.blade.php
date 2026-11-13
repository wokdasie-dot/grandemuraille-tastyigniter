@php
    $targetLocale = $item->config['targetLocale'];
    $returnPath = preg_replace('#^'.preg_quote(trim(\Igniter\Flame\Support\Facades\Igniter::adminUri(), '/'), '#').'/?#', '', request()->path());
@endphp
<li class="nav-item">
    <form action="{{ admin_url('language/'.$targetLocale) }}" method="post">
        @csrf
        <input type="hidden" name="return_path" value="{{ $returnPath ?: 'dashboard' }}">
        <button
            class="nav-link border-0 bg-transparent fw-semibold"
            type="submit"
            title="{{ $item->config['title'] }}"
            aria-label="{{ $item->config['title'] }}"
            data-testid="admin-language-switcher"
        >{{ $item->config['label'] }}</button>
    </form>
</li>
