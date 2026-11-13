<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class EmailMarketingSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_email_marketing_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
    ];

    public $rules = [
        ['name', 'Nom', 'required|string|max:255'],
        ['provider', 'Service emailing', 'required|in:brevo,mailjet,mailchimp,custom'],
        ['api_base_url', 'URL API', 'nullable|url|max:255'],
        ['api_key', 'Cle API', 'nullable|string|max:5000'],
        ['sender_name', 'Nom expediteur', 'nullable|string|max:255'],
        ['sender_email', 'Email expediteur', 'nullable|email|max:255'],
        ['reply_to_email', 'Email de reponse', 'nullable|email|max:255'],
        ['list_id', 'Liste / audience', 'nullable|string|max:255'],
        ['status', 'Actif', 'required|boolean'],
    ];

    public function getProviderOptions(): array
    {
        return [
            'brevo' => 'Brevo',
            'mailjet' => 'Mailjet',
            'mailchimp' => 'Mailchimp',
            'custom' => 'API personnalisee',
        ];
    }

    public function getProviderLabelAttribute(): string
    {
        return $this->getProviderOptions()[$this->provider] ?? (string)$this->provider;
    }

    public function getApiKeyStatusAttribute(): string
    {
        return $this->api_key ? 'Configuree' : 'Non configuree';
    }

    public function setApiKeyAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            if (!$this->exists) {
                $this->attributes['api_key'] = null;
            }

            return;
        }

        $this->attributes['api_key'] = Crypt::encryptString((string)$value);
    }

    public function getApiKeyAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (RuntimeException) {
            return $value;
        }
    }
}
