<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class GiftCardTransaction extends Model
{
    protected $table = 'restoline_gift_card_transactions';

    protected $primaryKey = 'gift_card_transaction_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public $relation = [
        'belongsTo' => [
            'gift_card' => GiftCard::class,
        ],
    ];
}
