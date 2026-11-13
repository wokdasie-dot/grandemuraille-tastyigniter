<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class TableOrderItem extends Model
{
    protected $table = 'restoline_table_order_items';

    protected $primaryKey = 'item_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => [TableOrder::class, 'foreignKey' => 'order_id'],
        ],
    ];
}
