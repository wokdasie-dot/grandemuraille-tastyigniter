<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class TakeawayOrder extends Model
{
    protected $table = 'restoline_service_takeaway_orders';

    protected $primaryKey = 'takeaway_id';

    protected $guarded = [];

    protected $casts = [
        'pickup_at' => 'datetime',
        'estimated_ready_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ready_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'customer_estimate_notified_at' => 'datetime',
        'customer_ready_notified_at' => 'datetime',
    ];
}
