<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_gift_cards';

    protected $primaryKey = 'gift_card_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'expires_at' => 'date',
        'emailed_at' => 'datetime',
        'status' => 'boolean',
    ];

    public $rules = [
        ['initial_amount', 'Montant', 'required|numeric|min:1|max:10000'],
        ['recipient_name', 'Bénéficiaire', 'nullable|string|max:255'],
        ['recipient_email', 'Email du bénéficiaire', 'nullable|email|max:255'],
        ['purchaser_name', 'Acheteur', 'nullable|string|max:255'],
        ['expires_at', 'Date d’expiration', 'nullable|date'],
        ['status', 'Actif', 'required|boolean'],
    ];

    public $relation = [
        'hasMany' => [
            'transactions' => GiftCardTransaction::class,
        ],
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function(self $giftCard): void {
            $giftCard->code = $giftCard->code ?: static::makeCode();
            $giftCard->qr_token = $giftCard->qr_token ?: Str::random(64);
            $giftCard->balance_amount = $giftCard->initial_amount;
        });
    }

    public static function makeCode(): string
    {
        do {
            $code = 'WOK-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format((float)$this->balance_amount, 2, ',', ' ').' EUR';
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->status) {
            return 'Désactivé';
        }

        if ($this->expires_at && $this->expires_at->endOfDay()->isPast()) {
            return 'Expiré';
        }

        return (float)$this->balance_amount <= 0 ? 'Utilisé' : 'Actif';
    }

    public function isRedeemable(): bool
    {
        return $this->status
            && (float)$this->balance_amount > 0
            && (!$this->expires_at || !$this->expires_at->endOfDay()->isPast());
    }
}
