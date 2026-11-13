<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class ReservationServiceAction extends Model
{
    protected $table = 'restoline_service_reservation_actions';

    protected $guarded = [];
}
