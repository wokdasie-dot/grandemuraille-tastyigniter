<?php

declare(strict_types=1);

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/buffet_pricings/create',
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
            'href' => 'restoline/content/buffet_pricings/edit/{pricing_id}',
        ],
    ],
    'label' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.column_label',
        'type' => 'text',
        'searchable' => true,
    ],
    'category' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.column_category',
        'type' => 'text',
        'sortable' => false,
        'valueFrom' => 'category_label',
    ],
    'adult_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.column_adult',
        'type' => 'number',
    ],
    'child_4_8_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.column_child_4_8',
        'type' => 'number',
    ],
    'child_9_13_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.column_child_9_13',
        'type' => 'number',
    ],
    'sort_order' => [
        'label' => 'lang:restoline.content::default.admin.common.order',
        'type' => 'number',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.active',
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
    'label' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_label',
        'type' => 'text',
        'span' => 'left',
    ],
    'category' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_category',
        'type' => 'select',
        'span' => 'right',
        'options' => 'getCategoryOptions',
    ],
    'adult_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_adult',
        'type' => 'number',
        'span' => 'left',
        'step' => 0.01,
    ],
    'child_4_8_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_child_4_8',
        'type' => 'number',
        'span' => 'right',
        'step' => 0.01,
    ],
    'child_9_13_price' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_child_9_13',
        'type' => 'number',
        'span' => 'left',
        'step' => 0.01,
    ],
    'sort_order' => [
        'label' => 'lang:restoline.content::default.admin.buffet_pricing.field_order',
        'type' => 'number',
        'span' => 'right',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.active',
        'type' => 'switch',
        'span' => 'full',
        'default' => true,
    ],
];

return $config;
