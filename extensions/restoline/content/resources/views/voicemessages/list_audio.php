<?php
$audioUrl = (string)($record->audio_dashboard_url ?? '');
?>

<?php if ($audioUrl !== '') { ?>
    <audio controls preload="none" style="width: 180px; max-width: 100%;">
        <source src="<?= e($audioUrl) ?>" type="audio/mpeg">
    </audio>
    <a class="btn btn-light btn-sm mt-1" href="<?= e($audioUrl) ?>" target="_blank" rel="noopener">
        Télécharger
    </a>
<?php } else { ?>
    <?php if ((int)($record->recording_duration ?? 0) > 0 && (int)($record->recording_duration ?? 0) < 6) { ?>
        <span class="text-warning">Message très court, audio en récupération</span>
    <?php } else { ?>
        <span class="text-muted">Audio en préparation</span>
    <?php } ?>
<?php } ?>
