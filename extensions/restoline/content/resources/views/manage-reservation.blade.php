<!doctype html>
<html lang="fr-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Gérer ma réservation | La Grande Muraille de Chine</title>
    <style>
        :root { color-scheme: light; font-family: Inter, system-ui, sans-serif; color: #17211d; background: #f6f7f5; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        main { width: min(100%, 560px); background: #fff; padding: 36px; border: 1px solid #e4e8e5; border-radius: 8px; box-shadow: 0 12px 36px rgba(23,33,29,.08); }
        a { color: #065f46; }
        .brand { display: block; margin-bottom: 30px; color: #17211d; text-decoration: none; font-family: Georgia, serif; font-size: 24px; }
        .brand small { display: block; margin-top: 3px; color: #68736d; font: 12px Inter, system-ui, sans-serif; text-transform: uppercase; }
        h1 { margin: 0 0 10px; font: 38px/1.08 Georgia, serif; }
        p { color: #5c6862; line-height: 1.6; }
        form { display: grid; gap: 14px; margin-top: 26px; }
        label { display: grid; gap: 7px; font-weight: 700; }
        input { width: 100%; min-height: 52px; padding: 12px 14px; border: 1px solid #cdd5d0; border-radius: 6px; font: inherit; }
        input:focus { outline: 3px solid rgba(6,95,70,.14); border-color: #065f46; }
        button { min-height: 52px; border: 0; border-radius: 6px; background: #065f46; color: #fff; font: 700 15px Inter, system-ui, sans-serif; cursor: pointer; }
        button.danger { background: #fff; color: #9b2c20; border: 1px solid #e1b8b2; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .summary { margin: 22px 0 0; padding: 16px; background: #f2f6f3; border-left: 3px solid #065f46; color: #34443c; line-height: 1.6; }
        .cancel-form { margin-top: 12px; }
        .message { padding: 12px 14px; border-radius: 6px; margin-top: 18px; }
        .success { background: #eaf5ef; color: #14543f; }
        .error { background: #fff0ed; color: #9b2c20; }
        .back { display: inline-block; margin-top: 28px; }
        @media (max-width: 560px) {
            main { padding: 26px 20px; }
            h1 { font-size: 32px; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
    @php($turnstileSiteKey = app(\Restoline\Content\Services\TurnstileService::class)->siteKey())
    @if($turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>
<body>
<main>
    <a class="brand" href="{{ url('/') }}">La Grande Muraille de Chine</a>
    <h1>Gérer ma réservation</h1>
    @if($reservation)
        <p>Modifiez les informations de votre table. Les disponibilités sont vérifiées avant l’enregistrement.</p>
    @else
        <p>Identifiez-vous avec l’adresse e-mail utilisée lors de la réservation.</p>
    @endif

    @if(session('status'))
        <div class="message success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="message error">{{ $errors->first() }}</div>
    @endif

    @if($reservation)
        <div class="summary">
            Réservation n°{{ $reservation->reservation_id }}<br>
            {{ $reservation->reserve_date->format('d/m/Y') }} à {{ substr((string)$reservation->reserve_time, 0, 5) }}
        </div>

        <form method="post" action="{{ route('restoline.manage.update') }}">
            @csrf
            <label>Nom et prénom
                <input
                    name="customer_name"
                    value="{{ old('customer_name', trim($reservation->first_name.' '.$reservation->last_name)) }}"
                    autocomplete="name"
                    required
                >
            </label>
            <label>Nombre de personnes
                <input
                    name="guest_num"
                    type="number"
                    min="1"
                    max="30"
                    inputmode="numeric"
                    value="{{ old('guest_num', $reservation->guest_num) }}"
                    required
                >
            </label>
            <div class="grid">
                <label>Date
                    <input
                        name="reserve_date"
                        type="date"
                        min="{{ now()->toDateString() }}"
                        max="{{ $maxDate }}"
                        value="{{ old('reserve_date', $reservation->reserve_date->toDateString()) }}"
                        required
                    >
                </label>
                <label>Heure
                    <input
                        name="reserve_time"
                        type="time"
                        step="900"
                        value="{{ old('reserve_time', substr((string)$reservation->reserve_time, 0, 5)) }}"
                        required
                    >
                </label>
            </div>
            <button type="submit">Enregistrer les modifications</button>
        </form>

        <form
            class="cancel-form"
            method="post"
            action="{{ route('restoline.manage.cancel') }}"
            onsubmit="if (!confirm('Voulez-vous vraiment annuler cette réservation ?')) return false; this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').textContent = 'Annulation en cours...'; return true;"
        >
            @csrf
            <button class="danger" type="submit">Annuler la réservation</button>
        </form>
    @elseif($codeRequested)
        <form method="post" action="{{ route('restoline.manage.verify') }}">
            @csrf
            <label>Code à 4 chiffres
                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="4" pattern="[0-9]{4}" required autofocus>
            </label>
            <button type="submit">Accéder à ma réservation</button>
        </form>
        <form method="post" action="{{ route('restoline.manage.send-code') }}">
            @csrf
            <label>Utiliser une autre adresse e-mail
                <input name="identifier" type="email" autocomplete="email" required>
            </label>
            @if($turnstileSiteKey)
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
            @endif
            <button type="submit">Recevoir un nouveau code</button>
        </form>
    @else
        <form method="post" action="{{ route('restoline.manage.send-code') }}">
            @csrf
            <label>Adresse e-mail
                <input name="identifier" type="email" autocomplete="email" value="{{ old('identifier') }}" placeholder="nom@email.be" required autofocus>
            </label>
            @if($turnstileSiteKey)
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
            @endif
            <button type="submit">Recevoir mon code</button>
        </form>
    @endif

    <a class="back" href="{{ url('/') }}">Retour au site La Grande Muraille de Chine</a>
</main>
</body>
</html>
