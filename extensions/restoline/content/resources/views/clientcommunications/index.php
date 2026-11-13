<?php
$sections = [
    'Automatisations clients' => [
        [
            'title' => 'Rappels grandes tables',
            'description' => 'Rappel email et SMS envoyé la veille aux réservations de '.$reminderSettings->min_guests.' personnes et plus.',
            'status' => $reminderSettings->enabled ? 'Actif' : 'Désactivé',
            'statusClass' => $reminderSettings->enabled ? 'badge-success' : 'badge-secondary',
            'detail' => $remindersSentToday.' envoyé(s) aujourd’hui · '.$reminderFailures.' échec(s) sur 30 jours',
            'actions' => [
                ['label' => 'Voir les rappels', 'url' => admin_url('restoline/content/reservation_reminders'), 'primary' => true],
                ['label' => 'Réglages', 'url' => admin_url('restoline/content/reservation_reminder_settings')],
            ],
        ],
        [
            'title' => 'Avantages grandes tables',
            'description' => 'Messages liés aux avantages pour paiement en espèces, le soir, le dimanche midi et certains midis.',
            'status' => ($groupSettings->status || $groupSettings->lunch_status) ? 'Actif' : 'Désactivé',
            'statusClass' => ($groupSettings->status || $groupSettings->lunch_status) ? 'badge-success' : 'badge-secondary',
            'detail' => 'Seuil soir : '.$groupSettings->min_guests.' · seuil midi : '.$groupSettings->lunch_min_guests,
            'actions' => [
                ['label' => 'Modifier les avantages', 'url' => admin_url('restoline/content/group_benefit_settings'), 'primary' => true],
            ],
        ],
        [
            'title' => 'Anniversaires et promotions',
            'description' => 'Messages automatiques d’anniversaire et campagnes promotionnelles.',
            'status' => $activeBirthdayCampaigns.' campagne(s) anniversaire active(s)',
            'statusClass' => $activeBirthdayCampaigns > 0 ? 'badge-success' : 'badge-secondary',
            'detail' => 'Les dates et canaux proviennent des contacts newsletter.',
            'actions' => [
                ['label' => 'Gérer les campagnes', 'url' => admin_url('restoline/content/marketing_campaigns'), 'primary' => true],
            ],
        ],
        [
            'title' => 'Avis et suivi clients',
            'description' => 'Retours après visite, problèmes signalés et suivi des demandes.',
            'status' => $feedbackSettings->enabled ? 'Actif' : 'Désactivé',
            'statusClass' => $feedbacksToReview > 0 ? 'badge-warning' : ($feedbackSettings->enabled ? 'badge-success' : 'badge-secondary'),
            'detail' => $feedbackSettings->sample_percent.' % des réservations · pause '.$feedbackSettings->cooldown_days.' jours · '.$feedbacksToReview.' à vérifier',
            'actions' => [
                ['label' => 'Avis et réglages', 'url' => admin_url('restoline/content/reservation_feedbacks'), 'primary' => true],
            ],
        ],
    ],
    'Contacts et conversations' => [
        [
            'title' => 'Contacts newsletter',
            'description' => 'Emails, GSM, consentements et dates d’anniversaire.',
            'status' => $activeSubscribers.' contact(s) actif(s)',
            'statusClass' => 'badge-info',
            'detail' => 'Gestion des inscriptions, désinscriptions et préférences.',
            'actions' => [
                ['label' => 'Ouvrir les contacts', 'url' => admin_url('restoline/content/newsletter_subscribers'), 'primary' => true],
            ],
        ],
        [
            'title' => 'Réservations par SMS',
            'description' => 'Discussions SMS, réservations créées et réponses manuelles de l’équipe.',
            'status' => $smsSettings->enabled ? 'Automatisation active' : 'Automatisation désactivée',
            'statusClass' => $smsNeedsHuman > 0 ? 'badge-warning' : ($smsSettings->enabled ? 'badge-success' : 'badge-secondary'),
            'detail' => $smsNeedsHuman.' conversation(s) à traiter par l’équipe',
            'actions' => [
                ['label' => 'Voir les conversations', 'url' => admin_url('restoline/content/sms_conversations'), 'primary' => true],
                ['label' => 'Réglages SMS', 'url' => admin_url('restoline/content/sms_reservation_settings')],
            ],
        ],
        [
            'title' => 'Messages vocaux',
            'description' => 'Messages laissés sur le répondeur et réservations reconnues.',
            'status' => 'Suivi opérationnel',
            'statusClass' => 'badge-info',
            'detail' => 'Écoute, transcription et ouverture de la réservation associée.',
            'actions' => [
                ['label' => 'Voir les messages', 'url' => admin_url('restoline/content/voice_messages'), 'primary' => true],
                ['label' => 'Statistiques', 'url' => admin_url('restoline/content/telephony_events/summary')],
            ],
        ],
    ],
    'Réglages techniques' => [
        [
            'title' => 'Répondeur téléphonique',
            'description' => 'Messages d’accueil, menu téléphonique et traitement des réservations vocales.',
            'status' => 'Configuration',
            'statusClass' => 'badge-secondary',
            'detail' => 'À modifier uniquement lorsque le fonctionnement du répondeur change.',
            'actions' => [
                ['label' => 'Réglages répondeur', 'url' => admin_url('restoline/content/telephony_settings'), 'primary' => true],
            ],
        ],
        [
            'title' => 'Configuration emailing',
            'description' => 'Fournisseur et expéditeur utilisés pour les campagnes marketing.',
            'status' => 'Configuration',
            'statusClass' => 'badge-secondary',
            'detail' => 'Les confirmations de réservation utilisent leur propre circuit Resend avec secours.',
            'actions' => [
                ['label' => 'Réglages emailing', 'url' => admin_url('restoline/content/email_marketing_settings'), 'primary' => true],
            ],
        ],
        [
            'title' => 'Notifications au restaurant',
            'description' => 'Notifications internes lors des créations, modifications et annulations.',
            'status' => 'Configuration',
            'statusClass' => 'badge-secondary',
            'detail' => 'Canaux WhatsApp, WeChat ou API personnalisée.',
            'actions' => [
                ['label' => 'Régler les notifications', 'url' => admin_url('restoline/content/restaurant_notification_settings'), 'primary' => true],
            ],
        ],
    ],
];
?>

