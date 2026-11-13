<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;

class GroupBenefitSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_group_benefit_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
        'send_for_dinner' => 'boolean',
        'send_for_sunday_lunch' => 'boolean',
        'lunch_status' => 'boolean',
    ];

    public $rules = [
        ['status', 'Actif', 'required|boolean'],
        ['min_guests', 'Nombre minimum', 'required|integer|min:1|max:30'],
        ['send_for_dinner', 'Tous les soirs', 'required|boolean'],
        ['send_for_sunday_lunch', 'Dimanche midi', 'required|boolean'],
        ['sms_message', 'Message SMS', 'required|string|max:500'],
        ['lunch_status', 'Actif midi', 'required|boolean'],
        ['lunch_min_guests', 'Nombre minimum midi', 'required|integer|min:1|max:30'],
        ['lunch_sms_message', 'Message SMS midi', 'required|string|max:500'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create([
            'status' => true,
            'min_guests' => 6,
            'send_for_dinner' => true,
            'send_for_sunday_lunch' => true,
            'sms_message' => "Info La Grande Muraille de Chine: si l’addition est reglee en especes, la maison offre un apero maison ou une coupe de mousseux + un cafe, thé ou pousse-cafe. Merci de faire passer le message à votre table",
            'lunch_status' => true,
            'lunch_min_guests' => 8,
            'lunch_sms_message' => "Info: si l’addition est reglee en especes, la maison offre un apero maison ou une coupe de mousseux. Merci de faire passer le message à votre table",
        ]);
    }
}
