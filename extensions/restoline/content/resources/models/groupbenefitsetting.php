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
    ],
];

$config['form']['fields'] = [
    'status' => [
        'label' => 'Activer le SMS soir / dimanche midi',
        'type' => 'switch',
        'span' => 'full',
        'comment' => 'Règle actuelle : 6 personnes et plus, tous les soirs et dimanche midi.',
    ],
    'min_guests' => [
        'label' => 'Nombre minimum soir / dimanche midi',
        'type' => 'number',
        'span' => 'left',
        'comment' => 'Exemple : 6 pour envoyer seulement aux réservations de 6 personnes et plus.',
    ],
    'send_for_dinner' => [
        'label' => 'Envoyer tous les soirs',
        'type' => 'switch',
        'span' => 'right',
        'comment' => 'Concerne les réservations à partir de 18:00.',
    ],
    'send_for_sunday_lunch' => [
        'label' => 'Envoyer le dimanche midi',
        'type' => 'switch',
        'span' => 'right',
        'comment' => 'Concerne les réservations du dimanche entre 12:00 et 16:00.',
    ],
    'sms_message' => [
        'label' => 'Message SMS soir / dimanche midi',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Le SMS est envoyé séparément de la confirmation. Le système retire les accents automatiquement avant envoi.',
    ],
    'lunch_status' => [
        'label' => 'Activer le SMS midi mardi-samedi',
        'type' => 'switch',
        'span' => 'full',
        'comment' => 'Règle midi : 8 personnes et plus, uniquement les midis du mardi au samedi.',
    ],
    'lunch_min_guests' => [
        'label' => 'Nombre minimum midi mardi-samedi',
        'type' => 'number',
        'span' => 'left',
        'comment' => 'Exemple : 8 pour envoyer seulement aux réservations du midi de 8 personnes et plus.',
    ],
    'lunch_sms_message' => [
        'label' => 'Message SMS midi mardi-samedi',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Le SMS est envoyé séparément de la confirmation. Le système retire les accents automatiquement avant envoi.',
    ],
];

return $config;
