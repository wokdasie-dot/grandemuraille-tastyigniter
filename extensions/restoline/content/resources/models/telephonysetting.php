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
        'regenerate-audio' => [
            'label' => 'Régénérer les audios',
            'context' => ['edit'],
            'class' => 'btn btn-default',
            'data-request' => 'onRegenerateVoiceAudio',
            'data-request-confirm' => 'Régénérer tous les fichiers audio du répondeur avec les textes sauvegardés ?',
            'data-progress-indicator' => 'Génération des audios...',
        ],
    ],
];

$config['form']['fields'] = [
    'voice_audio_status' => [
        'label' => 'Statut fichiers audio',
        'type' => 'text',
        'span' => 'full',
        'disabled' => true,
        'comment' => 'Après modification d’un texte, cliquez sur Enregistrer. Les fichiers manquants sont générés automatiquement.',
    ],
    'status' => [
        'label' => 'Activer le répondeur téléphonique',
        'type' => 'switch',
        'span' => 'full',
        'comment' => 'Si désactivé dans le futur, le répondeur pourra être basculé vers un message simple.',
    ],
    'welcome_message' => [
        'label' => 'Annonce d’accueil',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Première phrase entendue par le client.',
    ],
    'annual_leave_enabled' => [
        'label' => 'Lire l’annonce congés annuels',
        'type' => 'switch',
        'span' => 'left',
    ],
    'annual_leave_message' => [
        'label' => 'Annonce congés annuels',
        'type' => 'textarea',
        'span' => 'right',
        'comment' => 'Désactivez le switch quand les congés sont terminés.',
    ],
    'main_menu_message' => [
        'label' => 'Options du menu principal',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Exemple : Pour réserver..., tapez 1. Pour les tarifs..., tapez 4.',
    ],
    'phone_reservation_message' => [
        'label' => 'Annonce option réservation téléphone',
        'type' => 'textarea',
        'span' => 'full',
    ],
    'invalid_menu_message' => [
        'label' => 'Message si choix non reconnu',
        'type' => 'textarea',
        'span' => 'full',
    ],
    'tariffs_message' => [
        'label' => 'Annonce tarifs buffet',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Texte vocal lu quand le client tape 4.',
    ],
    'opening_hours_message' => [
        'label' => 'Annonce horaires d’ouverture',
        'type' => 'textarea',
        'span' => 'full',
        'comment' => 'Texte vocal lu quand le client tape 5.',
    ],
    'sms_number_notice' => [
        'label' => 'Annonce si le client appelle le numéro SMS',
        'type' => 'textarea',
        'span' => 'full',
    ],
    'rate_limited_message' => [
        'label' => 'Message limite anti-abus',
        'type' => 'textarea',
        'span' => 'left',
    ],
    'session_limited_message' => [
        'label' => 'Message session trop longue',
        'type' => 'textarea',
        'span' => 'right',
    ],
    'call_limit_per_hour' => [
        'label' => 'Appels max par heure',
        'type' => 'number',
        'span' => 'left',
    ],
    'call_limit_per_day' => [
        'label' => 'Appels max par jour',
        'type' => 'number',
        'span' => 'right',
    ],
    'max_call_seconds' => [
        'label' => 'Durée maximale appel en secondes',
        'type' => 'number',
        'span' => 'left',
    ],
    'max_call_steps' => [
        'label' => 'Nombre maximal d’étapes',
        'type' => 'number',
        'span' => 'right',
    ],
];

return $config;
