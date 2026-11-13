<?php

declare(strict_types=1);

$config['form']['toolbar'] = [
    'buttons' => [
        'save' => [
            'label' => 'Enregistrer',
            'context' => ['edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'Enregistrement...',
        ],
        'history' => [
            'label' => 'Historique des rappels',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/reservation_reminders',
        ],
    ],
];

$config['form']['fields'] = [
    'enabled' => [
        'label' => 'Activer les rappels grandes tables',
        'type' => 'switch',
        'span' => 'full',
        'comment' => "Une seule tâche quotidienne traite les réservations du lendemain.",
    ],
    'min_guests' => [
        'label' => 'Nombre minimum de personnes',
        'type' => 'number',
        'span' => 'left',
        'comment' => '6 signifie : envoyer aux réservations de 6 personnes et plus.',
    ],
    'email_enabled' => [
        'label' => 'Envoyer aussi par email',
        'type' => 'switch',
        'span' => 'left',
    ],
    'sms_enabled' => [
        'label' => 'Envoyer aussi par SMS',
        'type' => 'switch',
        'span' => 'right',
    ],
    'email_subject' => [
        'label' => "Sujet de l'email",
        'type' => 'text',
        'span' => 'full',
    ],
    'email_message' => [
        'label' => "Texte de l'email",
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Les informations de la réservation et le bouton de gestion sont ajoutés automatiquement.',
    ],
    'sms_message' => [
        'label' => 'Texte du SMS',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Variables : {date}, {time}, {guests}, {url}. Gardez {url} afin que le lien personnel court soit inclus.',
    ],
];

return $config;
