<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'Ajouter une configuration',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/email_marketing_settings/create',
        ],
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/email_marketing_settings/edit/{setting_id}',
        ],
    ],
    'name' => [
        'label' => 'Nom',
        'type' => 'text',
        'searchable' => true,
    ],
    'provider_label' => [
        'label' => 'Service',
        'type' => 'text',
    ],
    'sender_email' => [
        'label' => 'Email expediteur',
        'type' => 'text',
    ],
    'list_id' => [
        'label' => 'Liste / audience',
        'type' => 'text',
    ],
    'api_key_status' => [
        'label' => 'Cle API',
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
        'default' => 'Configuration principale',
    ],
    'provider' => [
        'label' => 'Service emailing',
        'type' => 'select',
        'options' => 'getProviderOptions',
        'span' => 'right',
        'comment' => 'Utilisez un service emailing marketing dedie pour les envois massifs, pas la boite email du restaurant.',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
        'span' => 'left',
        'default' => false,
        'comment' => 'Activez uniquement quand le compte externe est pret et valide.',
    ],
];

$config['form']['tabs']['fields'] = [
    'api_key' => [
        'label' => 'Cle API',
        'type' => 'password',
        'tab' => 'Connexion API',
        'commentAbove' => 'La cle est stockee chiffree. Laissez ce champ vide lors d une modification pour conserver la cle actuelle.',
    ],
    'api_base_url' => [
        'label' => 'URL API personnalisee',
        'type' => 'text',
        'tab' => 'Connexion API',
        'comment' => 'A remplir seulement pour une API personnalisee ou un endpoint specifique.',
    ],
    'list_id' => [
        'label' => 'ID liste / audience',
        'type' => 'text',
        'tab' => 'Connexion API',
        'comment' => 'Exemples : ID de liste Brevo/Mailjet ou audience Mailchimp.',
    ],
    'sender_name' => [
        'label' => 'Nom expediteur',
        'type' => 'text',
        'tab' => 'Expediteur',
        'span' => 'left',
        'placeholder' => "La Grande Muraille de Chine",
    ],
    'sender_email' => [
        'label' => 'Email expediteur',
        'type' => 'text',
        'tab' => 'Expediteur',
        'span' => 'right',
        'placeholder' => 'contact@votre-domaine.be',
    ],
    'reply_to_email' => [
        'label' => 'Email de reponse',
        'type' => 'text',
        'tab' => 'Expediteur',
        'span' => 'left',
        'placeholder' => 'contact@votre-domaine.be',
    ],
];

return $config;
