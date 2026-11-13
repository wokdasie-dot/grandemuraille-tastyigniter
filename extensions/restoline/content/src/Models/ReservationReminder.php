<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class ReservationReminder extends Model
{
    protected $table = 'restoline_reservation_reminders';

    protected $primaryKey = 'reminder_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'reserve_date' => 'date',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function getEmailStatusLabelAttribute(): string
    {
        return $this->statusLabel((string)$this->email_status);
    }

    public function getSmsStatusLabelAttribute(): string
    {
        return $this->statusLabel((string)$this->sms_status);
    }

    public function getOpenedLabelAttribute(): string
    {
        return $this->opened_at ? 'Lien ouvert le '.$this->opened_at->format('d/m/Y H:i') : 'Pas encore ouvert';
    }

    public function getReservationAdminUrlAttribute(): string
    {
        return admin_url('reservations/edit/'.$this->reservation_id);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'sent' => 'Envoyé',
            'failed' => 'Échec',
            'skipped' => 'Non disponible',
            default => 'En attente',
        };
    }
}
