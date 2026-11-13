<select
    wire:model="time"
    name="time"
    id="time"
    @class(['form-select', 'is-invalid' => has_form_error('time')])
>
    <option value="0">@lang('igniter.orange::default.text_select_time')</option>
    @php($selectedDateIsToday = make_carbon($this->date)->isToday())
    @php($bookableTimeslots = $this->timeslots->reject(fn($dateTime) => \Restoline\Content\Models\DailyCapacityOverride::isFullyBookedForDateTime($dateTime)))
    @foreach ($bookableTimeslots as $dateTime)
        <option
            value="{{ $dateTime->format('H:i') }}" @selected($selectedDateIsToday && $loop->first)
        >{{ $dateTime->isoFormat(lang('system::lang.moment.time_format')) }}</option>
    @endforeach
</select>
