<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buffet à table - La Grande Muraille de Chine</title>
    <style>
        :root { color-scheme: light; --gold:#c99a3f; --gold-dark:#8b611c; --ink:#17130e; --muted:#70685d; --line:#efe7da; --cream:#fffaf1; --bg:#120f0c; --green:#166534; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: radial-gradient(circle at top, #382116 0, #120f0c 46%, #070605 100%); color: #fff; }
        .page { min-height: 100vh; padding: 18px; }
        .card { width: min(980px, 100%); margin: 0 auto; background: linear-gradient(180deg, rgba(255,250,241,.99), rgba(255,255,255,.97)); color: var(--ink); border-radius: 24px; padding: 24px; box-shadow: 0 24px 80px rgba(0,0,0,.42); border: 1px solid rgba(201,154,63,.28); }
        .login-card { margin-top: min(14vh, 90px); max-width: 440px; }
        .logo { width: 72px; height: 72px; object-fit: contain; display: block; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; max-width: 820px; font-size: 42px; line-height: 1.08; letter-spacing: 0; }
        h2 { margin: 0; font-size: 24px; color: var(--ink); }
        p { margin: 0 0 18px; color: var(--muted); line-height: 1.5; }
        label { display: block; font-weight: 800; margin: 16px 0 6px; }
        input, textarea { width: 100%; border: 1px solid #d8c9ae; border-radius: 13px; padding: 15px; font-size: 18px; background: #fff; font: inherit; }
        textarea { min-height: 82px; resize: vertical; font-size: 15px; }
        button { border: 0; border-radius: 999px; padding: 13px 16px; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: #fff; font-size: 16px; font-weight: 900; box-shadow: 0 10px 26px rgba(139,97,28,.28); cursor: pointer; }
        .login-button, .submit-order { width: 100%; margin-top: 16px; padding: 15px 18px; font-size: 17px; }
        .alert { border-radius: 14px; padding: 12px 14px; margin-bottom: 16px; }
        .success { background: #e8f8ef; color: #14532d; }
        .error { background: #fde8e8; color: #7f1d1d; }
        .topbar { display: flex; gap: 14px; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
        .home-link { display: inline-flex; align-items: center; margin: 0 0 18px; color: var(--ink); font-size: 14px; font-weight: 800; text-decoration: none; }
        .home-link:hover { color: var(--gold-dark); text-decoration: underline; text-underline-offset: 3px; }
        .intro-copy { max-width: 820px; }
        .intro-copy p { margin-bottom: 12px; }
        .intro-copy .intro-lead { color: var(--ink); font-size: 18px; font-weight: 800; }
        .intro-copy .waste-note { margin-bottom: 0; padding: 12px 14px; border-left: 4px solid var(--gold); background: #fff8e8; color: #5d461c; }
        .eyebrow { color: var(--gold-dark); font-weight: 900; text-transform: uppercase; letter-spacing: .12em; font-size: 12px; margin-bottom: 8px; }
        .table-pill { border: 1px solid rgba(201,154,63,.48); background: rgba(201,154,63,.14); color: #704c13; border-radius: 999px; padding: 10px 14px; font-weight: 900; white-space: nowrap; }
        .category-nav { display: flex; gap: 8px; overflow-x: auto; padding: 7px 0 16px; margin: 0 -2px 12px; position: sticky; top: 0; background: linear-gradient(180deg, rgba(255,250,241,.99), rgba(255,250,241,.94)); z-index: 3; backdrop-filter: blur(10px); }
        .category-nav a { flex: 0 0 auto; color: var(--ink); border: 1px solid #e2d2b5; background: #fff; border-radius: 999px; padding: 10px 13px; text-decoration: none; font-size: 14px; font-weight: 800; }
        .category { margin-top: 24px; scroll-margin-top: 72px; }
        .category-head { display: grid; grid-template-columns: 1fr minmax(120px, 220px); gap: 16px; align-items: center; padding: 16px; border: 1px solid var(--line); border-radius: 20px; background: rgba(255,255,255,.72); }
        .category-desc { margin: 7px 0 0; font-size: 14px; color: var(--muted); }
        .category-hero { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; border-radius: 16px; background: #f5efe4; box-shadow: inset 0 0 0 1px rgba(0,0,0,.06); }
        .menu-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 12px; }
        .menu-item { display: grid; grid-template-columns: 104px 1fr; gap: 12px; min-height: 132px; padding: 10px; border: 1px solid var(--line); border-radius: 18px; background: #fff; box-shadow: 0 8px 22px rgba(76,45,10,.06); }
        .menu-photo { width: 104px; height: 112px; object-fit: cover; border-radius: 14px; background: #f4efe7; align-self: start; }
        .menu-content { min-width: 0; display: flex; flex-direction: column; gap: 7px; }
        .menu-name { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-weight: 900; line-height: 1.22; font-size: 15px; }
        .portion-badge { display: inline-flex; align-items: center; border: 1px solid #eadcc5; border-radius: 999px; padding: 3px 7px; color: #704c13; background: #fff8e8; font-size: 11px; font-weight: 800; white-space: nowrap; }
        .menu-desc { font-size: 12px; color: var(--muted); line-height: 1.35; }
        .price { color: var(--green); background: #e8f8ef; border-radius: 999px; padding: 5px 9px; font-weight: 900; font-size: 12px; align-self: flex-start; white-space: nowrap; margin-top: auto; }
        .qty-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
        .qty-btn { width: 38px; height: 38px; padding: 0; display: grid; place-items: center; font-size: 22px; line-height: 1; box-shadow: none; }
        .qty-value { min-width: 28px; text-align: center; font-size: 18px; font-weight: 900; }
        .cart-panel { position: sticky; bottom: 10px; z-index: 4; margin-top: 22px; border: 1px solid rgba(201,154,63,.45); border-radius: 20px; background: rgba(23,19,14,.94); color: #fff; padding: 14px; box-shadow: 0 16px 50px rgba(0,0,0,.34); }
        .cart-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-weight: 900; margin-bottom: 0; }
        .cart-empty { color: rgba(255,255,255,.72); font-size: 14px; }
        .cart-list { display: none; gap: 7px; margin: 10px 0 12px; padding: 0; list-style: none; max-height: 110px; overflow: auto; }
        .cart-list li { font-size: 14px; color: rgba(255,255,255,.9); }
        .cart-panel label { color: rgba(255,255,255,.88); margin-top: 10px; }
        .checkout-step { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,.16); }
        .cart-panel.is-checkout .cart-list { display: grid; }
        .cart-panel.is-checkout .checkout-step { display: block; }
        .checkout-help { margin: 0 0 10px; color: rgba(255,255,255,.72); font-size: 13px; line-height: 1.4; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .checkout-grid input { font-size: 16px; padding: 12px; }
        .submit-order:disabled { opacity: .48; cursor: not-allowed; }
        .last-order { border: 1px solid #b7e4c7; background: #e8f8ef; color: #14532d; border-radius: 16px; padding: 14px; margin-bottom: 18px; }
        .table-session { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid rgba(22,101,52,.2); background: #e8f8ef; color: #14532d; border-radius: 16px; padding: 12px 14px; margin-bottom: 14px; }
        .table-session strong { display: block; font-size: 16px; }
        .table-session a { color: #14532d; font-weight: 800; white-space: nowrap; }
        .client-orders { border: 1px solid var(--line); border-radius: 18px; padding: 14px; margin-bottom: 18px; background: rgba(255,255,255,.78); }
        .client-orders h2 { font-size: 20px; margin-bottom: 10px; }
        .client-order-list { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
        .client-order { border: 1px solid #eadcc5; border-radius: 14px; padding: 12px; background: #fff; }
        .client-order-head { display: flex; justify-content: space-between; gap: 10px; font-weight: 900; margin-bottom: 6px; }
        .client-order-head a { color: var(--ink); text-decoration: underline; text-underline-offset: 3px; }
        .client-order-meta { color: var(--muted); font-size: 13px; margin-bottom: 6px; }
        .client-order-items { color: #312820; font-size: 14px; line-height: 1.45; }
        .client-order-status { color: #166534; white-space: nowrap; }
        .floating-cart-button { display: none; }
        @media (max-width: 760px) { .menu-grid { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { h1 { font-size: 30px; } .checkout-grid { grid-template-columns: 1fr; } .page { padding: 0; } .card { border-radius: 0; min-height: 100vh; padding: 20px 14px 92px; } .login-card { margin-top: 0; border-radius: 18px 18px 0 0; min-height: auto; padding: 24px; } .topbar { gap: 8px; } .table-pill { display: none; } .category-head { grid-template-columns: 1fr; } .category-hero { max-height: 170px; } .menu-item { grid-template-columns: 92px 1fr; min-height: 118px; } .menu-photo { width: 92px; height: 98px; } .cart-panel { bottom: 0; border-radius: 18px 18px 0 0; margin-left: -8px; margin-right: -8px; padding: 10px 12px; } .cart-panel:not(.is-checkout) .cart-empty, .cart-panel:not(.is-checkout) .cart-list { display: none !important; } .cart-panel:not(.is-checkout) .submit-order { margin-top: 8px; padding: 12px 14px; } .floating-cart-button { position: fixed; left: 14px; right: 14px; bottom: max(14px, env(safe-area-inset-bottom)); z-index: 20; display: none; justify-content: center; align-items: center; gap: 8px; min-height: 52px; box-shadow: 0 18px 42px rgba(23,19,14,.34); } .floating-cart-button.is-visible { display: flex; } }
    </style>
</head>
<body>
<main class="page">
    <section class="card">
        <a class="home-link" href="{{ url('/') }}#buffet">&larr; Retour aux tarifs buffet</a>
        <div class="topbar">
            <div>
                <div class="eyebrow">La Grande Muraille de Chine</div>
                <h1>Buffet à table</h1>
                <div class="intro-copy">
                    <p class="intro-lead">Nouveau : commandez vos spécialités directement depuis votre table.</p>
                    <p>Sushis, accompagnements chauds, petites bouchées et suggestions du moment : tous les articles proposés ici sont inclus sans supplément dans votre formule buffet, tous les soirs et le dimanche midi.</p>
                    <p><strong>Comment ça marche ?</strong><br>Indiquez votre numéro de table et votre code d'accès, puis validez vos choix.</p>
                    <p class="waste-note">💡 Afin de préserver ce service inclus sans supplément, nous faisons appel à votre sens de l'anti-gaspillage : ne commandez que ce que vous mangez. Tout abus ou reste important fera l'objet d'un supplément de 10 € à 20 €.</p>
                </div>
            </div>
            @if($table)
                <div class="table-pill">Table {{ $table }}</div>
            @endif
        </div>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif
        @if(!$orderingOpen)
            <div class="alert error">{{ $orderingClosedMessage ?: 'La commande à table est fermée pour le moment.' }}</div>
        @endif
        @if($lastOrder)
            <div class="last-order"><strong>Dernière commande envoyée :</strong> {{ $lastOrder['reference'] }} - {{ $lastOrder['count'] }} article(s) pour la table {{ $lastOrder['table'] }}.</div>
        @endif
        @if($validated)
            <div class="table-session">
                <div>
                    <strong>Table {{ $table }} validée</strong>
                    <span>Vous pouvez commander encore sans retaper le code aujourd'hui.</span>
                </div>
                <a href="{{ route('restoline.table-ordering.index', ['changer_table' => 1]) }}">Changer</a>
            </div>
        @endif
        @if($clientOrders->isNotEmpty())
            <section class="client-orders" id="mes-commandes">
                <h2>Mes commandes</h2>
                <ul class="client-order-list">
                    @foreach($clientOrders as $order)
                        <li class="client-order">
                            <div class="client-order-head">
                                <a href="{{ route('restoline.table-ordering.status', ['reference' => $order->reference]) }}">{{ $order->reference }}</a>
                                <span class="client-order-status">{{ $order->print_status_label }}</span>
                            </div>
                            <div class="client-order-meta">
                                Table {{ $order->table_name }} · {{ $order->submitted_at?->format('H:i') }}
                                @if($order->printed_at)
                                    · imprimée à {{ $order->printed_at->format('H:i') }}
                                @endif
                            </div>
                            <div class="client-order-items">
                                {{ $order->items->map(fn($item) => $item->quantity.'x '.$item->menu_name)->implode(' · ') }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <nav class="category-nav" aria-label="Catégories du menu">
            @foreach($categories as $category)
                <a href="#category-{{ $category->id }}">{{ $category->name }}</a>
            @endforeach
        </nav>

        <form method="post" action="{{ route('restoline.table-ordering.submit') }}" id="order-form">
            @csrf
            <input type="hidden" name="order_token" value="{{ $orderToken }}">
            @forelse($categories as $category)
                <section class="category" id="category-{{ $category->id }}">
                    <div class="category-head">
                        <div>
                            <h2>{{ $category->name }}</h2>
                            @if($category->description)
                                <p class="category-desc">{{ $category->description }}</p>
                            @endif
                        </div>
                        @if($category->hero_image_url)
                            <img class="category-hero" src="{{ $category->hero_image_url }}" alt="{{ $category->name }}">
                        @endif
                    </div>

                    <div class="menu-grid">
                        @foreach($category->items as $item)
                            <article class="menu-item" data-menu-id="{{ $item->menu_id }}" data-menu-name="{{ e($item->menu_name) }}">
                                @if($item->image_url)
                                    <img class="menu-photo" src="{{ $item->image_url }}" alt="{{ $item->menu_name }}" loading="lazy">
                                @else
                                    <div class="menu-photo" aria-hidden="true"></div>
                                @endif
                                <div class="menu-content">
                                    <div class="menu-name">
                                        <span>{{ $item->menu_name }}</span>
                                        @if(str_contains(mb_strtolower((string)$category->name), 'california') || str_contains(mb_strtolower((string)$category->name), 'maki'))
                                            <span class="portion-badge">6 pièces / portion</span>
                                        @endif
                                    </div>
                                    @if($item->menu_description)
                                        <div class="menu-desc">{{ strip_tags($item->menu_description) }}</div>
                                    @endif
                                    <div class="price">{{ (float)$item->menu_price <= 0 ? 'Inclus' : number_format((float)$item->menu_price, 2, ',', ' ') . ' €' }}</div>
                                    <div class="qty-row">
                                        <button class="qty-btn" type="button" data-action="minus" aria-label="Retirer" {{ !$orderingOpen ? 'disabled' : '' }}>−</button>
                                        <span class="qty-value" data-qty-display="{{ $item->menu_id }}">0</span>
                                        <button class="qty-btn" type="button" data-action="plus" aria-label="Ajouter" {{ !$orderingOpen ? 'disabled' : '' }}>+</button>
                                        <input type="hidden" name="items[{{ $item->menu_id }}]" value="0" data-qty-input="{{ $item->menu_id }}">
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="alert error">Aucun plat disponible pour le moment.</div>
            @endforelse

            <aside class="cart-panel {{ $errors->has('table') || $errors->has('code') ? 'is-checkout' : '' }}" aria-live="polite" id="cart-panel">
                <div class="cart-title"><span>Votre commande</span><span id="cart-count">0 article</span></div>
                <div class="cart-empty" id="cart-empty">Aucun plat sélectionné pour le moment.</div>
                <ul class="cart-list" id="cart-list"></ul>

                <div class="checkout-step" id="checkout-step">
                    @if($validated)
                        <p class="checkout-help">Votre commande sera envoyée pour la table {{ $table }}.</p>
                        <input id="table" name="table" type="hidden" value="{{ $table }}">
                        <input id="code" name="code" type="hidden" value="">
                    @else
                        <p class="checkout-help">Pour envoyer la commande en cuisine, indiquez votre table et le code du jour donné par le restaurant.</p>
                        <div class="checkout-grid">
                            <div>
                                <label for="table">Table</label>
                                <input id="table" name="table" value="{{ old('table', $table) }}" inputmode="text" autocomplete="off" placeholder="Ex. 9 ou 23A">
                            </div>
                            <div>
                                <label for="code">Code du jour</label>
                                <input id="code" name="code" value="{{ old('code') }}" inputmode="numeric" pattern="[0-9]{3}" maxlength="3" autocomplete="off" placeholder="3 chiffres">
                            </div>
                        </div>
                    @endif

                    <label for="note">Remarque pour la cuisine</label>
                    <textarea id="note" name="note" maxlength="500" placeholder="Ex. sans sauce, allergies, cuisson..."></textarea>
                </div>

                <button class="submit-order" id="submit-order" type="submit" disabled data-open="{{ $orderingOpen ? '1' : '0' }}" data-validated="{{ $validated ? '1' : '0' }}">Valider la commande</button>
            </aside>
        </form>
        <button class="floating-cart-button" id="floating-cart-button" type="button" aria-controls="cart-panel">
            Voir ma commande · <span id="floating-cart-count">0 article</span>
        </button>
    </section>
</main>
<script>
(function() {
    const form = document.getElementById('order-form');
    if (!form) return;
    const cartPanel = document.getElementById('cart-panel');
    const cartCount = document.getElementById('cart-count');
    const cartEmpty = document.getElementById('cart-empty');
    const cartList = document.getElementById('cart-list');
    const submitButton = document.getElementById('submit-order');
    const tableInput = document.getElementById('table');
    const codeInput = document.getElementById('code');
    const floatingCartButton = document.getElementById('floating-cart-button');
    const floatingCartCount = document.getElementById('floating-cart-count');
    const accessValidated = submitButton.dataset.validated === '1';
    let checkoutVisible = cartPanel && cartPanel.classList.contains('is-checkout');
    function updateSubmitLabel() { submitButton.textContent = checkoutVisible ? 'Confirmer et envoyer' : 'Valider la commande'; }
    function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, function(char) { return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]; }); }
    function updateCart() {
        let total = 0; const lines = [];
        document.querySelectorAll('.menu-item').forEach(function(item) {
            const menuId = item.dataset.menuId;
            const input = document.querySelector('[data-qty-input="' + menuId + '"]');
            const quantity = parseInt(input.value || '0', 10);
            total += quantity;
            if (quantity > 0) lines.push('<li>' + quantity + 'x ' + escapeHtml(item.dataset.menuName) + '</li>');
        });
        cartCount.textContent = total + (total > 1 ? ' articles' : ' article');
        if (floatingCartCount) floatingCartCount.textContent = cartCount.textContent;
        if (floatingCartButton) floatingCartButton.classList.toggle('is-visible', total > 0 && !checkoutVisible);
        cartEmpty.style.display = total > 0 ? 'none' : 'block';
        cartList.innerHTML = lines.join('');
        submitButton.disabled = total === 0 || submitButton.dataset.open !== '1';
        updateSubmitLabel();
    }
    form.addEventListener('submit', function(event) {
        if (!checkoutVisible) {
            event.preventDefault();
            checkoutVisible = true;
            cartPanel.classList.add('is-checkout');
            if (floatingCartButton) floatingCartButton.classList.remove('is-visible');
            updateSubmitLabel();
            setTimeout(function() { if (!accessValidated) tableInput && tableInput.focus(); }, 60);
            return;
        }

        if (!String(tableInput.value || '').trim() || (!accessValidated && !/^\d{3}$/.test(String(codeInput.value || '').trim()))) {
            event.preventDefault();
            cartPanel.classList.add('is-checkout');
            updateSubmitLabel();
            (String(tableInput.value || '').trim() ? codeInput : tableInput)?.focus();
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Commande en cours...';
    });

    if (floatingCartButton) {
        floatingCartButton.addEventListener('click', function() {
            checkoutVisible = true;
            cartPanel.classList.add('is-checkout');
            floatingCartButton.classList.remove('is-visible');
            updateSubmitLabel();
            cartPanel.scrollIntoView({behavior: 'smooth', block: 'start'});
            setTimeout(function() { if (!accessValidated) tableInput && tableInput.focus(); }, 220);
        });
    }

    document.addEventListener('click', function(event) {
        const button = event.target.closest('.qty-btn');
        if (!button) return;
        const item = button.closest('.menu-item');
        const menuId = item.dataset.menuId;
        const input = document.querySelector('[data-qty-input="' + menuId + '"]');
        const display = document.querySelector('[data-qty-display="' + menuId + '"]');
        let quantity = parseInt(input.value || '0', 10);
        if (button.dataset.action === 'plus') quantity = Math.min(quantity + 1, 20);
        if (button.dataset.action === 'minus') quantity = Math.max(quantity - 1, 0);
        input.value = quantity; display.textContent = quantity; updateCart();
    });
    updateSubmitLabel();
    updateCart();
})();
</script>
</body>
</html>
