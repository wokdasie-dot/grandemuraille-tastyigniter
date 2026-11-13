<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;

class ReservationReminderSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_reservation_reminder_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'min_guests' => 'integer',
    ];

    public $rules = [
        ['enabled', 'Actif', 'required|boolean'],
        ['min_guests', 'Nombre minimum', 'required|integer|min:2|max:30'],
        ['email_enabled', 'Email actif', 'required|boolean'],
        ['sms_enabled', 'SMS actif', 'required|boolean'],
        ['send_time', "Heure d'envoi", 'required|date_format:H:i'],
        ['email_subject', "Sujet de l'email", 'required|string|max:190'],
        ['email_message', "Texte de l'email", 'required|string|max:1500'],
        ['sms_message', 'Texte du SMS', 'required|string|max:150'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create([
            'enabled' => true,
            'min_guests' => 6,
            'email_enabled' => true,
            'sms_enabled' => true,
            'send_time' => '08:00',
            'email_subject' => "La Grande Muraille de Chine - Rappel de votre réservation",
            'email_message' => 'Petit rappel pour votre réservation de demain. Vous pouvez vérifier les informations, modifier le nombre de personnes ou annuler via le bouton ci-dessous.',
            'sms_message' => "La Grande Muraille de Chine: rappel demain a {time} pour {guests} pers. Modifier ou annuler: {url}",
        ]);
    }
}
