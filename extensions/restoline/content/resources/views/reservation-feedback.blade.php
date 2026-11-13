<!doctype html>
<html lang="fr-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Votre retour | La Grande Muraille de Chine</title>
    <style>
        :root { color-scheme: light; font-family: Inter, system-ui, sans-serif; color: #17211d; background: #f7f4ee; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 22px; display: grid; place-items: center; }
        main { width: min(100%, 620px); background: #fff; border: 1px solid #e5ded0; border-radius: 8px; padding: 30px; box-shadow: 0 14px 42px rgba(23,33,29,.08); }
        h1 { font: 34px/1.08 Georgia, serif; margin: 0 0 12px; }
        p { color: #5f6b64; line-height: 1.6; }
        .brand { display: block; margin-bottom: 24px; color: #17211d; text-decoration: none; font-family: Georgia, serif; font-size: 24px; }
        .brand small { display: block; font: 12px Inter, system-ui, sans-serif; text-transform: uppercase; color: #7b6a45; letter-spacing: .12em; }
        .summary { margin: 18px 0; padding: 14px; background: #f5f8f5; border-left: 3px solid #215b45; color: #34443c; }
        .message { padding: 12px 14px; border-radius: 6px; background: #eaf5ef; color: #14543f; margin: 18px 0; }
        form { display: grid; gap: 18px; margin-top: 22px; }
        fieldset { border: 1px solid #e1ded8; border-radius: 8px; padding: 14px; display: grid; gap: 10px; }
        legend { font-weight: 800; padding: 0 6px; }
        label { display: flex; gap: 9px; align-items: flex-start; color: #25322d; line-height: 1.45; }
        textarea, select { width: 100%; border: 1px solid #cbcfc9; border-radius: 6px; font: inherit; padding: 12px; }
        textarea { min-height: 110px; resize: vertical; }
        button, .button { min-height: 50px; border: 0; border-radius: 6px; padding: 14px 18px; background: #215b45; color: #fff; font: 800 15px Inter, system-ui, sans-serif; text-decoration: none; text-align: center; cursor: pointer; }
        button:disabled { cursor: wait; opacity: .72; }
        .button.secondary { background: #f2eadb; color: #17211d; border: 1px solid #d8c7a3; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .actions.single { grid-template-columns: 1fr; }
        .problem-details[hidden] { display: none; }
        @media (max-width: 560px) { main { padding: 24px 18px; } h1 { font-size: 30px; } .actions { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <a class="brand" href="{{ url('/') }}">La Grande Muraille de Chine <small>Hornu</small></a>
    <h1>Comment s’est passée votre visite ?</h1>
    <p>Deux réponses rapides nous aident à améliorer le restaurant et notre système de réservation.</p>

    <div class="summary">
        Réservation n°{{ $feedback->reservation_id }}<br>
        @if($feedback->reserve_date)
            {{ $feedback->reserve_date->format('d/m/Y') }} à {{ substr((string)$feedback->reserve_time, 0, 5) }}
        @endif
    </div>

    @if(session('status'))
        <div class="message">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('restoline.reservation-feedback.store', ['token' => $feedback->token]) }}">
        @csrf
        <fieldset>
            <legend>Votre visite</legend>
            <label><input type="radio" name="visit_answer" value="ok" @checked($feedback->visit_answer === 'ok') required> Tout s’est bien passé.</label>
            <label><input type="radio" name="visit_answer" value="problem" @checked($feedback->visit_answer === 'problem') required> J’ai rencontré un problème.</label>
        </fieldset>

        <fieldset>
            <legend>Votre réservation</legend>
            <label><input type="radio" name="reservation_answer" value="ok" @checked($feedback->reservation_answer === 'ok') required> La réservation s’est bien passée.</label>
            <label><input type="radio" name="reservation_answer" value="bug" @checked($feedback->reservation_answer === 'bug') required> J’ai eu un bug ou une difficulté.</label>
        </fieldset>

        <label style="display:grid;gap:7px;font-weight:700;">Comment avez-vous réservé ?
            <select name="reservation_channel" required>
                <option value="online" @selected($feedback->reservation_channel === 'online')>En ligne sur le site</option>
                <option value="phone" @selected($feedback->reservation_channel === 'phone')>Par téléphone / répondeur</option>
                <option value="unknown" @selected($feedback->reservation_channel === 'unknown')>Je ne sais plus</option>
            </select>
        </label>

        <div class="problem-details" @if(!in_array($feedback->visit_answer, ['problem']) && !in_array($feedback->reservation_answer, ['bug'])) hidden @endif>
            <label style="display:grid;gap:7px;font-weight:700;">Type de problème
                <select name="problem_type">
                    <option value="">Choisissez si nécessaire</option>
                    <option value="code_sms" @selected($feedback->problem_type === 'code_sms')>Code SMS ou email</option>
                    <option value="annulation" @selected($feedback->problem_type === 'annulation')>Modification ou annulation</option>
                    <option value="horaire" @selected($feedback->problem_type === 'horaire')>Date ou horaire</option>
                    <option value="telephone" @selected($feedback->problem_type === 'telephone')>Répondeur téléphonique</option>
                    <option value="autre" @selected($feedback->problem_type === 'autre')>Autre</option>
                </select>
            </label>

        </div>

        <label style="display:grid;gap:7px;font-weight:700;">Un commentaire à ajouter ? <span style="font-weight:400;color:#6b7280;">(facultatif)</span>
            <textarea name="message" placeholder="Quelques mots suffisent.">{{ old('message', $feedback->message) }}</textarea>
        </label>

        <div class="actions @if(!$googleEnabled) single @endif">
            <button type="submit">Envoyer mon retour</button>
            @if($googleEnabled)
                <a class="button secondary" href="{{ $googleUrl }}">Laisser un avis Google</a>
            @endif
        </div>
    </form>
</main>
<script>
    const details = document.querySelector('.problem-details');
    const updateDetails = () => {
        const visitProblem = document.querySelector('input[name="visit_answer"]:checked')?.value === 'problem';
        const reservationProblem = document.querySelector('input[name="reservation_answer"]:checked')?.value === 'bug';
        details.hidden = !(visitProblem || reservationProblem);
    };
    document.querySelectorAll('input[name="visit_answer"], input[name="reservation_answer"]').forEach((input) => {
        input.addEventListener('change', updateDetails);
    });

    const form = document.querySelector('form');
    form?.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton || submitButton.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        submitButton.dataset.submitting = 'true';
        submitButton.disabled = true;
        submitButton.textContent = 'Envoi en cours...';
    });
</script>
</body>
</html>
