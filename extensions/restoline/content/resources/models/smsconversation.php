<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'Rechercher téléphone, message ou réservation',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'Statut',
            'type' => 'select',
            'modelClass' => \Restoline\Content\Models\SmsConversation::class,
            'options' => 'getStatusOptions',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'refresh' => [
            'label' => 'Actualiser',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/sms_conversations',
        ],
        'toggle_automation' => [
            'label' => 'Activer / désactiver les SMS',
            'class' => 'btn btn-outline-primary',
            'data-request' => 'onToggleAutomation',
        ],
        'settings' => [
            'label' => 'Réglages SMS',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/sms_reservation_settings',
        ],
    ],
];

$config['list']['columns'] = [
    'last_message_at' => [
        'label' => 'Dernier message',
        'type' => 'datetime',
    ],
    'status_label' => [
        'label' => 'Statut',
        'type' => 'text',
        'sortable' => false,
    ],
    'delivery_status_label' => [
        'label' => 'Suivi SMS',
        'type' => 'text',
        'sortable' => false,
    ],
    'phone' => [
        'label' => 'GSM',
        'type' => 'text',
        'searchable' => true,
    ],
    'customer_name' => [
        'label' => 'Client',
        'type' => 'text',
        'searchable' => true,
    ],
    'draft_summary' => [
        'label' => 'Infos reconnues',
        'type' => 'text',
        'sortable' => false,
    ],
    'reservation_id' => [
        'label' => 'Réservation',
        'type' => 'number',
        'searchable' => true,
    ],
    'reservation_action' => [
        'label' => 'Action',
        'type' => 'partial',
        'path' => 'sms_conversations/list_reservation',
        'sortable' => false,
    ],
    'last_message_preview' => [
        'label' => 'Dernier SMS',
        'type' => 'text',
        'searchable' => false,
        'sortable' => false,
    ],
    'preview' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-eye',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/sms_conversations/preview/{conversation_id}',
        ],
    ],
];

$config['form']['fields'] = [
    'phone' => [
        'label' => 'GSM client',
        'type' => 'text',
        'span' => 'left',
        'readOnly' => true,
    ],
    'status_label' => [
        'label' => 'Statut',
        'type' => 'text',
        'span' => 'right',
        'readOnly' => true,
    ],
    'delivery_status_label' => [
        'label' => 'Suivi du dernier SMS',
        'type' => 'text',
        'span' => 'right',
        'readOnly' => true,
    ],
    'customer_name' => [
        'label' => 'Nom reconnu',
        'type' => 'text',
        'span' => 'left',
        'readOnly' => true,
    ],
    'reservation_id' => [
        'label' => 'Réservation créée',
        'type' => 'number',
        'span' => 'right',
        'readOnly' => true,
    ],
    'reservation_admin_url' => [
        'label' => 'Lien réservation dashboard',
        'type' => 'partial',
        'path' => 'sms_conversations/reservation_link',
        'span' => 'full',
    ],
    'draft_summary' => [
        'label' => 'Infos reconnues',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
    ],
    'last_message' => [
        'label' => 'Dernier message',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
    ],
    'messages_summary' => [
        'label' => 'Discussion SMS',
        'type' => 'textarea',
        'span' => 'full',
        'readOnly' => true,
        'attributes' => ['rows' => 18, 'style' => 'min-height: 420px; font-family: monospace;'],
    ],
    'manual_reply' => [
        'label' => 'Réponse de l’équipe',
        'type' => 'partial',
        'path' => 'sms_conversations/reply',
        'span' => 'full',
    ],
]; 

$config['form']['toolbar'] = [
    'buttons' => [
        'back' => [
            'label' => 'Retour aux conversations',
            'class' => 'btn btn-light',
            'href' => 'restoline/content/sms_conversations',
        ],
    ],
];

return $config;
