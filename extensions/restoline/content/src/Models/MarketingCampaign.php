<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;

class MarketingCampaign extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_marketing_campaigns';

    protected $primaryKey = 'campaign_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
    ];

    public $rules = [
        ['title', 'Titre', 'required|string|max:255'],
        ['type', 'Type', 'required|in:manual,birthday'],
        ['channel', 'Canal', 'required|in:email,sms,both'],
        ['email_subject', 'Sujet email', 'nullable|string|max:255'],
        ['email_body', 'Texte email', 'nullable|string'],
        ['sms_body', 'Texte SMS', 'nullable|string|max:500'],
        ['birthday_days_before', 'Jours avant anniversaire', 'required|integer|min:0|max:30'],
        ['valid_days', 'Durée validité', 'required|integer|min:1|max:60'],
        ['status', 'Actif', 'required|boolean'],
    ];

    public function getTypeOptions(): array
    {
        return [
            'manual' => 'Promotion manuelle',
            'birthday' => 'Anniversaire automatique',
        ];
    }

    public function getChannelOptions(): array
    {
        return [
            'email' => 'Email seulement',
            'sms' => 'SMS seulement',
            'both' => 'Canal choisi par le client',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->getTypeOptions()[$this->type] ?? $this->type;
    }

    public function getChannelLabelAttribute(): string
    {
        return $this->getChannelOptions()[$this->channel] ?? $this->channel;
    }
}
