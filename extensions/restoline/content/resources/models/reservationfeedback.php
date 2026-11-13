<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher réservation, nom, email ou message',
        'mode' => 'all',
    ],
    'scopes' => [
        'reservation_channel' => [
            'label' => 'Canal',
            'type' => 'select',
            'conditions' => 'reservation_channel = :filtered',
            'options' => [
                'online' => 'En ligne',
                'phone' => 'Téléphone',
                'unknown' => 'Non précisé',
            ],
        ],
        'reservation_answer' => [
            'label' => 'Réservation',
            'type' => 'select',
            'conditions' => 'reservation_answer = :filtered',
            'options' => [
                'ok' => 'OK',
                'bug' => 'Bug',
            ],
        ],
        'follow_up_status' => [
            'label' => 'Suivi',
            'type' => 'select',
            'conditions' => 'follow_up_status = :filtered',
            'options' => [
                'new' => 'À vérifier',
                'contacted' => 'Client contacté',
                'resolved' => 'Résolu',
                'ignored' => 'Sans action',
            ],
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/reservation_feedbacks',
        ],
    ],
];

$config['list']['columns'] = [
    'feedback_id' => ['label' => 'ID', 'type' => 'number', 'sortable' => true],
    'reservation_id' => ['label' => 'Réservation', 'type' => 'number', 'searchable' => true, 'sortable' => true],
    'customer_name' => ['label' => 'Client', 'type' => 'text', 'searchable' => true],
    'email' => ['label' => 'Email', 'type' => 'text', 'searchable' => true],
    'reserve_date' => ['label' => 'Date venue', 'type' => 'date', 'sortable' => true],
    'reservation_channel_label' => ['label' => 'Canal', 'type' => 'text', 'sortable' => false],
    'visit_answer_label' => ['label' => 'Visite', 'type' => 'text', 'sortable' => false],
    'reservation_answer_label' => ['label' => 'Réservation', 'type' => 'text', 'sortable' => false],
    'problem_type' => ['label' => 'Problème', 'type' => 'text', 'searchable' => true],
    'message' => ['label' => 'Message', 'type' => 'text', 'searchable' => true],
    'follow_up_status_label' => ['label' => 'Suivi', 'type' => 'text', 'sortable' => false],
    'sent_label' => ['label' => 'Envoyé', 'type' => 'text', 'sortable' => false],
    'responded_label' => ['label' => 'Répondu', 'type' => 'text', 'sortable' => false],
    'google_clicked_label' => ['label' => 'Clic avis Google', 'type' => 'text', 'sortable' => false],
];

$config['form']['fields'] = [
    'reservation_id' => ['label' => 'Réservation', 'type' => 'number', 'disabled' => true],
    'customer_name' => ['label' => 'Client', 'type' => 'text', 'disabled' => true],
    'email' => ['label' => 'Email', 'type' => 'text', 'disabled' => true],
    'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'disabled' => true],
    'reservation_channel_label' => ['label' => 'Canal réservation', 'type' => 'text', 'disabled' => true],
    'visit_answer_label' => ['label' => 'Retour visite', 'type' => 'text', 'disabled' => true],
    'reservation_answer_label' => ['label' => 'Retour réservation', 'type' => 'text', 'disabled' => true],
    'problem_type' => ['label' => 'Type de problème', 'type' => 'text', 'disabled' => true, 'span' => 'left'],
    'google_clicked_label' => ['label' => 'A cliqué sur avis Google', 'type' => 'text', 'disabled' => true, 'span' => 'right'],
    'message' => ['label' => 'Message client', 'type' => 'textarea', 'disabled' => true],
    'follow_up_status' => [
        'label' => 'Suivi restaurant',
        'type' => 'select',
        'options' => 'getFollowUpStatusOptions',
        'span' => 'left',
    ],
    'followed_up_label' => ['label' => 'Dernier suivi', 'type' => 'text', 'disabled' => true, 'span' => 'right'],
    'follow_up_note' => [
        'label' => 'Note interne',
        'type' => 'textarea',
        'commentAbove' => 'Visible uniquement dans le dashboard.',
    ],
];

return $config;
