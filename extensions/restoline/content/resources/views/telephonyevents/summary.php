<?php
$adminUrl = admin_url('restoline/content/telephony_events');
$voiceMessagesUrl = admin_url('restoline/content/voice_messages');
?>

<div class="row-fluid">
    <div class="span12">
        <div class="toolbar-widget">
            <div class="toolbar">
                <a class="btn btn-light" href="<?= e($adminUrl); ?>">Journal détaillé</a>
                <a class="btn btn-light" href="<?= e($voiceMessagesUrl); ?>">Messages vocaux</a>
            </div>
        </div>

        <form method="get" class="form-inline mb-3">
            <label for="telephony-summary-month" class="mr-2">Mois à analyser</label>
            <input id="telephony-summary-month" type="month" name="month" value="<?= e($month); ?>" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Afficher</button>
        </form>

        <div class="callout callout-info">
            Vue simplifiée du répondeur et des SMS pour <?= e($periodLabel); ?>. Le journal détaillé reste disponible pour les recherches techniques.
        </div>
    </div>
</div>

<div class="row">
    <?php
    $cards = [
        ['label' => 'Appels aujourd’hui', 'value' => $todayCalls],
        ['label' => 'Messages vocaux aujourd’hui', 'value' => $todayVoiceMessages],
        ['label' => 'Appels 7 jours', 'value' => $last7Calls],
        ['label' => 'Messages vocaux 7 jours', 'value' => $last7VoiceMessages],
        ['label' => 'Réservations créées 7 jours', 'value' => $last7ConfirmedReservations],
        ['label' => 'Doutes à vérifier', 'value' => $failedRequests],
        ['label' => 'SMS GSM livrés', 'value' => $smsGatewayDelivered],
        ['label' => 'SMS GSM échoués', 'value' => $smsGatewayFailed],
        ['label' => 'Durée moyenne appel', 'value' => $averageDuration ? $averageDuration.' sec.' : '-'],
        ['label' => 'Événements conservés', 'value' => $events->count()],
    ];
    ?>
    <?php foreach ($cards as $card): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted small"><?= e($card['label']); ?></div>
                    <div style="font-size: 28px; font-weight: 700;"><?= e((string)$card['value']); ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Activité par jour</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Date</th><th class="text-right">Appels</th><th class="text-right">Messages</th><th class="text-right">Réservations</th><th class="text-right">SMS livrés</th><th class="text-right">Doutes</th></tr></thead>
                    <tbody>
                    <?php if ($dailyCounts->isEmpty()): ?>
                        <tr><td colspan="6" class="text-muted">Aucune activité sur ce mois.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($dailyCounts as $day): ?>
                        <tr>
                            <td><?= e($day['date']); ?></td>
                            <td class="text-right"><?= e((string)$day['calls']); ?></td>
                            <td class="text-right"><?= e((string)$day['voice_messages']); ?></td>
                            <td class="text-right"><?= e((string)$day['reservations']); ?></td>
                            <td class="text-right"><?= e((string)$day['sms_delivered']); ?></td>
                            <td class="text-right"><?= e((string)$day['problems']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Touches utilisées</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Touche</th><th class="text-right">Nombre</th></tr></thead>
                    <tbody>
                    <?php if ($menuChoices->isEmpty()): ?>
                        <tr><td colspan="2" class="text-muted">Aucun choix enregistré.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($menuChoices as $choice): ?>
                        <tr><td><?= e($choice['option']); ?></td><td class="text-right"><?= e((string)$choice['count']); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">Doutes à vérifier</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Date</th><th>Événement</th><th>Statut</th><th>Détail</th></tr></thead>
                    <tbody>
                    <?php if ($problemEvents->isEmpty()): ?>
                        <tr><td colspan="4" class="text-muted">Aucun doute détecté sur ce mois.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($problemEvents as $event): ?>
                        <tr>
                            <td><?= e($event->created_at?->format('d/m H:i') ?? '-'); ?></td>
                            <td><?= e($event->event_label); ?></td>
                            <td><?= e((string)$event->status); ?></td>
                            <td><?= e($event->payload_summary ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Événements fréquents</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Événement</th><th class="text-right">Nombre</th></tr></thead>
                    <tbody>
                    <?php foreach ($eventTypes as $event): ?>
                        <tr><td><?= e($event['label']); ?></td><td class="text-right"><?= e((string)$event['count']); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
