<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\DB;

class TableOrderingSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_table_ordering_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
        'qr_category_ids' => 'array',
    ];

    public $rules = [
        ['status', 'Actif', 'required|boolean'],
        ['monday_hours', 'Lundi', 'nullable|string|max:100'],
        ['tuesday_hours', 'Mardi', 'nullable|string|max:100'],
        ['wednesday_hours', 'Mercredi', 'nullable|string|max:100'],
        ['thursday_hours', 'Jeudi', 'nullable|string|max:100'],
        ['friday_hours', 'Vendredi', 'nullable|string|max:100'],
        ['saturday_hours', 'Samedi', 'nullable|string|max:100'],
        ['sunday_hours', 'Dimanche', 'nullable|string|max:100'],
        ['qr_category_ids', 'Catégories QR visibles', 'nullable'],
        ['closed_message', 'Message fermeture', 'nullable|string|max:500'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create([
            'status' => true,
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'monday_hours' => '',
            'tuesday_hours' => '18:00-22:00',
            'wednesday_hours' => '18:00-22:00',
            'thursday_hours' => '18:00-22:00',
            'friday_hours' => '18:00-23:00',
            'saturday_hours' => '18:00-23:00',
            'sunday_hours' => '12:00-15:00, 18:00-22:00',
            'qr_category_ids' => static::defaultQrCategoryIds(),
            'closed_message' => 'La commande à table est fermée pour le moment.',
        ]);
    }

    public function getQrCategoryIdsOptions(): array
    {
        return DB::table('categories')
            ->where('status', 1)
            ->orderBy('priority')
            ->orderBy('name')
            ->pluck('name', 'category_id')
            ->mapWithKeys(fn($name, $id) => [(string)$id => (string)$name])
            ->all();
    }

    public function qrCategoryIds(): array
    {
        $ids = $this->qr_category_ids;
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : preg_split('/[,;\s]+/', $ids);
        }

        $ids = collect(is_array($ids) ? $ids : [])
            ->map(fn($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $ids ?: static::defaultQrCategoryIds();
    }

    public static function defaultQrCategoryNames(): array
    {
        return [
            'Potage',
            'Friture',
            'Suggestions du moment',
            'California / Maki - 6 pieces',
            'Sushi minute',
            'Temaki minute',
            'Poké bowl minute',
        ];
    }

    public static function defaultQrCategoryIds(): array
    {
        return DB::table('categories')
            ->whereIn('name', static::defaultQrCategoryNames())
            ->orderBy('priority')
            ->pluck('category_id')
            ->map(fn($id) => (int)$id)
            ->all();
    }

    public function isOpenNow(): bool
    {
        if (!$this->status) {
            return false;
        }

        $todayHours = $this->hoursForToday();
        if ($todayHours !== '') {
            return $this->timeIsInRanges(now()->format('H:i:s'), $todayHours);
        }

        if (!$this->hasWeeklyHours()) {
            $now = now()->format('H:i:s');
            $start = $this->normalizeTime((string)$this->start_time);
            $end = $this->normalizeTime((string)$this->end_time);

            if ($start <= $end) {
                return $now >= $start && $now <= $end;
            }

            return $now >= $start || $now <= $end;
        }

        return false;
    }

    public function scheduleLabel(): string
    {
        $labels = [];
        foreach ($this->weeklyHourFields() as $field => $label) {
            $hours = trim((string)$this->{$field});
            if ($hours !== '') {
                $labels[] = $label.' '.$this->formatHoursLabel($hours);
            }
        }

        if ($labels !== []) {
            return implode(' · ', $labels);
        }

        return sprintf('%s à %s', substr($this->normalizeTime((string)$this->start_time), 0, 5), substr($this->normalizeTime((string)$this->end_time), 0, 5));
    }

    private function hoursForToday(): string
    {
        $fields = array_keys($this->weeklyHourFields());
        $field = $fields[(int)now()->isoWeekday() - 1] ?? null;

        return $field ? trim((string)$this->{$field}) : '';
    }

    private function hasWeeklyHours(): bool
    {
        foreach (array_keys($this->weeklyHourFields()) as $field) {
            if (trim((string)$this->{$field}) !== '') {
                return true;
            }
        }

        return false;
    }

    private function weeklyHourFields(): array
    {
        return [
            'monday_hours' => 'Lundi',
            'tuesday_hours' => 'Mardi',
            'wednesday_hours' => 'Mercredi',
            'thursday_hours' => 'Jeudi',
            'friday_hours' => 'Vendredi',
            'saturday_hours' => 'Samedi',
            'sunday_hours' => 'Dimanche',
        ];
    }

    private function timeIsInRanges(string $now, string $ranges): bool
    {
        foreach (preg_split('/[,;]+/', $ranges) ?: [] as $range) {
            if (!preg_match('/(\d{1,2}:\d{2})\s*[-àa]\s*(\d{1,2}:\d{2})/u', trim($range), $matches)) {
                continue;
            }

            $start = $this->normalizeTime($matches[1]);
            $end = $this->normalizeTime($matches[2]);

            if ($start <= $end && $now >= $start && $now <= $end) {
                return true;
            }

            if ($start > $end && ($now >= $start || $now <= $end)) {
                return true;
            }
        }

        return false;
    }

    private function formatHoursLabel(string $hours): string
    {
        return str_replace('-', ' à ', preg_replace('/\s+/', ' ', trim($hours)) ?: $hours);
    }

    private function normalizeTime(string $value): string
    {
        if (preg_match('/^\d{1}:\d{2}$/', $value)) {
            return '0'.$value.':00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value.':00';
        }

        return $value ?: '00:00:00';
    }
}
