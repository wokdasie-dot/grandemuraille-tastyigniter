@if ($record->reservation_id && $record->reservation_admin_url)
    <a href="{{ $record->reservation_admin_url }}" class="btn btn-sm btn-primary">
        Ouvrir la réservation
    </a>
@else
    <span class="text-muted">-</span>
@endif