<div class="callout callout-info mb-4">
    Toutes les communications automatiques et les échanges clients sont regroupés ici. Les pages techniques restent disponibles sans encombrer le menu Restoline.
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div class="mb-3">
                <h5 class="card-title mb-1">Confirmations de réservation</h5>
                <p class="text-muted mb-0">Choisissez les canaux utilisés lors d’une confirmation ou d’une annulation.</p>
            </div>
            <span class="badge <?= $clientNotificationSettings->sendsSmsAfterSuccessfulEmail() ? 'badge-success' : 'badge-info'; ?>">
                <?= $clientNotificationSettings->sendsSmsAfterSuccessfulEmail() ? 'Email + SMS' : 'Email, puis SMS si échec'; ?>
            </span>
        </div>

        <form
            method="post"
            data-request="onSaveReservationNotificationSettings"
            data-request-validate
            data-request-flash
        >
            <div class="row align-items-end">
                <div class="col-md-8 col-lg-6">
                    <label class="form-label fw-bold" for="reservation-confirmation-mode">Mode d’envoi</label>
                    <select class="form-select" id="reservation-confirmation-mode" name="confirmation_mode" required>
                        <?php foreach ($clientNotificationSettings->getConfirmationModeOptions() as $value => $label): ?>
                            <option value="<?= e($value); ?>" <?= $clientNotificationSettings->confirmation_mode === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Le mode « Email et SMS » envoie les deux lorsque le client a fourni les deux coordonnées.</small>
                </div>
                <div class="col-md-4 col-lg-3 mt-3 mt-md-0">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save mr-1"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php foreach ($sections as $sectionTitle => $items): ?>
    <h4 class="mb-3 mt-4"><?= e($sectionTitle); ?></h4>
    <div class="row">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-xl-4 d-flex">
                <div class="card mb-3 w-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= e($item['title']); ?></h5>
                            <span class="badge <?= e($item['statusClass']); ?> ml-2"><?= e($item['status']); ?></span>
                        </div>
                        <p class="text-muted mb-2"><?= e($item['description']); ?></p>
                        <p class="small mb-3"><?= e($item['detail']); ?></p>
                        <div class="mt-auto">
                            <?php foreach ($item['actions'] as $action): ?>
                                <a class="btn <?= !empty($action['primary']) ? 'btn-primary' : 'btn-light'; ?> btn-sm mr-1 mb-1" href="<?= e($action['url']); ?>">
                                    <?= e($action['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
