@if ($model->reservation_id && $model->reservation_admin_url)
    <a href="{{ $model->reservation_admin_url }}" class="btn btn-sm btn-primary">
        Ouvrir la réservation #{{ $model->reservation_id }}
    </a>
@else
    <span class="text-muted">Aucune réservation créée</span>
@endif
