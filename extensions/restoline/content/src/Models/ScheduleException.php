<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Local\Models\Location;
use Igniter\System\Models\Concerns\Switchable;

class ScheduleException extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_schedule_exceptions';

    protected $primaryKey = 'exception_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public $rules = [
        ['location_id', 'restoline.content::default.admin.schedule_exceptions.field_location', 'required|integer'],
        ['name', 'restoline.content::default.admin.schedule_exceptions.field_name', 'required|string|min:2|max:255'],
        ['kind', 'restoline.content::default.admin.schedule_exceptions.field_kind', 'required|in:open,closed'],
        ['start_date', 'restoline.content::default.admin.schedule_exceptions.field_start', 'required|date'],
        ['end_date', 'restoline.content::default.admin.schedule_exceptions.field_end', 'required|date|after_or_equal:start_date'],
        ['opening_start', 'restoline.content::default.admin.schedule_exceptions.field_opening_start', 'nullable|required_if:kind,open|date_format:H:i'],
        ['opening_end', 'restoline.content::default.admin.schedule_exceptions.field_opening_end', 'nullable|required_if:kind,open|date_format:H:i|after:opening_start'],
        ['evening_start', 'restoline.content::default.admin.schedule_exceptions.field_evening_start', 'nullable|date_format:H:i|required_with:evening_end'],
        ['evening_end', 'restoline.content::default.admin.schedule_exceptions.field_evening_end', 'nullable|date_format:H:i|required_with:evening_start|after:evening_start'],
        ['status', 'restoline.content::default.admin.common.active', 'required|boolean'],
    ];

    public function beforeValidate(): void
    {
        if (!$this->location_id) {
            $this->location_id = Location::getDefault()?->getKey() ?? Location::query()->value('location_id');
        }

        if ($this->kind === 'closed') {
            $this->opening_start = null;
            $this->opening_end = null;
            $this->evening_start = null;
            $this->evening_end = null;
        }
    }

    public function getKindOptions(): array
    {
        return [
            'open' => lang('restoline.content::default.admin.schedule_exceptions.kind_open_long'),
            'closed' => lang('restoline.content::default.admin.schedule_exceptions.kind_closed_long'),
        ];
    }

    public function getLocationIdOptions(): array
    {
        return Location::query()->dropdown('location_name')->all();
    }

    public function getKindLabelAttribute(): string
    {
        return lang($this->kind === 'open'
            ? 'restoline.content::default.admin.schedule_exceptions.kind_open'
            : 'restoline.content::default.admin.schedule_exceptions.kind_closed');
    }

    public function getDateRangeLabelAttribute(): string
    {
        $start = $this->start_date?->format('d/m/Y');
        $end = $this->end_date?->format('d/m/Y');

        return $start === $end
            ? (string)$start
            : sprintf(lang('restoline.content::default.admin.schedule_exceptions.date_range'), $start, $end);
    }

    public function getHoursLabelAttribute(): string
    {
        if ($this->kind === 'closed') {
            return lang('restoline.content::default.admin.schedule_exceptions.hours_closed');
        }

        return collect($this->workingPeriods())
            ->map(fn(array $period): string => implode(' - ', $period))
            ->implode(' / ');
    }

    public function workingPeriods(): array
    {
        if ($this->kind === 'closed') {
            return [];
        }

        return collect([
            [$this->opening_start, $this->opening_end],
            [$this->evening_start, $this->evening_end],
        ])->filter(fn(array $period): bool => filled($period[0]) && filled($period[1]))
            ->map(fn(array $period): array => [
                substr((string)$period[0], 0, 5),
                substr((string)$period[1], 0, 5),
            ])
            ->values()
            ->all();
    }

    public function datesBetween(CarbonInterface $minimum, CarbonInterface $maximum): array
    {
        $start = $this->start_date->copy()->max($minimum);
        $end = $this->end_date->copy()->min($maximum);

        if ($start->gt($end)) {
            return [];
        }

        return collect(CarbonPeriod::create($start, $end))
            ->map(fn(CarbonInterface $date): string => $date->format('Y-m-d'))
            ->all();
    }
}
