<?php
$audioUrl = (string)($model->audio_dashboard_url ?? '');
$twilioUrl = (string)($model->audio_link ?? '');
?>

<?php if ($audioUrl !== '') { ?>
    <div class="card p-3">
        <audio controls preload="metadata" style="width: 100%;">
            <source src="<?= e($audioUrl) ?>" type="audio/mpeg">
        </audio>
        <p class="mt-2 mb-0">
            <a class="btn btn-light" href="<?= e($audioUrl) ?>" target="_blank" rel="noopener">
                Ouvrir / télécharger l’audio
            </a>
        </p>
    </div>
<?php } else { ?>
    <div class="alert alert-warning mb-0">
        Audio local pas encore disponible. Le traitement IA va le télécharger automatiquement.
        <?php if ($twilioUrl !== '') { ?>
            <br>Lien Twilio brut: <code><?= e($twilioUrl) ?></code>
        <?php } ?>
    </div>
<?php } ?>
