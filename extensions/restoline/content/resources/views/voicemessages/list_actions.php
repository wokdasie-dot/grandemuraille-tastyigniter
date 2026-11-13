<?php

$reservationUrl = (string)($record->reservation_admin_url ?? '');
$manualUrl = (string)($record->manual_reservation_url ?? '');
?>

<?php if ($reservationUrl !== '') { ?>
    <a class="btn btn-sm btn-light" href="<?= e($reservationUrl) ?>">Ouvrir</a>
<?php } elseif ($manualUrl !== '') { ?>
    <a class="btn btn-sm btn-primary" href="<?= e($manualUrl) ?>">Créer réservation</a>
<?php } else { ?>
    <span class="text-muted">-</span>
<?php } ?>
