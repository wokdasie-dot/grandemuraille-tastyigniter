<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/daily_capacity_overrides/create',
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
            'href' => 'restoline/content/daily_capacity_overrides/edit/{override_id}',
        ],
    ],
    'date' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.column_date',
        'type' => 'date',
        'searchable' => true,
    ],
    'lunch_closed' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.column_lunch',
        'type' => 'switch',
    ],
    'dinner_closed' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.column_dinner',
        'type' => 'switch',
    ],
    'reason' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.column_reason',
        'type' => 'text',
    ],
    'created_at' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.column_created',
        'type' => 'datetime',
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
    'date' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.field_date',
        'type' => 'datepicker',
        'mode' => 'date',
        'span' => 'left',
    ],
    'lunch_closed' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.field_lunch',
        'type' => 'switch',
        'span' => 'left',
        'default' => false,
    ],
    'dinner_closed' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.field_dinner',
        'type' => 'switch',
        'span' => 'right',
        'default' => false,
    ],
    'reason' => [
        'label' => 'lang:restoline.content::default.admin.daily_capacity.field_reason',
        'type' => 'text',
        'span' => 'full',
    ],
];

return $config;
