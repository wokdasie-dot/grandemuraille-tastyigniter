<?php

declare(strict_types=1);

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'lang:restoline.content::default.admin.schedule_exceptions.search_prompt',
        'mode' => 'all',
    ],
    'scopes' => [
        'kind' => [
            'label' => 'lang:restoline.content::default.admin.common.type',
            'type' => 'select',
            'conditions' => 'kind = :filtered',
            'options' => [
                'open' => 'lang:restoline.content::default.admin.schedule_exceptions.kind_open',
                'closed' => 'lang:restoline.content::default.admin.schedule_exceptions.kind_closed',
            ],
        ],
        'status' => [
            'label' => 'lang:restoline.content::default.admin.common.active',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:restoline.content::default.admin.schedule_exceptions.button_create',
            'class' => 'btn btn-primary',
            'href' => 'restoline/content/schedule_exceptions/create',
        ],
        'weekly-hours' => [
            'label' => 'lang:restoline.content::default.admin.schedule_exceptions.button_weekly',
            'class' => 'btn btn-default',
            'href' => 'locations',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'lang:restoline.content::default.admin.common.delete',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'lang:restoline.content::default.admin.schedule_exceptions.confirm_delete_all',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'restoline/content/schedule_exceptions/edit/{exception_id}',
        ],
    ],
    'name' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.column_name',
        'type' => 'text',
        'searchable' => true,
    ],
    'kind_label' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.column_kind',
        'type' => 'text',
    ],
    'date_range_label' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.column_period',
        'type' => 'text',
    ],
    'hours_label' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.column_hours',
        'type' => 'text',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.active',
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
            'data-request-confirm' => 'lang:restoline.content::default.admin.schedule_exceptions.confirm_delete',
        ],
    ],
];

$config['form']['fields'] = [
    'location_id' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_location',
        'type' => 'select',
        'options' => 'getLocationIdOptions',
        'span' => 'left',
    ],
    'status' => [
        'label' => 'lang:restoline.content::default.admin.common.active',
        'type' => 'switch',
        'default' => true,
        'span' => 'right',
    ],
    'name' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_name',
        'type' => 'text',
        'placeholder' => lang('restoline.content::default.admin.schedule_exceptions.placeholder_name'),
        'span' => 'left',
    ],
    'kind' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_kind',
        'type' => 'select',
        'options' => 'getKindOptions',
        'default' => 'closed',
        'span' => 'right',
    ],
    'start_date' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_start',
        'type' => 'datepicker',
        'mode' => 'date',
        'span' => 'left',
    ],
    'end_date' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_end',
        'type' => 'datepicker',
        'mode' => 'date',
        'span' => 'right',
        'comment' => 'lang:restoline.content::default.admin.schedule_exceptions.comment_end',
    ],
    'opening_start' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_opening_start',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'left',
        'trigger' => [
            'action' => 'show',
            'field' => 'kind',
            'condition' => 'value[open]',
        ],
    ],
    'opening_end' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_opening_end',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'right',
        'trigger' => [
            'action' => 'show',
            'field' => 'kind',
            'condition' => 'value[open]',
        ],
    ],
    'evening_start' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_evening_start',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'left',
        'trigger' => [
            'action' => 'show',
            'field' => 'kind',
            'condition' => 'value[open]',
        ],
    ],
    'evening_end' => [
        'label' => 'lang:restoline.content::default.admin.schedule_exceptions.field_evening_end',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'right',
        'trigger' => [
            'action' => 'show',
            'field' => 'kind',
            'condition' => 'value[open]',
        ],
    ],
];

return $config;
