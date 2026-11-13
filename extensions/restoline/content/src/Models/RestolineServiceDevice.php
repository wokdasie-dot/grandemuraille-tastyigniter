<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class RestolineServiceDevice extends Model
{
    protected $table = 'restoline_service_devices';

    protected $primaryKey = 'device_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
