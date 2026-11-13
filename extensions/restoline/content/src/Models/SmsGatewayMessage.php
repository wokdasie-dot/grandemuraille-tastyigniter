<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class SmsGatewayMessage extends Model
{
    protected $table = 'restoline_sms_gateway_messages';

    protected $primaryKey = 'sms_gateway_message_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'accepted_at' => 'datetime',
        'last_status_at' => 'datetime',
        'fallback_due_at' => 'datetime',
        'fallback_sent_at' => 'datetime',
        'last_payload' => 'array',
    ];

    public function isFinalStatus(): bool
    {
        return in_array(strtolower((string)$this->status), [
            'delivered',
            'sent',
            'processed',
            'failed',
            'cancelled',
            'canceled',
        ], true);
    }
}
