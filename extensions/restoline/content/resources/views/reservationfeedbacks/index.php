<div class="d-flex p-3">
    <a class="btn shadow-none border-none ps-0" href="<?= admin_url('restoline/content/client_communications'); ?>">
        <i class="fa fa-angle-left fs-4 align-bottom"></i>
    </a>
    <h4 class="page-title mb-0 lh-base">Avis et suivi clients</h4>
</div>

<div class="mx-3 mb-3" id="feedback-settings">
    <details class="card shadow-sm" open>
        <summary class="card-header d-flex align-items-center justify-content-between" style="cursor:pointer;list-style:none;">
            <span>
                <strong>Réglages des demandes d’avis</strong>
                <span class="text-muted ms-2">
                    <?= $feedbackSettings->enabled ? 'Actif' : 'Désactivé'; ?> ·
                    <?= (int)$feedbackSettings->sample_percent; ?> % ·
                    pause <?= (int)$feedbackSettings->cooldown_days; ?> jours
                </span>
            </span>
            <span class="btn btn-light btn-sm">Afficher / masquer</span>
        </summary>

        <form
            class="card-body"
            method="post"
            data-request="onSaveSettings"
            data-request-validate
            data-request-flash
        >
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="hidden" name="enabled" value="0">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="feedback-enabled" <?= $feedbackSettings->enabled ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="feedback-enabled">Activer les demandes de retour</label>
                    </div>
                    <small class="text-muted">Réservations passées, confirmées et avec une adresse email.</small>
                </div>

                <div class="col-md-6">
                    <input type="hidden" name="google_review_enabled" value="0">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="google_review_enabled" value="1" id="feedback-google" <?= $feedbackSettings->google_review_enabled ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="feedback-google">Proposer aussi l’avis Google</label>
                    </div>
                    <small class="text-muted">Le même lien est proposé à tous les clients sélectionnés.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold" for="feedback-percent">Clients contactés</label>
                    <select class="form-select" id="feedback-percent" name="sample_percent" required>
                        <?php foreach ($feedbackSettings->getSamplePercentOptions() as $value => $label): ?>
                            <option value="<?= (int)$value; ?>" <?= (int)$feedbackSettings->sample_percent === (int)$value ? 'selected' : ''; ?>><?= e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold" for="feedback-delay">Délai après la visite</label>
                    <div class="input-group">
                        <input class="form-control" id="feedback-delay" name="days_after_visit" type="number" min="1" max="7" value="<?= (int)$feedbackSettings->days_after_visit; ?>" required>
                        <span class="input-group-text">jour(s)</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold" for="feedback-cooldown">Pause par client</label>
                    <div class="input-group">
                        <input class="form-control" id="feedback-cooldown" name="cooldown_days" type="number" min="0" max="365" value="<?= (int)$feedbackSettings->cooldown_days; ?>" required>
                        <span class="input-group-text">jours</span>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold" for="feedback-subject">Sujet de l’email</label>
                    <input class="form-control" id="feedback-subject" name="email_subject" maxlength="190" value="<?= e($feedbackSettings->email_subject); ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold" for="feedback-heading">Titre dans l’email</label>
                    <input class="form-control" id="feedback-heading" name="email_heading" maxlength="190" value="<?= e($feedbackSettings->email_heading); ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold" for="feedback-message">Texte de l’email</label>
                    <textarea class="form-control" id="feedback-message" name="email_message" rows="3" maxlength="1500" required><?= e($feedbackSettings->email_message); ?></textarea>
                    <small class="text-muted">Le prénom et les boutons sont ajoutés automatiquement.</small>
                </div>
            </div>

            <button class="btn btn-primary mt-3" type="submit">
                <i class="fa fa-save me-1"></i> Enregistrer les réglages
            </button>
        </form>
    </details>
</div>

<div class="row-fluid">
    <div class="card shadow-sm mx-3">
        <div class="border-bottom">
            <div class="row">
                <div class="col-lg-9 pe-0">
                    <?= $this->renderListToolbar(); ?>
                </div>
                <div class="col-lg-3">
                    <?= $this->renderListFilter(); ?>
                </div>
            </div>
        </div>
        <?= $this->renderList(null, true); ?>
    </div>
</div>
