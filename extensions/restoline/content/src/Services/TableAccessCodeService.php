<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use Carbon\CarbonInterface;
use Igniter\Reservation\Models\DiningTable;
use Illuminate\Support\Collection;
use Restoline\Content\Models\TableAccessCode;

class TableAccessCodeService
{
    public function generateForDate(?CarbonInterface $date = null, bool $regenerateExisting = false): int
    {
        $date ??= now();
        $createdOrUpdated = 0;

        $this->activeQrTables()->each(function(DiningTable $table) use ($date, $regenerateExisting, &$createdOrUpdated): void {
            $existing = TableAccessCode::query()
                ->where('dining_table_id', $table->getKey())
                ->whereDate('valid_date', $date->toDateString())
                ->first();

            if ($existing && !$regenerateExisting) {
                return;
            }

            $code = $this->uniqueCodeForDate($date, $existing?->getKey());

            $record = $existing ?: new TableAccessCode();
            $record->fill([
                'dining_table_id' => $table->getKey(),
                'table_name' => (string)$table->name,
                'valid_date' => $date->toDateString(),
                'code' => $code,
                'status' => true,
            ]);
            $record->attempts = 0;
            $record->last_used_at = null;
            $record->save();
            $createdOrUpdated++;
        });

        return $createdOrUpdated;
    }

    public function regenerate(TableAccessCode $record): TableAccessCode
    {
        $record->code = $this->uniqueCodeForDate($record->valid_date, $record->getKey());
        $record->status = true;
        $record->attempts = 0;
        $record->last_used_at = null;
        $record->save();

        return $record;
    }

    public function validateAccess(string $tableName, string $code): ?TableAccessCode
    {
        $normalizedTable = strtoupper(trim($tableName));
        $normalizedCode = preg_replace('/\D+/', '', $code) ?: '';

        if ($normalizedTable === '' || !preg_match('/^\d{3}$/', $normalizedCode)) {
            return null;
        }

        $record = TableAccessCode::query()
            ->whereDate('valid_date', now()->toDateString())
            ->where('status', true)
            ->whereRaw('UPPER(table_name) = ?', [$normalizedTable])
            ->first();

        if (!$record) {
            return null;
        }

        if (!hash_equals((string)$record->code, $normalizedCode)) {
            $record->attempts = ((int)$record->attempts) + 1;
            $record->save();
            return null;
        }

        $record->markUsed(now());

        return $record;
    }

    public function activeQrTables(): Collection
    {
        return DiningTable::query()
            ->where('dining_area_id', 1)
            ->where('is_enabled', true)
            ->where('priority', '>', 0)
            ->where('is_combo', false)
            ->orderByDesc('priority')
            ->get();
    }

    protected function uniqueCodeForDate(CarbonInterface $date, $ignoreId = null): string
    {
        do {
            $code = (string)random_int(100, 999);
            $query = TableAccessCode::query()
                ->whereDate('valid_date', $date->toDateString())
                ->where('code', $code);

            if ($ignoreId) {
                $query->whereKeyNot($ignoreId);
            }
        } while ($query->exists());

        return $code;
    }
}
