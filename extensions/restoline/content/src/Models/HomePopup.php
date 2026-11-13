<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Carbon\CarbonInterface;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\System\Models\Concerns\Switchable;

class HomePopup extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_home_popups';

    protected $primaryKey = 'popup_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'dismiss_hours' => 'integer',
        'priority' => 'integer',
    ];

    public $rules = [
        ['title', 'Titre', 'required|string|min:2|max:255'],
        ['status', 'Actif', 'required|boolean'],
        ['dismiss_hours', 'Masquer après fermeture', 'integer|min:0|max:168'],
        ['priority', 'Priorité', 'integer|min:0|max:9999'],
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return resolve(MediaLibrary::class)->getMediaUrl($this->image_path);
    }

    public function getPeriodLabelAttribute(): string
    {
        $date = 'Toujours';
        if ($this->starts_at || $this->ends_at) {
            $date = trim(($this->starts_at?->format('d/m/Y H:i') ?? '...').' - '.($this->ends_at?->format('d/m/Y H:i') ?? '...'));
        }

        $time = '';
        if ($this->daily_start_time || $this->daily_end_time) {
            $time = ' · '.substr((string)($this->daily_start_time ?: '00:00'), 0, 5).' à '.substr((string)($this->daily_end_time ?: '23:59'), 0, 5);
        }

        return $date.$time;
    }

    public function scopeActiveNow($query, ?CarbonInterface $now = null)
    {
        $now ??= now();
        $todayTime = $now->format('H:i:s');

        return $query
            ->where('status', true)
            ->where(function($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function($query) use ($todayTime): void {
                $query->where(function($query): void {
                    $query->whereNull('daily_start_time')->whereNull('daily_end_time');
                })->orWhere(function($query) use ($todayTime): void {
                    $query->whereNotNull('daily_start_time')
                        ->whereNotNull('daily_end_time')
                        ->where(function($query) use ($todayTime): void {
                            $query->where(function($query) use ($todayTime): void {
                                $query->whereColumn('daily_start_time', '<=', 'daily_end_time')
                                    ->where('daily_start_time', '<=', $todayTime)
                                    ->where('daily_end_time', '>=', $todayTime);
                            })->orWhere(function($query) use ($todayTime): void {
                                $query->whereColumn('daily_start_time', '>', 'daily_end_time')
                                    ->where(function($query) use ($todayTime): void {
                                        $query->where('daily_start_time', '<=', $todayTime)
                                            ->orWhere('daily_end_time', '>=', $todayTime);
                                    });
                            });
                        });
                });
            })
            ->orderByDesc('priority')
            ->latest('updated_at');
    }
}
