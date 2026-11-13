<?php

declare(strict_types=1);

$config['form']['toolbar'] = [
    'buttons' => [
        'save' => [
            'label' => 'Enregistrer',
            'context' => ['create', 'edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'Enregistrement...',
        ],
        'qr-codes' => [
            'label' => 'Codes QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_access_codes'),
        ],
        'qr-orders' => [
            'label' => 'Commandes QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_orders'),
        ],
    ],
];

$config['form']['fields'] = [
    'status' => [
        'label' => 'Activer la commande à table',
        'type' => 'switch',
        'span' => 'full',
        'comment' => 'Si désactivé, les clients verront le menu mais ne pourront pas envoyer une commande.',
    ],
    'monday_hours' => [
        'label' => 'Lundi',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'Laisser vide si fermé. Exemple : 18:00-22:00',
    ],
    'tuesday_hours' => [
        'label' => 'Mardi',
        'type' => 'text',
        'span' => 'right',
        'comment' => 'Exemple : 18:00-22:00',
    ],
    'wednesday_hours' => [
        'label' => 'Mercredi',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'Exemple : 18:00-22:00',
    ],
    'thursday_hours' => [
        'label' => 'Jeudi',
        'type' => 'text',
        'span' => 'right',
        'comment' => 'Exemple : 18:00-22:00',
    ],
    'friday_hours' => [
        'label' => 'Vendredi',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'Exemple : 18:00-23:00',
    ],
    'saturday_hours' => [
        'label' => 'Samedi',
        'type' => 'text',
        'span' => 'right',
        'comment' => 'Exemple : 18:00-23:00',
    ],
    'sunday_hours' => [
        'label' => 'Dimanche',
        'type' => 'text',
        'span' => 'full',
        'comment' => 'Plusieurs créneaux possibles, séparés par une virgule. Exemple : 12:00-15:00, 18:00-22:00',
    ],
    'qr_category_ids' => [
        'label' => 'Catégories affichées dans le QR',
        'type' => 'checkboxlist',
        'span' => 'full',
        'options' => 'getQrCategoryIdsOptions',
        'comment' => 'Cochez uniquement les catégories qui doivent apparaître sur la page /commander. Pour masquer une catégorie, décochez-la puis enregistrez.',
    ],
    'closed_message' => [
        'label' => 'Message hors horaire',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Message affiché au client quand la commande à table est fermée.',
    ],
];

return $config;
