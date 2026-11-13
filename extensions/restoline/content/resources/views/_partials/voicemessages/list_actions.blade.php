@php
    $reservationUrl = (string)($record->reservation_admin_url ?? '');
    $manualUrl = (string)($record->manual_reservation_url ?? '');
@endphp

@if ($reservationUrl !== '')
    <a class="btn btn-sm btn-light" href="{{ $reservationUrl }}">Ouvrir</a>
@elseif ($manualUrl !== '')
    <a class="btn btn-sm btn-primary" href="{{ $manualUrl }}">Créer réservation</a>
@else
    <span class="text-muted">-</span>
@endif
