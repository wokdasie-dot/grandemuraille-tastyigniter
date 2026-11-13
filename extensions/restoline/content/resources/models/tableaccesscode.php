<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher une table ou un code',
        'mode' => 'all',
    ],
    'scopes' => [
        'valid_date' => [
            'label' => 'Date',
            'type' => 'date',
            'conditions' => 'valid_date = :filtered',
        ],
        'status' => [
            'label' => 'Actif',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'qr-hours' => [
            'label' => 'Horaires QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_ordering_settings'),
        ],
        'qr-orders' => [
            'label' => 'Commandes QR',
            'class' => 'btn btn-light',
            'href' => admin_url('restoline/content/table_orders'),
        ],
        'generate-today' => [
            'label' => 'Générer les codes du jour',
            'class' => 'btn btn-primary',
            'data-request' => 'onGenerateToday',
            'data-request-confirm' => 'Générer les codes manquants pour aujourd’hui ?',
        ],
        'regenerate-today' => [
            'label' => 'Régénérer tous les codes du jour',
            'class' => 'btn btn-default',
            'data-request' => 'onRegenerateToday',
            'data-request-confirm' => 'Attention : les anciens codes du jour ne fonctionneront plus. Continuer ?',
        ],
        'open-order-page' => [
            'label' => 'Ouvrir la page QR',
            'class' => 'btn btn-default',
            'href' => url('/commander'),
            'target' => '_blank',
        ],
        'print-today' => [
            'label' => 'Imprimer les codes du jour',
            'class' => 'btn btn-default',
            'href' => 'restoline/content/table_access_codes/print_today',
            'target' => '_blank',
        ],
        'edit-menu-items' => [
            'label' => 'Modifier les plats',
            'class' => 'btn btn-default',
            'href' => admin_url('menus'),
        ],
        'edit-menu-categories' => [
            'label' => 'Catégories menu',
            'class' => 'btn btn-default',
            'href' => admin_url('categories'),
        ],
        'download-qr-code' => [
            'label' => 'Télécharger le QR code',
            'class' => 'btn btn-default',
            'href' => url('/storage/media/uploads/qr-codes/wokdasie-qr-commande-table.png'),
            'target' => '_blank',
        ],
        'download-qr-code-svg' => [
            'label' => 'QR code SVG',
            'class' => 'btn btn-default',
            'href' => url('/storage/media/uploads/qr-codes/wokdasie-qr-commande-table.svg'),
            'target' => '_blank',
        ],
        'qr-stocks' => [
            'label' => 'Stocks QR',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/table_access_codes/stocks',
        ],
    ],
];

$config['list']['columns'] = [
    'regenerate' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-sync',
        'attributes' => [
            'class' => 'btn btn-light',
            'data-request' => 'onRegenerateCode',
            'data-request-data' => 'code_id: {code_id}',
            'data-request-confirm' => 'Régénérer le code de cette table ?',
        ],
    ],
    'table_name' => [
        'label' => 'Table',
        'type' => 'text',
        'searchable' => true,
    ],
    'code' => [
        'label' => 'Code 3 chiffres',
        'type' => 'text',
        'searchable' => true,
    ],
    'date_label' => [
        'label' => 'Date',
        'type' => 'text',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
    ],
    'attempts' => [
        'label' => 'Utilisations / essais',
        'type' => 'number',
    ],
    'last_used_at' => [
        'label' => 'Dernière utilisation',
        'type' => 'datetime',
    ],
    'qr_url' => [
        'label' => 'Lien QR unique',
        'type' => 'text',
    ],
];

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
        'delete' => [
            'label' => 'Supprimer',
            'context' => ['edit'],
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-submit' => 'true',
            'data-request-confirm' => 'Supprimer ce code ?',
        ],
    ],
];

$config['form']['fields'] = [
    'table_name' => [
        'label' => 'Table',
        'type' => 'text',
        'span' => 'left',
    ],
    'code' => [
        'label' => 'Code 3 chiffres',
        'type' => 'text',
        'span' => 'right',
    ],
    'valid_date' => [
        'label' => 'Date de validité',
        'type' => 'datepicker',
        'mode' => 'date',
        'span' => 'left',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
        'span' => 'right',
    ],
    'dining_table_id' => [
        'label' => 'ID table interne',
        'type' => 'number',
        'span' => 'left',
        'disabled' => true,
    ],
    'qr_url' => [
        'label' => 'Lien QR unique à imprimer',
        'type' => 'text',
        'span' => 'right',
        'disabled' => true,
    ],
];

return $config;
