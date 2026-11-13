<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher un numéro, transcript ou réservation',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'Statut',
            'type' => 'select',
            'modelClass' => \Restoline\Content\Models\VoiceMessage::class,
            'options' => 'getStatusOptions',
            'conditions' => 'status = :filtered',
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
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/voice_messages',
        ],
    ],
];

$config['list']['columns'] = [
    'created_at' => [
        'label' => 'Reçu le',
        'type' => 'datetime',
    ],
    'status_label' => [
        'label' => 'Statut',
        'type' => 'text',
        'sortable' => false,
    ],
    'caller_label' => [
        'label' => 'Numéro appelant',
        'type' => 'text',
        'sortable' => false,
        'searchable' => true,
    ],
    'recording_duration' => [
        'label' => 'Durée',
        'type' => 'number',
    ],
    'audio_dashboard_url' => [
        'label' => 'Audio',
        'type' => 'partial',
        'path' => 'voicemessages/list_audio',
        'sortable' => false,
    ],
    'transcript' => [
        'label' => 'Message transcrit',
        'type' => 'text',
        'searchable' => true,
    ],
    'extracted_summary' => [
        'label' => 'Infos reconnues',
        'type' => 'text',
        'sortable' => false,
    ],
    'confidence_label' => [
        'label' => 'Confiance IA',
        'type' => 'text',
        'sortable' => false,
    ],
    'reservation_id' => [
        'label' => 'Réservation',
        'type' => 'number',
        'searchable' => true,
    ],
    'actions' => [
        'label' => 'Action',
        'type' => 'partial',
        'path' => 'voicemessages/list_actions',
        'sortable' => false,
    ],
    'preview' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-eye',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/voice_messages/preview/{message_id}',
        ],
    ],
];

$config['form']['fields'] = [
    'created_label' => [
        'label' => 'Reçu le',
        'type' => 'text',
        'span' => 'left',
        'readOnly' => true,
    ],
    'status_label' => [
        'label' => 'Statut',
        'type' => 'text',
        'span' => 'right',
        'readOnly' => true,
    ],
    'caller_phone' => [
        'label' => 'Numéro appelant',
        'type' => 'text',
        'span' => 'left',
        'readOnly' => true,
    ],
    'recording_duration' => [
        'label' => 'Durée en secondes',
        'type' => 'number',
        'span' => 'right',
        'readOnly' => true,
    ],
    'audio_dashboard_url' => [
        'label' => 'Écouter le message vocal',
        'type' => 'text',
        'span' => 'full',
        'readOnly' => true,
        'comment' => 'Ouvrez ce lien dans un nouvel onglet pour écouter le message.',
    ],
    'transcript' => [
        'label' => 'Transcript',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
    ],
    'extracted_summary' => [
        'label' => 'Informations reconnues',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
    ],
    'error_message' => [
        'label' => 'Erreur / raison vérification humaine',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
    ],
    'reservation_id' => [
        'label' => 'Réservation créée',
        'type' => 'number',
        'span' => 'left',
        'readOnly' => true,
    ],
    'manual_reservation_url' => [
        'label' => 'Créer une réservation manuellement',
        'type' => 'text',
        'span' => 'full',
        'readOnly' => true,
        'comment' => 'Ouvrez ce lien pour créer une réservation avec les informations reconnues depuis ce message.',
    ],
    'processed_label' => [
        'label' => 'Traité le',
        'type' => 'text',
        'span' => 'right',
        'readOnly' => true,
    ],
];

return $config;
