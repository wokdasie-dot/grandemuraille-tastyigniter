<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'Ajouter une configuration',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/restaurant_notification_settings/create',
        ],
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/restaurant_notification_settings/edit/{setting_id}',
        ],
    ],
    'name' => [
        'label' => 'Nom',
        'type' => 'text',
        'searchable' => true,
    ],
    'channel_label' => [
        'label' => 'Canal',
        'type' => 'text',
    ],
    'provider_label' => [
        'label' => 'Fournisseur',
        'type' => 'text',
    ],
    'recipient' => [
        'label' => 'Destinataire',
        'type' => 'text',
    ],
    'secret_status' => [
        'label' => 'Secrets',
        'type' => 'text',
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
            'data-request-confirm' => 'Supprimer cette configuration ?',
        ],
    ],
];

$config['form']['fields'] = [
    'name' => [
        'label' => 'Nom',
        'type' => 'text',
        'span' => 'left',
        'default' => 'Notifications restaurant',
    ],
    'channel' => [
        'label' => 'Canal',
        'type' => 'select',
        'options' => 'getChannelOptions',
        'span' => 'right',
    ],
    'provider' => [
        'label' => 'Fournisseur',
        'type' => 'select',
        'options' => 'getProviderOptions',
        'span' => 'left',
        'comment' => 'Pour WeCom robot de groupe, indiquez l URL du webhook : le site enverra le JSON brut dans un message texte WeCom.',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
        'span' => 'right',
        'default' => false,
        'comment' => 'Activez seulement après test du fournisseur. Cette page configure le canal; le branchement d envoi doit être validé selon le fournisseur choisi.',
    ],
];

$config['form']['tabs']['fields'] = [
    'recipient' => [
        'label' => 'Destinataire restaurant',
        'type' => 'text',
        'tab' => 'Destinataire',
        'span' => 'left',
        'placeholder' => 'Ex: whatsapp:+324... ou openid WeChat',
        'comment' => 'Numéro WhatsApp du restaurateur ou identifiant WeChat destinataire selon le fournisseur.',
    ],
    'sender' => [
        'label' => 'Expéditeur',
        'type' => 'text',
        'tab' => 'Destinataire',
        'span' => 'right',
        'placeholder' => 'Ex: whatsapp:+1415... ou app/account id',
    ],
    'notify_new_reservation' => [
        'label' => 'Nouvelle réservation',
        'type' => 'switch',
        'tab' => 'Événements',
        'span' => 'left',
        'default' => true,
    ],
    'notify_modified_reservation' => [
        'label' => 'Réservation modifiée',
        'type' => 'switch',
        'tab' => 'Événements',
        'span' => 'right',
        'default' => true,
    ],
    'notify_cancelled_reservation' => [
        'label' => 'Réservation annulée',
        'type' => 'switch',
        'tab' => 'Événements',
        'span' => 'left',
        'default' => true,
    ],
    'api_base_url' => [
        'label' => 'URL API personnalisée',
        'type' => 'text',
        'tab' => 'Connexion API',
        'comment' => 'Pour WeCom robot de groupe, collez ici l URL du webhook. Le JSON brut sera envoyé dans text.content.',
    ],
    'account_id' => [
        'label' => 'Identifiant compte / App ID',
        'type' => 'text',
        'tab' => 'Connexion API',
    ],
    'api_secret' => [
        'label' => 'Secret API',
        'type' => 'password',
        'tab' => 'Connexion API',
        'commentAbove' => 'Stocké chiffré. Laissez vide lors d une modification pour conserver la valeur actuelle.',
    ],
    'access_token' => [
        'label' => 'Token accès',
        'type' => 'password',
        'tab' => 'Connexion API',
        'commentAbove' => 'Stocké chiffré. Laissez vide lors d une modification pour conserver la valeur actuelle.',
    ],
    'message_template' => [
        'label' => 'Modèle de message restaurateur',
        'type' => 'textarea',
        'tab' => 'Message',
        'size' => 'large',
        'commentAbove' => 'Utilisé seulement par les fournisseurs qui demandent un message texte. Pour WeCom robot de groupe, ce champ est ignoré : le JSON brut est envoyé dans text.content.',
    ],
];

return $config;
