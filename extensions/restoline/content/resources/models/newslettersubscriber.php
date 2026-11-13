<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher email, téléphone ou prénom',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'Actif',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
        'email_consent' => [
            'label' => 'Consentement email',
            'type' => 'switch',
            'conditions' => 'email_consent = :filtered',
        ],
        'sms_consent' => [
            'label' => 'Consentement SMS',
            'type' => 'switch',
            'conditions' => 'sms_consent = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'Ajouter un inscrit',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/newsletter_subscribers/create',
        ],
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/newsletter_subscribers/edit/{subscriber_id}',
        ],
    ],
    'email' => [
        'label' => 'Email',
        'type' => 'text',
        'searchable' => true,
    ],
    'phone' => [
        'label' => 'Téléphone',
        'type' => 'text',
        'searchable' => true,
    ],
    'first_name' => [
        'label' => 'Prénom',
        'type' => 'text',
        'searchable' => true,
    ],
    'birthday_label' => [
        'label' => 'Anniversaire',
        'type' => 'text',
    ],
    'channels_label' => [
        'label' => 'Canaux',
        'type' => 'text',
    ],
    'birthday_offer_used_label' => [
        'label' => 'Offre utilisée',
        'type' => 'text',
    ],
    'consented_at' => [
        'label' => 'Consentement',
        'type' => 'datetime',
    ],
    'status' => [
        'label' => 'Actif',
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
        ],
        'delete' => [
            'label' => 'Supprimer',
            'context' => ['edit'],
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-submit' => 'true',
            'data-request-confirm' => 'Supprimer cet inscrit ?',
        ],
    ],
];

$config['form']['fields'] = [
    'first_name' => [
        'label' => 'Prénom',
        'type' => 'text',
        'span' => 'left',
    ],
    'email' => [
        'label' => 'Email',
        'type' => 'text',
        'span' => 'right',
    ],
    'phone' => [
        'label' => 'Téléphone',
        'type' => 'text',
        'span' => 'left',
    ],
    'birthday_day' => [
        'label' => 'Jour anniversaire',
        'type' => 'number',
        'span' => 'left',
    ],
    'birthday_month' => [
        'label' => 'Mois anniversaire',
        'type' => 'number',
        'span' => 'right',
        'comment' => 'Seuls le jour et le mois sont nécessaires. Ne stockez pas l’année de naissance.',
    ],
    'email_consent' => [
        'label' => 'Canal email',
        'type' => 'switch',
        'span' => 'left',
        'comment' => 'Un inscrit doit utiliser un seul canal marketing : email ou SMS.',
    ],
    'sms_consent' => [
        'label' => 'Canal SMS',
        'type' => 'switch',
        'span' => 'right',
        'comment' => 'Ne pas activer email et SMS en même temps.',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
        'span' => 'left',
        'default' => true,
    ],
    'birthday_offer_used_at' => [
        'label' => 'Offre anniversaire utilisée le',
        'type' => 'datepicker',
        'mode' => 'date',
        'span' => 'right',
        'comment' => 'À remplir lorsque le client utilise réellement son avantage au restaurant.',
    ],
    'birthday_offer_note' => [
        'label' => 'Note utilisation anniversaire',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Exemple : apéritif offert, coupe de mousseux, venu avec email imprimé.',
    ],
    'consented_at' => [
        'label' => 'Date du consentement',
        'type' => 'datepicker',
        'mode' => 'datetime',
        'span' => 'right',
    ],
];

return $config;
