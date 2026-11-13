<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'Ajouter une promotion',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/marketing_campaigns/create',
        ],
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/marketing_campaigns/edit/{campaign_id}',
        ],
    ],
    'title' => [
        'label' => 'Titre',
        'type' => 'text',
        'searchable' => true,
    ],
    'type_label' => [
        'label' => 'Type',
        'type' => 'text',
    ],
    'channel_label' => [
        'label' => 'Canal',
        'type' => 'text',
    ],
    'birthday_days_before' => [
        'label' => 'Jours avant anniversaire',
        'type' => 'number',
    ],
    'valid_days' => [
        'label' => 'Validité',
        'type' => 'number',
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
            'data-request-confirm' => 'Supprimer cette promotion ?',
        ],
    ],
];

$config['form']['fields'] = [
    'title' => [
        'label' => 'Titre',
        'type' => 'text',
        'span' => 'left',
    ],
    'type' => [
        'label' => 'Type',
        'type' => 'select',
        'options' => 'getTypeOptions',
        'span' => 'right',
    ],
    'channel' => [
        'label' => 'Canal',
        'type' => 'select',
        'options' => 'getChannelOptions',
        'span' => 'left',
        'comment' => 'Pour la newsletter anniversaire, utilisez "Canal choisi par le client" : chaque inscrit reçoit soit email, soit SMS selon son choix.',
    ],
    'status' => [
        'label' => 'Actif',
        'type' => 'switch',
        'span' => 'right',
        'default' => true,
    ],
    'birthday_days_before' => [
        'label' => 'Envoyer combien de jours avant anniversaire',
        'type' => 'number',
        'span' => 'left',
        'default' => 3,
    ],
    'valid_days' => [
        'label' => 'Offre valable combien de jours',
        'type' => 'number',
        'span' => 'right',
        'default' => 10,
    ],
];

$config['form']['tabs']['fields'] = [
    'email_subject' => [
        'label' => 'Sujet email',
        'type' => 'text',
        'tab' => 'Message email',
    ],
    'email_body' => [
        'label' => 'Texte email',
        'type' => 'textarea',
        'tab' => 'Message email',
        'size' => 'large',
        'commentAbove' => 'Variables disponibles : {prenom}, {date_debut}, {date_fin}, {desinscription}. Configurez le service externe dans Configuration emailing avant tout envoi email marketing massif.',
    ],
    'sms_body' => [
        'label' => 'Texte SMS',
        'type' => 'textarea',
        'tab' => 'Message SMS',
        'size' => 'small',
        'commentAbove' => 'Variables disponibles : {prenom}, {date_debut}, {date_fin}, {desinscription}. Dans les SMS, {desinscription} utilise toujours la page générale de désinscription, pas un lien unique.',
    ],
];

return $config;
