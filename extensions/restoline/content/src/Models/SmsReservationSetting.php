<?php
declare(strict_types=1);
namespace Restoline\Content\Models;
use Igniter\Flame\Database\Model;
class SmsReservationSetting extends Model
{
    protected $table = 'restoline_sms_reservation_settings';
    protected $primaryKey = 'setting_id';
    protected $guarded = [];
    public $timestamps = true;
    protected $casts = ['enabled' => 'boolean'];
    public static function current(): self { return static::query()->first() ?: static::create(['enabled' => true]); }
}
