<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher un titre ou un texte',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'Active',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'Ajouter une pop-up',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/home_popups/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'Supprimer',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'Supprimer les pop-ups sélectionnées ?',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/home_popups/edit/{popup_id}',
        ],
    ],
    'title' => [
        'label' => 'Titre',
        'type' => 'text',
        'searchable' => true,
    ],
    'period_label' => [
        'label' => 'Période affichage',
        'type' => 'text',
    ],
    'priority' => [
        'label' => 'Priorité',
        'type' => 'number',
    ],
    'status' => [
        'label' => 'Active',
        'type' => 'switch',
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
            'data-request-confirm' => 'Supprimer cette pop-up ?',
        ],
    ],
];

$config['form']['fields'] = [
    'title' => [
        'label' => 'Titre',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'Ex. Congé annuel, Offre spéciale, Information importante.',
    ],
    'status' => [
        'label' => 'Afficher la pop-up',
        'type' => 'switch',
        'span' => 'right',
        'default' => false,
    ],
    'subtitle' => [
        'label' => 'Sous-titre court',
        'type' => 'text',
        'span' => 'left',
    ],
    'priority' => [
        'label' => 'Priorité',
        'type' => 'number',
        'span' => 'right',
        'default' => 0,
        'comment' => 'Si plusieurs pop-ups sont actives, la priorité la plus élevée s’affiche.',
    ],
    'starts_at' => [
        'label' => 'Début affichage',
        'type' => 'datepicker',
        'mode' => 'datetime',
        'span' => 'left',
        'comment' => 'Laissez vide pour afficher immédiatement.',
    ],
    'ends_at' => [
        'label' => 'Fin affichage',
        'type' => 'datepicker',
        'mode' => 'datetime',
        'span' => 'right',
        'comment' => 'Laissez vide si aucune date de fin.',
    ],
    'daily_start_time' => [
        'label' => 'Heure début quotidienne',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'left',
        'comment' => 'Optionnel. Ex. 18:00.',
    ],
    'daily_end_time' => [
        'label' => 'Heure fin quotidienne',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'right',
        'comment' => 'Optionnel. Ex. 23:00.',
    ],
    'dismiss_hours' => [
        'label' => 'Après fermeture, masquer pendant',
        'type' => 'number',
        'span' => 'left',
        'default' => 12,
        'comment' => 'En heures. Mettez 0 pour réafficher à chaque visite.',
    ],
    'button_label' => [
        'label' => 'Texte du bouton',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'Optionnel. Ex. Réserver une table.',
    ],
    'button_url' => [
        'label' => 'Lien du bouton',
        'type' => 'text',
        'span' => 'right',
        'comment' => 'Optionnel. Ex. /wok-d-asie-hornu/reservation ou tel:065783378.',
    ],
    'image_path' => [
        'label' => 'Image',
        'type' => 'mediafinder',
        'useAttachment' => false,
        'mode' => 'inline',
        'commentAbove' => 'Image optionnelle affichée dans la pop-up.',
    ],
];

$config['form']['tabs']['fields'] = [
    'content' => [
        'label' => 'Texte de la pop-up',
        'type' => 'richeditor',
        'tab' => 'Message',
        'cssClass' => 'richeditor-fluid',
        'commentAbove' => 'Vous pouvez écrire le message, ajouter des liens ou mettre du texte en gras.',
    ],
];

return $config;
