@php
    $returnPath = preg_replace('#^'.preg_quote(trim(\Igniter\Flame\Support\Facades\Igniter::adminUri(), '/'), '#').'/?#', '', request()->path());
    $current = app()->getLocale();
    $choices = [
        'fr' => ['label' => 'FR', 'title' => lang('restoline.content::default.admin_language.switch_to_french')],
        'en' => ['label' => 'EN', 'title' => lang('restoline.content::default.admin_language.switch_to_english')],
        'zh_CN' => ['label' => '中文', 'title' => lang('restoline.content::default.admin_language.switch_to_chinese')],
    ];
@endphp
<li class="nav-item d-flex align-items-center gap-1">
    @foreach ($choices as $locale => $choice)
        @if ($current === $locale)
            <span class="nav-link fw-semibold" aria-current="true" data-testid="admin-language-current">{{ $choice['label'] }}</span>
        @else
            <form action="{{ admin_url('language/'.$locale) }}" method="post">
                @csrf
                <input type="hidden" name="return_path" value="{{ $returnPath ?: 'dashboard' }}">
                <button
                    class="nav-link border-0 bg-transparent fw-semibold"
                    type="submit"
                    title="{{ $choice['title'] }}"
                    aria-label="{{ $choice['title'] }}"
                    data-testid="admin-language-switcher"
                >{{ $choice['label'] }}</button>
            </form>
        @endif
    @endforeach
</li>
