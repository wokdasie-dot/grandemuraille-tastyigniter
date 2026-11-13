<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;

class ReservationClientNotificationSetting extends Model
{
    use Validation;

    public const string MODE_EMAIL_THEN_SMS_FALLBACK = 'email_then_sms_fallback';
    public const string MODE_EMAIL_AND_SMS = 'email_and_sms';

    protected $table = 'restoline_reservation_client_notification_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    public $rules = [
        ['confirmation_mode', 'Mode de confirmation', 'required|in:email_then_sms_fallback,email_and_sms'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create([
            'confirmation_mode' => self::MODE_EMAIL_THEN_SMS_FALLBACK,
        ]);
    }

    public function getConfirmationModeOptions(): array
    {
        return [
            self::MODE_EMAIL_THEN_SMS_FALLBACK => 'Actuel : email, puis SMS si échec',
            self::MODE_EMAIL_AND_SMS => 'Email et SMS',
        ];
    }

    public function sendsSmsAfterSuccessfulEmail(): bool
    {
        return $this->confirmation_mode === self::MODE_EMAIL_AND_SMS;
    }
}
