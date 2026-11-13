<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;

class TableOrder extends Model
{
    use Validation;

    protected $table = 'restoline_table_orders';

    protected $primaryKey = 'order_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'valid_date' => 'date',
        'submitted_at' => 'datetime',
        'printed_at' => 'datetime',
        'subtotal' => 'float',
    ];

    public $rules = [
        ['reference', 'Référence', 'required|string|max:32'],
        ['table_name', 'Table', 'required|string|max:32'],
        ['status', 'Statut', 'required|string|max:32'],
        ['print_status', 'Impression', 'required|string|max:32'],
    ];

    public $relation = [
        'hasMany' => [
            'items' => [TableOrderItem::class, 'foreignKey' => 'order_id'],
        ],
        'belongsTo' => [
            'access_code' => [TableAccessCode::class, 'foreignKey' => 'access_code_id'],
        ],
    ];

    public function getSubmittedLabelAttribute(): string
    {
        return $this->submitted_at?->format('d/m/Y H:i') ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((string)$this->status) {
            'new' => 'Nouvelle',
            'preparing' => 'En préparation',
            'done' => 'Terminée',
            'cancelled' => 'Annulée',
            default => (string)$this->status,
        };
    }

    public function getPrintStatusLabelAttribute(): string
    {
        return match ((string)$this->print_status) {
            'pending' => 'À imprimer',
            'printing' => 'Envoyée à l’imprimante',
            'printed' => 'Imprimée',
            'failed' => 'Erreur impression',
            default => (string)$this->print_status,
        };
    }

    public function getPrintedLabelAttribute(): string
    {
        return $this->printed_at?->format('d/m/Y H:i') ?? '';
    }

    public function getUpdatedLabelAttribute(): string
    {
        return $this->updated_at?->format('d/m/Y H:i') ?? '';
    }

    public function getItemsSummaryAttribute(): string
    {
        return $this->items->map(fn($item) => $item->quantity.'x '.$item->menu_name)->implode(' | ');
    }
}
