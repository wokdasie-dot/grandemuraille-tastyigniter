@php
    $audioUrl = (string)($record->audio_dashboard_url ?? '');
@endphp

@if ($audioUrl !== '')
    <audio controls preload="none" style="width: 150px; height: 32px; vertical-align: middle;">
        <source src="{{ $audioUrl }}" type="audio/mpeg">
    </audio>
@else
    <span class="text-muted">En préparation</span>
@endif
