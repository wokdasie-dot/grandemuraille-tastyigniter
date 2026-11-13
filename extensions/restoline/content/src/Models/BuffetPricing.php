<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;

class BuffetPricing extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_buffet_pricings';

    protected $primaryKey = 'pricing_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'adult_price' => 'decimal:2',
        'child_4_8_price' => 'decimal:2',
        'child_9_13_price' => 'decimal:2',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public $rules = [
        ['label', 'restoline.content::default.admin.buffet_pricing.field_label', 'required|string|min:2|max:255'],
        ['category', 'restoline.content::default.admin.buffet_pricing.field_category', 'required|string|min:2|max:64|unique:restoline_buffet_pricings'],
        ['adult_price', 'restoline.content::default.admin.buffet_pricing.field_adult', 'required|numeric|min:0'],
        ['child_4_8_price', 'restoline.content::default.admin.buffet_pricing.field_child_4_8', 'required|numeric|min:0'],
        ['child_9_13_price', 'restoline.content::default.admin.buffet_pricing.field_child_9_13', 'required|numeric|min:0'],
        ['sort_order', 'restoline.content::default.admin.common.order', 'integer'],
        ['status', 'restoline.content::default.admin.common.active', 'boolean'],
    ];

    public function getCategoryOptions(): array
    {
        return [
            'weekday_lunch' => lang('restoline.content::default.admin.buffet_pricing.category_weekday_lunch'),
            'weekday_dinner' => lang('restoline.content::default.admin.buffet_pricing.category_weekday_dinner'),
            'weekend_dinner' => lang('restoline.content::default.admin.buffet_pricing.category_weekend_dinner'),
            'sunday_holiday' => lang('restoline.content::default.admin.buffet_pricing.category_sunday_holiday'),
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->getCategoryOptions()[$this->category] ?? (string)$this->category;
    }
}
