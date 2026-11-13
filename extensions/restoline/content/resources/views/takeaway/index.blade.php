<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commander a emporter | La Grande Muraille de Chine</title>
    <style>
        :root { color-scheme: light; --ink:#19251f; --green:#176348; --gold:#b37c1e; --paper:#fffdf7; --line:#e6ddca; --muted:#647067; }
        * { box-sizing:border-box; } body { margin:0; background:#f7f3e9; color:var(--ink); font:16px/1.45 Arial,sans-serif; }
        .hero { background:linear-gradient(90deg,rgba(17,58,45,.96),rgba(32,92,65,.88)),url('/vendor/restoline-wokdasie/images/emporter-wokdasie.jpg') center/cover; color:white; padding:42px 20px; }
        .hero-inner,.page { max-width:1180px; margin:auto; } .eyebrow { color:#f3cd7e; font-weight:700; letter-spacing:.08em; text-transform:uppercase; font-size:.78rem; }
        h1 { font:700 clamp(2rem,5vw,3.7rem)/1.05 Georgia,serif; margin:8px 0; } h2 { font:700 1.55rem/1.2 Georgia,serif; margin:0; }
        .hero p { max-width:620px; margin:0; font-size:1.05rem; } .page { padding:28px 20px 100px; }
        .notice { margin:0 0 22px; border-radius:8px; padding:14px 16px; } .success { background:#e5f4eb; color:#145538; } .error { background:#f8e7e7; color:#8c1f1f; } .testing { background:#fff3cf; color:#684700; border:1px solid #e8cb7c; }
        .layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:26px; align-items:start; }
        .category { margin:0 0 30px; scroll-margin-top:16px; } .category-head { display:flex; align-items:end; justify-content:space-between; border-bottom:2px solid var(--gold); padding-bottom:10px; margin-bottom:12px; }
        .category-head span { color:var(--muted); font-size:.9rem; }
        .menu-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .menu-item { background:var(--paper); border:1px solid var(--line); border-radius:8px; padding:15px; display:grid; gap:10px; }
        .menu-title { font-weight:700; } .price { color:var(--green); font-weight:800; font-size:1.1rem; }
        .menu-option { display:grid; gap:5px; } .menu-option label { color:var(--muted); font-size:.82rem; font-weight:700; } .menu-option select { width:100%; border:1px solid var(--line); border-radius:6px; background:#fff; color:var(--ink); padding:9px; }
        .add { border:0; border-radius:6px; background:var(--green); color:#fff; padding:10px 12px; font-weight:700; cursor:pointer; width:100%; }
        .add:hover { background:#104d37; } .cart { position:sticky; top:16px; background:#1d2c25; color:white; padding:20px; border-radius:8px; box-shadow:0 15px 34px rgba(25,37,31,.18); }
        .cart h2 { color:#fff; } .cart-list { list-style:none; padding:0; margin:15px 0; display:grid; gap:10px; } .cart-list li { border-bottom:1px solid rgba(255,255,255,.16); padding-bottom:10px; }
        .cart-name { display:block; font-weight:700; } .cart-summary { color:#dfe8e2; font-size:.92rem; display:block; } .cart-options { color:#f3cd7e; font-size:.84rem; margin-top:3px; display:block; } .cart-option { display:inline; } .cart-option + .cart-option::before { content:' · '; } .cart-actions { display:flex; gap:7px; margin-top:9px; } .cart-actions form { margin:0; } .cart-actions button { border:1px solid rgba(255,255,255,.35); border-radius:5px; background:transparent; color:#fff; padding:4px 8px; cursor:pointer; font-weight:700; } .cart-actions button:hover { background:rgba(255,255,255,.12); } .cart-actions .remove { color:#ffd2ca; }
        .cart-total { border-top:1px solid rgba(255,255,255,.25); margin-top:15px; padding-top:15px; font-size:1.2rem; font-weight:800; display:flex; justify-content:space-between; }
        .checkout { display:block; text-align:center; text-decoration:none; border-radius:6px; margin-top:16px; padding:13px; font-weight:800; background:#e2ae45; color:#1c271f; } .checkout:hover { background:#f1c566; }
        .meta { font-size:.9rem; color:#cbd9d0; margin:14px 0 0; } .closed { color:#ffe0a1; font-weight:700; }
        @media(max-width:820px) { .layout { grid-template-columns:1fr; } .cart { position:static; order:-1; } .menu-grid { grid-template-columns:1fr; } .hero { padding:32px 20px; } }
    </style>
</head>
<body>
<header class="hero"><div class="hero-inner"><div class="eyebrow">La Grande Muraille de Chine</div><h1>Commander a emporter</h1><p>Choisissez vos plats, puis indiquez votre heure de retrait. Paiement au restaurant lors de la remise de votre commande.</p></div></header>
<main class="page">
    <p class="notice testing"><strong>Version de test interne.</strong> La carte et le parcours de commande sont en verification. Ne passez pas encore de commande reelle.</p>
    @if(session('takeaway_success'))<p class="notice success">{{ session('takeaway_success') }}</p>@endif
    @if(session('takeaway_error'))<p class="notice error">{{ session('takeaway_error') }}</p>@endif
    <div class="layout">
        <section>
            @forelse($categories as $category)
                <section class="category"><div class="category-head"><h2>{{ str_replace('Emporter | ', '', $category->name) }}</h2><span>{{ $category->menus->count() }} plats</span></div>
                <div class="menu-grid">@foreach($category->menus as $menu)<article class="menu-item"><div class="menu-title">{{ $menu->menu_name }}</div><div class="price">{{ number_format((float)$menu->menu_price, 2, ',', ' ') }} EUR</div><form method="post" action="{{ route('restoline.takeaway.add') }}">@csrf<input type="hidden" name="menu_id" value="{{ $menu->getKey() }}">@foreach($menu->menu_options->sortBy('priority') as $menuOption)<div class="menu-option"><label for="option-{{ $menu->getKey() }}-{{ $menuOption->getKey() }}">{{ str_replace('Emporter - ', '', $menuOption->option_name) }}@if($menuOption->is_required) * @endif</label><select id="option-{{ $menu->getKey() }}-{{ $menuOption->getKey() }}" name="menu_options[{{ $menuOption->getKey() }}][option_values][]" @if($menuOption->is_required) required @endif><option value="">Choisir</option>@foreach($menuOption->menu_option_values->sortBy('priority') as $optionValue)<option value="{{ $optionValue->getKey() }}">{{ $optionValue->name }}</option>@endforeach</select></div>@endforeach<button class="add" type="submit">Ajouter</button></form></article>@endforeach</div></section>
            @empty
                <p class="notice error">La carte emporter est en preparation. Aucun produit n'est encore disponible a la commande.</p>
            @endforelse
        </section>
        <aside class="cart"><h2>Votre commande</h2>
            @if($cartItems->isEmpty())<p class="meta">Aucun plat ajoute pour le moment.</p>@else<ul class="cart-list">@foreach($cartItems as $item)<li><span class="cart-name">{{ $item->name }}</span><span class="cart-summary">{{ $item->qty }} x {{ number_format((float)$item->price, 2, ',', ' ') }} EUR</span>@if($item->options->isNotEmpty())<span class="cart-options">@foreach($item->options as $option)@foreach($option->values as $value)<span class="cart-option">{{ $value->name }}</span>@endforeach@endforeach</span>@endif<div class="cart-actions"><form method="post" action="{{ route('restoline.takeaway.update-cart', ['rowId' => $item->rowId]) }}">@csrf<input type="hidden" name="action" value="minus"><button type="submit" aria-label="Diminuer la quantite">-</button></form><form method="post" action="{{ route('restoline.takeaway.update-cart', ['rowId' => $item->rowId]) }}">@csrf<input type="hidden" name="action" value="plus"><button type="submit" aria-label="Augmenter la quantite">+</button></form><form method="post" action="{{ route('restoline.takeaway.update-cart', ['rowId' => $item->rowId]) }}">@csrf<input type="hidden" name="action" value="remove"><button class="remove" type="submit">Retirer</button></form></div></li>@endforeach</ul><div class="cart-total"><span>Total</span><span>{{ number_format((float)$cartTotal, 2, ',', ' ') }} EUR</span></div><a class="checkout" href="/checkout">Choisir le retrait</a>@endif
            <p class="meta @if(!$collectionOpen) closed @endif">@if($collectionOpen) Retrait sur place disponible selon les creneaux proposes. @else Les commandes immediates sont actuellement fermees. @endif</p>
        </aside>
    </div>
</main>
</body>
</html>
