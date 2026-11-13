<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher un SID, statut ou réservation',
        'mode' => 'all',
    ],
    'scopes' => [
        'channel' => [
            'label' => 'Canal',
            'type' => 'select',
            'modelClass' => \Restoline\Content\Models\TelephonyEvent::class,
            'options' => 'getChannelOptions',
            'conditions' => 'channel = :filtered',
        ],
        'event_type' => [
            'label' => 'Événement',
            'type' => 'select',
            'modelClass' => \Restoline\Content\Models\TelephonyEvent::class,
            'options' => 'getEventTypeOptions',
            'conditions' => 'event_type = :filtered',
        ],
        'created_at' => [
            'label' => 'Date',
            'type' => 'date',
            'conditions' => 'DATE(created_at) = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'summary' => [
            'label' => 'Résumé mensuel',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/telephony_events/summary',
        ],
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/telephony_events',
        ],
    ],
];

$config['list']['columns'] = [
    'created_at' => [
        'label' => 'Date',
        'type' => 'datetime',
    ],
    'channel_label' => [
        'label' => 'Canal',
        'type' => 'text',
        'sortable' => false,
    ],
    'event_label' => [
        'label' => 'Événement',
        'type' => 'text',
        'sortable' => false,
    ],
    'status' => [
        'label' => 'Statut',
        'type' => 'text',
    ],
    'menu_digit' => [
        'label' => 'Touche',
        'type' => 'text',
    ],
    'duration_seconds' => [
        'label' => 'Durée appel',
        'type' => 'number',
    ],
    'reservation_id' => [
        'label' => 'Réservation',
        'type' => 'number',
        'searchable' => true,
    ],
    'payload_summary' => [
        'label' => 'Détails utiles',
        'type' => 'text',
        'sortable' => false,
    ],
    'call_sid' => [
        'label' => 'Call SID',
        'type' => 'text',
        'invisible' => true,
        'searchable' => true,
    ],
    'message_sid' => [
        'label' => 'SMS SID',
        'type' => 'text',
        'invisible' => true,
        'searchable' => true,
    ],
];

$config['list']['perPage'] = 25;

return $config;
