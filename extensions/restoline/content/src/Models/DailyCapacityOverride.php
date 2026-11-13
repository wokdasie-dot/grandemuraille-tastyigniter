<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Carbon\CarbonInterface;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;

class DailyCapacityOverride extends Model
{
    use Validation;

    protected $table = 'restoline_daily_capacity_overrides';

    protected $primaryKey = 'override_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'date' => 'date',
        'lunch_closed' => 'boolean',
        'dinner_closed' => 'boolean',
    ];

    public $rules = [
        ['location_id', 'restoline.content::default.admin.schedule_exceptions.field_location', 'required|integer'],
        ['date', 'restoline.content::default.admin.daily_capacity.field_date', 'required|date'],
        ['lunch_closed', 'restoline.content::default.admin.daily_capacity.field_lunch', 'boolean'],
        ['dinner_closed', 'restoline.content::default.admin.daily_capacity.field_dinner', 'boolean'],
        ['reason', 'restoline.content::default.admin.daily_capacity.field_reason', 'nullable|string|max:255'],
    ];

    public static function isLunchClosed(CarbonInterface $date, ?int $locationId = null): bool
    {
        $locationId ??= 1;

        return static::query()
            ->where('location_id', $locationId)
            ->whereDate('date', $date->toDateString())
            ->where('lunch_closed', true)
            ->exists();
    }

    public static function isDinnerClosed(CarbonInterface $date, ?int $locationId = null): bool
    {
        $locationId ??= 1;

        return static::query()
            ->where('location_id', $locationId)
            ->whereDate('date', $date->toDateString())
            ->where('dinner_closed', true)
            ->exists();
    }

    public static function isFullyBookedForDateTime(CarbonInterface $dateTime, ?int $locationId = null): bool
    {
        $locationId ??= 1;
        $hour = (int)$dateTime->format('H');

        // Midi : 12h00 - 14h30
        // Soir : 18h00 - 22h30
        $isLunch = $hour >= 10 && $hour < 16;
        $isDinner = $hour >= 16 || $hour < 4;

        $override = static::query()
            ->where('location_id', $locationId)
            ->whereDate('date', $dateTime->toDateString())
            ->first();

        if (!$override) {
            return false;
        }

        if ($isLunch && $override->lunch_closed) {
            return true;
        }

        if ($isDinner && $override->dinner_closed) {
            return true;
        }

        return false;
    }

    public static function toggleLunchForToday(?int $locationId = null): static
    {
        $locationId ??= 1;
        $today = now()->toDateString();

        $override = static::firstOrNew([
            'location_id' => $locationId,
            'date' => $today,
        ]);

        $override->lunch_closed = !$override->lunch_closed;
        $override->reason = $override->lunch_closed ? 'Complet ce midi' : null;
        $override->save();

        return $override;
    }

    public static function toggleDinnerForToday(?int $locationId = null): static
    {
        $locationId ??= 1;
        $today = now()->toDateString();

        $override = static::firstOrNew([
            'location_id' => $locationId,
            'date' => $today,
        ]);

        $override->dinner_closed = !$override->dinner_closed;
        $override->reason = $override->dinner_closed ? 'Complet ce soir' : null;
        $override->save();

        return $override;
    }
}
