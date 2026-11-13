<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande envoyée - La Grande Muraille de Chine</title>
    <style>
        :root { color-scheme: light; --gold:#c99a3f; --gold-dark:#8b611c; --ink:#17130e; --muted:#70685d; --line:#efe7da; --ok:#166534; --warn:#9a3412; --bad:#991b1b; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 18px; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: radial-gradient(circle at top, #382116 0, #120f0c 46%, #070605 100%); color: #fff; }
        .card { width: min(560px, 100%); background: #fffaf1; color: var(--ink); border-radius: 24px; padding: 26px; box-shadow: 0 24px 80px rgba(0,0,0,.42); border: 1px solid rgba(201,154,63,.28); }
        .eyebrow { color: var(--gold-dark); font-weight: 900; text-transform: uppercase; letter-spacing: .12em; font-size: 12px; margin-bottom: 10px; }
        h1 { margin: 0 0 12px; font-size: clamp(30px, 8vw, 44px); line-height: 1.04; }
        p { color: var(--muted); line-height: 1.55; margin: 0 0 16px; }
        .status { border-radius: 18px; padding: 16px; margin: 18px 0; background: #fff; border: 1px solid var(--line); }
        .status strong { display: block; font-size: 20px; margin-bottom: 6px; }
        .status.is-ok { border-color: #b7e4c7; background: #e8f8ef; color: #14532d; }
        .status.is-wait { border-color: #fed7aa; background: #fff7ed; color: var(--warn); }
        .status.is-bad { border-color: #fecaca; background: #fef2f2; color: var(--bad); }
        .items { margin: 16px 0 0; padding: 0; list-style: none; display: grid; gap: 8px; }
        .items li { padding: 10px 12px; border-radius: 12px; background: rgba(201,154,63,.12); color: var(--ink); font-weight: 700; }
        .actions { display: grid; gap: 10px; margin-top: 20px; }
        .button { display: inline-flex; justify-content: center; align-items: center; min-height: 48px; border-radius: 999px; padding: 12px 16px; text-decoration: none; font-weight: 900; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: #fff; }
        .button.secondary { background: #17130e; }
        .small { font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<main class="card">
    <div class="eyebrow">La Grande Muraille de Chine</div>
    <h1>Commande envoyée</h1>
    <p>Table <strong id="table-name">{{ $order->table_name }}</strong> · Référence <strong id="order-reference">{{ $order->reference }}</strong></p>

    <section class="status is-wait" id="order-status" aria-live="polite">
        <strong id="status-title">Envoi vers l’imprimante...</strong>
        <span id="status-text">Merci de rester à table quelques instants.</span>
    </section>

    <ul class="items">
        @foreach($order->items as $item)
            <li>{{ (int)$item->quantity }}x {{ $item->menu_name }}</li>
        @endforeach
    </ul>

    <div class="actions">
        <a class="button" href="{{ route('restoline.table-ordering.index') }}#mes-commandes">Voir / ajouter une commande</a>
        <a class="button secondary" href="tel:+3265783378">Appeler le restaurant</a>
    </div>
    <p class="small">Si le message indique un problème d’impression, montrez cette page au serveur.</p>
</main>
<script>
(function() {
    const statusUrl = @json($statusUrl);
    const box = document.getElementById('order-status');
    const title = document.getElementById('status-title');
    const text = document.getElementById('status-text');
    let attempts = 0;

    function render(data) {
        box.classList.remove('is-ok', 'is-wait', 'is-bad');
        if (data.print_status === 'printed') {
            box.classList.add('is-ok');
            title.textContent = 'Ticket imprimé';
            text.textContent = data.printed_at ? 'La cuisine a reçu votre commande à ' + data.printed_at + '.' : 'La cuisine a reçu votre commande.';
            return true;
        }
        if (data.needs_help) {
            box.classList.add('is-bad');
            title.textContent = 'Demandez au serveur';
            text.textContent = 'Nous ne recevons pas le retour imprimante. Pour éviter une attente inutile, montrez cette page à notre équipe.';
            return true;
        }
        box.classList.add('is-wait');
        title.textContent = data.print_status === 'pending' ? 'Commande reçue' : 'Impression en cours';
        text.textContent = 'Merci de rester à table, le ticket est en cours de traitement.';
        return false;
    }

    function poll() {
        attempts += 1;
        fetch(statusUrl, {headers: {'Accept': 'application/json'}})
            .then(function(response) { return response.json(); })
            .then(function(data) {
                const done = render(data);
                if (!done && attempts < 45) window.setTimeout(poll, 2000);
            })
            .catch(function() {
                box.classList.remove('is-ok', 'is-wait');
                box.classList.add('is-bad');
                title.textContent = 'Connexion interrompue';
                text.textContent = 'Demandez au serveur de vérifier votre commande.';
            });
    }

    poll();
})();
</script>
</body>
</html>
