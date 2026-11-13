<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher référence, table ou plat',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'Statut',
            'type' => 'select',
            'conditions' => 'status = :filtered',
            'options' => [
                'new' => 'Nouvelle',
                'preparing' => 'En préparation',
                'done' => 'Terminée',
                'cancelled' => 'Annulée',
            ],
        ],
        'print_status' => [
            'label' => 'Impression',
            'type' => 'select',
            'conditions' => 'print_status = :filtered',
            'options' => [
                'pending' => 'À imprimer',
                'printing' => 'Envoyée à l’imprimante',
                'printed' => 'Imprimée',
                'failed' => 'Erreur impression',
            ],
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'qr-codes' => [
            'label' => 'Codes QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_access_codes'),
        ],
        'qr-hours' => [
            'label' => 'Horaires QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_ordering_settings'),
        ],
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/table_orders',
        ],
    ],
];

$config['list']['columns'] = [
    'print' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-print',
        'sortable' => false,
        'attributes' => [
            'class' => 'btn btn-light',
            'data-request' => 'onMarkPrinted',
            'data-request-data' => 'order_id: {order_id}',
            'data-request-confirm' => 'Marquer cette commande comme imprimée ?',
        ],
    ],
    'reference' => [
        'label' => 'Référence',
        'type' => 'text',
        'searchable' => true,
    ],
    'table_name' => [
        'label' => 'Table',
        'type' => 'text',
        'searchable' => true,
    ],
    'submitted_label' => [
        'label' => 'Reçue le',
        'type' => 'text',
        'valueFrom' => 'submitted_at',
        'sortable' => true,
    ],
    'status_label' => [
        'label' => 'Statut',
        'type' => 'text',
        'sortable' => false,
    ],
    'print_status_label' => [
        'label' => 'Impression',
        'type' => 'text',
        'sortable' => false,
    ],
    'printed_label' => [
        'label' => 'Imprimée le',
        'type' => 'text',
        'sortable' => false,
    ],
    'updated_label' => [
        'label' => 'Dernier changement',
        'type' => 'text',
        'sortable' => false,
    ],
    'items_summary' => [
        'label' => 'Articles',
        'type' => 'text',
        'sortable' => false,
    ],
    'note' => [
        'label' => 'Remarque',
        'type' => 'text',
    ],
];

$config['form']['fields'] = [
    'reference' => [
        'label' => 'Référence',
        'type' => 'text',
        'disabled' => true,
        'span' => 'left',
    ],
    'table_name' => [
        'label' => 'Table',
        'type' => 'text',
        'disabled' => true,
        'span' => 'right',
    ],
    'status' => [
        'label' => 'Statut',
        'type' => 'select',
        'span' => 'left',
        'options' => [
            'new' => 'Nouvelle',
            'preparing' => 'En préparation',
            'done' => 'Terminée',
            'cancelled' => 'Annulée',
        ],
    ],
    'print_status' => [
        'label' => 'Impression',
        'type' => 'select',
        'span' => 'right',
        'options' => [
            'pending' => 'À imprimer',
            'printing' => 'Envoyée à l’imprimante',
            'printed' => 'Imprimée',
            'failed' => 'Erreur impression',
        ],
    ],
    'items_summary' => [
        'label' => 'Articles',
        'type' => 'textarea',
        'disabled' => true,
    ],
    'note' => [
        'label' => 'Remarque client',
        'type' => 'textarea',
        'disabled' => true,
    ],
];

return $config;
