<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class RestaurantNotificationSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_restaurant_notification_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'notify_new_reservation' => 'boolean',
        'notify_modified_reservation' => 'boolean',
        'notify_cancelled_reservation' => 'boolean',
        'status' => 'boolean',
    ];

    public $rules = [
        ['name', 'Nom', 'required|string|max:255'],
        ['channel', 'Canal', 'required|in:whatsapp,wechat,both'],
        ['provider', 'Fournisseur', 'required|in:twilio_whatsapp,meta_whatsapp,wecom_workflow,wechat_official,custom'],
        ['recipient', 'Destinataire', 'nullable|string|max:255'],
        ['sender', 'Expediteur', 'nullable|string|max:255'],
        ['api_base_url', 'URL API', 'nullable|url|max:255'],
        ['account_id', 'Identifiant compte', 'nullable|string|max:255'],
        ['api_secret', 'Secret API', 'nullable|string|max:5000'],
        ['access_token', 'Token acces', 'nullable|string|max:5000'],
        ['message_template', 'Modele message', 'nullable|string|max:2000'],
        ['notify_new_reservation', 'Nouvelle reservation', 'required|boolean'],
        ['notify_modified_reservation', 'Reservation modifiee', 'required|boolean'],
        ['notify_cancelled_reservation', 'Reservation annulee', 'required|boolean'],
        ['status', 'Actif', 'required|boolean'],
    ];

    public function getChannelOptions(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'wechat' => 'WeChat',
            'both' => 'WhatsApp et WeChat',
        ];
    }

    public function getProviderOptions(): array
    {
        return [
            'twilio_whatsapp' => 'Twilio WhatsApp',
            'meta_whatsapp' => 'WhatsApp Cloud API (Meta)',
            'wecom_workflow' => 'WeCom robot de groupe',
            'wechat_official' => 'WeChat Official Account',
            'custom' => 'API personnalisée',
        ];
    }

    public function getChannelLabelAttribute(): string
    {
        return $this->getChannelOptions()[$this->channel] ?? (string)$this->channel;
    }

    public function getProviderLabelAttribute(): string
    {
        return $this->getProviderOptions()[$this->provider] ?? (string)$this->provider;
    }

    public function getSecretStatusAttribute(): string
    {
        return ($this->api_secret || $this->access_token) ? 'Configuré' : 'Non configuré';
    }

    public function setApiSecretAttribute($value): void
    {
        $this->setEncryptedAttribute('api_secret', $value);
    }

    public function getApiSecretAttribute($value): ?string
    {
        return $this->getEncryptedAttribute($value);
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->setEncryptedAttribute('access_token', $value);
    }

    public function getAccessTokenAttribute($value): ?string
    {
        return $this->getEncryptedAttribute($value);
    }

    private function setEncryptedAttribute(string $key, $value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            if (!$this->exists) {
                $this->attributes[$key] = null;
            }

            return;
        }

        $this->attributes[$key] = Crypt::encryptString((string)$value);
    }

    private function getEncryptedAttribute($value): ?string
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
