<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher réservation, email ou téléphone',
        'mode' => 'all',
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/reservation_reminders',
        ],
        'settings' => [
            'label' => 'Réglages des rappels',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/reservation_reminder_settings',
        ],
    ],
];

$config['list']['columns'] = [
    'reserve_date' => ['label' => 'Date venue', 'type' => 'date', 'sortable' => true],
    'reservation_id' => ['label' => 'Réservation', 'type' => 'number', 'searchable' => true, 'sortable' => true],
    'reservation_action' => [
        'label' => 'Ouvrir',
        'type' => 'partial',
        'path' => 'reservation_reminders/reservation_link',
        'sortable' => false,
    ],
    'email' => ['label' => 'Email', 'type' => 'text', 'searchable' => true],
    'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'searchable' => true],
    'email_status_label' => ['label' => 'Email', 'type' => 'text', 'sortable' => false],
    'sms_status_label' => ['label' => 'SMS', 'type' => 'text', 'sortable' => false],
    'opened_label' => ['label' => 'Lien client', 'type' => 'text', 'sortable' => false],
    'sent_at' => ['label' => 'Envoyé le', 'type' => 'datetime', 'sortable' => true],
];

return $config;
