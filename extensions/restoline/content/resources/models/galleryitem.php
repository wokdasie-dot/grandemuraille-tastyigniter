<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:restoline.content::default.admin.gallery.create_title',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/gallery_items/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'lang:igniter::admin.button_delete',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/gallery_items/edit/{gallery_id}',
        ],
    ],
    'title' => [
        'label' => 'lang:restoline.content::default.admin.gallery.column_title',
        'type' => 'text',
        'searchable' => true,
    ],
    'caption' => [
        'label' => 'lang:restoline.content::default.admin.gallery.column_caption',
        'type' => 'text',
        'searchable' => true,
    ],
    'sort_order' => [
        'label' => 'lang:restoline.content::default.admin.common.order',
        'type' => 'number',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.gallery.column_active',
        'type' => 'switch',
    ],
];

$config['form']['toolbar'] = [
    'buttons' => [
        'save' => [
            'label' => 'lang:igniter::admin.button_save',
            'context' => ['create', 'edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'igniter::admin.text_saving',
        ],
        'delete' => [
            'label' => 'lang:igniter::admin.button_icon_delete',
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-data' => "_method:'DELETE'",
            'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
            'data-progress-indicator' => 'igniter::admin.text_deleting',
            'context' => ['edit'],
        ],
    ],
];

$config['form']['fields'] = [
    'title' => [
        'label' => 'lang:restoline.content::default.admin.gallery.field_title',
        'type' => 'text',
        'span' => 'left',
        'comment' => 'lang:restoline.content::default.admin.gallery.comment_title',
    ],
    'sort_order' => [
        'label' => 'lang:restoline.content::default.admin.gallery.field_order',
        'type' => 'number',
        'span' => 'right',
        'default' => 10,
    ],
    'image_path' => [
        'label' => 'lang:restoline.content::default.admin.gallery.field_image',
        'type' => 'mediafinder',
        'useAttachment' => false,
        'span' => 'full',
        'commentAbove' => 'lang:restoline.content::default.admin.gallery.comment_image',
    ],
    'caption' => [
        'label' => 'lang:restoline.content::default.admin.gallery.field_caption',
        'type' => 'text',
        'span' => 'full',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.gallery.field_status',
        'type' => 'switch',
        'span' => 'left',
        'default' => true,
    ],
];

return $config;
