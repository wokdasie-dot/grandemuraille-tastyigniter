<?php
/** @var ?\Restoline\Content\Models\GiftCard $giftCard */
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Utiliser un bon cadeau</h3>
                <form method="get" action="<?= e(admin_url('restoline/content/gift_cards/redeem')) ?>" class="mb-4">
                    <div class="input-group">
                        <input class="form-control" name="code" value="<?= e(request()->query('code')) ?>" placeholder="Saisir ou scanner le code du bon cadeau" autofocus>
                        <button class="btn btn-primary" type="submit">Rechercher</button>
                    </div>
                </form>
                <button class="btn btn-outline-secondary mb-4" type="button" id="start-qr-scanner">Scanner le QR code</button>
                <div id="qr-scanner" class="mb-4" hidden>
                    <video id="qr-video" autoplay playsinline muted style="max-width:360px;width:100%;border-radius:6px;background:#111;"></video>
                    <p class="text-muted mt-2 mb-0" id="qr-scanner-message">Autorisez la caméra puis cadrez le QR code.</p>
                </div>

                <?php if (!$giftCard): ?>
                    <p class="text-muted mb-0">Saisissez le code du bon ou scannez son QR code avec un appareil déjà connecté au dashboard.</p>
                <?php else: ?>
                    <div class="alert <?= $giftCard->isRedeemable() ? 'alert-success' : 'alert-warning' ?>">
                        <strong><?= e($giftCard->code) ?></strong> · <?= e($giftCard->status_label) ?> · Solde disponible : <strong><?= e($giftCard->formatted_balance) ?></strong>
                    </div>
                    <p class="mb-3">Bénéficiaire : <?= e($giftCard->recipient_name ?: 'Non renseigné') ?></p>
                    <form method="post" action="<?= e(admin_url('restoline/content/gift_cards/redeem')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="gift_card_id" value="<?= (int)$giftCard->gift_card_id ?>">
                        <div class="mb-3"><label class="form-label d-block">Utilisation</label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="mode" id="mode-partial" value="partial" <?= empty($selectFullAmount) ? 'checked' : '' ?> <?= !$giftCard->isRedeemable() ? 'disabled' : '' ?>><label class="form-check-label" for="mode-partial">Utiliser un montant partiel</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="mode" id="mode-full" value="full" <?= !empty($selectFullAmount) ? 'checked' : '' ?> <?= !$giftCard->isRedeemable() ? 'disabled' : '' ?>><label class="form-check-label" for="mode-full">Utiliser tout le solde (<?= e($giftCard->formatted_balance) ?>)</label></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5"><label class="form-label" for="redeem-amount">Montant à débiter</label><div class="input-group"><input class="form-control" id="redeem-amount" name="amount" type="number" min="0.01" max="<?= e($giftCard->balance_amount) ?>" step="0.01" required <?= !$giftCard->isRedeemable() ? 'disabled' : '' ?>><span class="input-group-text">EUR</span></div></div>
                            <div class="col-md-7"><label class="form-label">Note interne</label><input class="form-control" name="note" maxlength="500" placeholder="Ex. repas table 12" <?= !$giftCard->isRedeemable() ? 'disabled' : '' ?>></div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit" <?= !$giftCard->isRedeemable() ? 'disabled' : '' ?>>Valider le débit</button>
                    </form>
                    <?php if ($giftCard->transactions->isNotEmpty()): ?>
                        <hr><h5>Historique</h5><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Action</th><th>Montant</th><th>Solde</th><th>Personnel</th></tr></thead><tbody>
                        <?php foreach ($giftCard->transactions()->latest('gift_card_transaction_id')->get() as $transaction): ?><tr><td><?= e($transaction->created_at?->format('d/m/Y H:i')) ?></td><td><?= e($transaction->type) ?></td><td><?= e(number_format((float)$transaction->amount, 2, ',', ' ')) ?> EUR</td><td><?= e(number_format((float)$transaction->balance_after, 2, ',', ' ')) ?> EUR</td><td><?= e($transaction->staff_name ?: '-') ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const scannerButton = document.getElementById('start-qr-scanner');
    const scanner = document.getElementById('qr-scanner');
    const video = document.getElementById('qr-video');
    const message = document.getElementById('qr-scanner-message');
    let stream;

    const stop = () => stream?.getTracks().forEach(track => track.stop());
    const scan = async () => {
        if (!('BarcodeDetector' in window)) {
            message.textContent = 'Le scan caméra n’est pas pris en charge par ce navigateur. Ouvrez le QR avec l’appareil photo ou saisissez le code.';
            return;
        }
        const detector = new BarcodeDetector({formats: ['qr_code']});
        const tick = async () => {
            if (!video.videoWidth) return requestAnimationFrame(tick);
            const codes = await detector.detect(video);
            if (codes[0]?.rawValue) {
                stop();
                const value = codes[0].rawValue;
                window.location.assign(value.startsWith(window.location.origin) ? value : '<?= e(admin_url('restoline/content/gift_cards/redeem?code=')) ?>'+encodeURIComponent(value));
                return;
            }
            requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    scannerButton?.addEventListener('click', async () => {
        scanner.hidden = false;
        try {
            stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: {ideal: 'environment'}}});
            video.srcObject = stream;
            await video.play();
            scan();
        } catch (error) {
            message.textContent = 'Impossible d’ouvrir la caméra. Vérifiez l’autorisation du navigateur puis réessayez.';
        }
    });
    window.addEventListener('beforeunload', stop);

    const full = document.getElementById('mode-full');
    const partial = document.getElementById('mode-partial');
    const amount = document.getElementById('redeem-amount');
    const updateAmount = () => {
        amount.disabled = full.checked;
        if (full.checked) amount.value = '<?= e(number_format((float)$giftCard?->balance_amount, 2, '.', '')) ?>';
        else amount.value = '';
    };
    full?.addEventListener('change', updateAmount);
    partial?.addEventListener('change', updateAmount);
    if (full && partial && amount) updateAmount();
})();
</script>
