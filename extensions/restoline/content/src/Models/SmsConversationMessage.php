<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class SmsConversationMessage extends Model
{
    protected $table = 'restoline_sms_conversation_messages';

    protected $primaryKey = 'message_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'ai_payload' => 'array',
        'raw_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
