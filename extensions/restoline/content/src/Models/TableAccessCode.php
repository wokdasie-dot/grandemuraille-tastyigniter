<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Carbon\CarbonInterface;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Reservation\Models\DiningTable;
use Igniter\System\Models\Concerns\Switchable;

class TableAccessCode extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_table_access_codes';

    protected $primaryKey = 'code_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'valid_date' => 'date',
        'status' => 'boolean',
        'attempts' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public $rules = [
        ['dining_table_id', 'Table', 'required|integer'],
        ['table_name', 'Nom de table', 'required|string|max:32'],
        ['valid_date', 'Date', 'required|date'],
        ['code', 'Code', 'required|digits:3'],
        ['status', 'Actif', 'required|boolean'],
    ];

    public $relation = [
        'belongsTo' => [
            'dining_table' => [DiningTable::class, 'foreignKey' => 'dining_table_id'],
        ],
    ];

    public function getDateLabelAttribute(): string
    {
        return $this->valid_date?->format('d/m/Y') ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'Actif' : 'Désactivé';
    }

    public function getQrUrlAttribute(): string
    {
        return url('/commander');
    }

    public function markUsed(CarbonInterface $at): void
    {
        $this->attempts = ((int)$this->attempts) + 1;
        $this->last_used_at = $at;
        $this->save();
    }
}
