<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'lang:restoline.content::default.admin.content_items.search_prompt',
        'mode' => 'all',
    ],
    'scopes' => [
        'type' => [
            'label' => 'lang:restoline.content::default.admin.common.type',
            'type' => 'select',
            'conditions' => 'type = :filtered',
            'options' => [
                'home' => 'lang:restoline.content::default.admin.content_items.type_home',
                'news' => 'lang:restoline.content::default.admin.content_items.type_news',
                'holiday' => 'lang:restoline.content::default.admin.content_items.type_holiday',
                'takeaway' => 'lang:restoline.content::default.admin.content_items.type_takeaway',
            ],
        ],
        'status' => [
            'label' => 'lang:restoline.content::default.admin.common.published',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:restoline.content::default.admin.content_items.button_create',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/content_items/create',
        ],
        'reservations' => [
            'label' => 'lang:restoline.content::default.admin.content_items.button_reservations',
            'class' => 'btn btn-default',
            'href' => 'reservations',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'lang:restoline.content::default.admin.common.delete',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'lang:restoline.content::default.admin.content_items.confirm_delete_all',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/content_items/edit/{content_id}',
        ],
    ],
    'title' => [
        'label' => 'lang:restoline.content::default.admin.common.title',
        'type' => 'text',
        'searchable' => true,
    ],
    'type_label' => [
        'label' => 'lang:restoline.content::default.admin.common.type',
        'type' => 'text',
    ],
    'published_at' => [
        'label' => 'lang:restoline.content::default.admin.content_items.column_published_at',
        'type' => 'datetime',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.published',
        'type' => 'switch',
    ],
];

$config['form']['toolbar'] = [
    'buttons' => [
        'save' => [
            'label' => 'lang:restoline.content::default.admin.common.save',
            'context' => ['create', 'edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'restoline.content::default.admin.common.saving',
        ],
        'delete' => [
            'label' => 'lang:restoline.content::default.admin.common.delete',
            'context' => ['edit'],
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-submit' => 'true',
            'data-request-confirm' => 'lang:restoline.content::default.admin.content_items.confirm_delete',
        ],
    ],
];

$config['form']['fields'] = [
    'title' => [
        'label' => 'lang:restoline.content::default.admin.common.title',
        'type' => 'text',
        'span' => 'left',
    ],
    'type' => [
        'label' => 'lang:restoline.content::default.admin.common.type',
        'type' => 'select',
        'span' => 'right',
        'options' => 'getTypeOptions',
    ],
    'published_at' => [
        'label' => 'lang:restoline.content::default.admin.content_items.field_published_at',
        'type' => 'datepicker',
        'mode' => 'datetime',
        'span' => 'left',
        'comment' => 'lang:restoline.content::default.admin.content_items.comment_published_at',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.published',
        'type' => 'switch',
        'span' => 'right',
        'default' => true,
    ],
    'image_path' => [
        'label' => 'lang:restoline.content::default.admin.content_items.field_image',
        'type' => 'mediafinder',
        'useAttachment' => false,
        'mode' => 'inline',
        'commentAbove' => 'lang:restoline.content::default.admin.content_items.comment_image',
    ],
];

$config['form']['tabs']['fields'] = [
    'summary' => [
        'label' => 'lang:restoline.content::default.admin.content_items.field_summary',
        'type' => 'textarea',
        'tab' => 'lang:restoline.content::default.admin.content_items.tab_text',
        'size' => 'small',
    ],
    'content' => [
        'label' => 'lang:restoline.content::default.admin.content_items.field_content',
        'type' => 'richeditor',
        'tab' => 'lang:restoline.content::default.admin.content_items.tab_text',
        'cssClass' => 'richeditor-fluid',
        'commentAbove' => 'lang:restoline.content::default.admin.content_items.comment_content',
    ],
];

return $config;
