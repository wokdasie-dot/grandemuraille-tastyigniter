<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_newsletter_subscribers';

    protected $primaryKey = 'subscriber_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'email_consent' => 'boolean',
        'sms_consent' => 'boolean',
        'status' => 'boolean',
        'consented_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'birthday_offer_used_at' => 'datetime',
    ];

    public $rules = [
        ['email', 'Email', 'nullable|email|max:255'],
        ['phone', 'Téléphone', 'nullable|string|max:40'],
        ['first_name', 'Prénom', 'nullable|string|max:255'],
        ['birthday_day', 'Jour anniversaire', 'nullable|integer|min:1|max:31'],
        ['birthday_month', 'Mois anniversaire', 'nullable|integer|min:1|max:12'],
        ['birthday_offer_note', 'Note offre anniversaire', 'nullable|string|max:500'],
        ['email_consent', 'Consentement email', 'required|boolean'],
        ['sms_consent', 'Consentement SMS', 'required|boolean'],
        ['status', 'Actif', 'required|boolean'],
    ];

    protected static function booted(): void
    {
        static::creating(function(NewsletterSubscriber $subscriber): void {
            if (!$subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = hash('sha256', Str::random(64).microtime(true));
            }
        });
    }

    public function getBirthdayLabelAttribute(): string
    {
        if (!$this->birthday_day || !$this->birthday_month) {
            return '';
        }

        return sprintf('%02d/%02d', (int)$this->birthday_day, (int)$this->birthday_month);
    }

    public function getChannelsLabelAttribute(): string
    {
        if ($this->email_consent) {
            return 'Email';
        }

        if ($this->sms_consent) {
            return 'SMS';
        }

        return 'Aucun';
    }

    public function getBirthdayOfferUsedLabelAttribute(): string
    {
        return $this->birthday_offer_used_at
            ? $this->birthday_offer_used_at->format('d/m/Y')
            : 'Non';
    }

    public function scopeActive($query)
    {
        return $query->where('status', true)->whereNull('unsubscribed_at');
    }
}
