<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;

class ReservationFeedback extends Model
{
    use Validation;

    protected $table = 'restoline_reservation_feedbacks';

    protected $primaryKey = 'feedback_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'reserve_date' => 'date',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'google_clicked_at' => 'datetime',
        'followed_up_at' => 'datetime',
    ];

    public $rules = [
        ['reservation_id', 'Réservation', 'required|integer'],
        ['token', 'Token', 'required|string|max:80'],
        ['email', 'Email', 'nullable|email|max:190'],
        ['reservation_channel', 'Canal réservation', 'required|string|max:20'],
    ];

    public function getReservationChannelLabelAttribute(): string
    {
        return match ((string)$this->reservation_channel) {
            'online' => 'En ligne',
            'phone' => 'Téléphone',
            default => 'Non précisé',
        };
    }

    public function getVisitAnswerLabelAttribute(): string
    {
        return match ((string)$this->visit_answer) {
            'ok' => 'Visite OK',
            'problem' => 'Problème visite',
            default => '-',
        };
    }

    public function getReservationAnswerLabelAttribute(): string
    {
        return match ((string)$this->reservation_answer) {
            'ok' => 'Réservation OK',
            'bug' => 'Bug réservation',
            default => '-',
        };
    }

    public function getSentLabelAttribute(): string
    {
        return $this->sent_at?->format('d/m/Y H:i') ?? '-';
    }

    public function getRespondedLabelAttribute(): string
    {
        return $this->responded_at?->format('d/m/Y H:i') ?? '-';
    }

    public function getGoogleClickedLabelAttribute(): string
    {
        return $this->google_clicked_at?->format('d/m/Y H:i') ?? '-';
    }

    public function getFollowUpStatusOptions(): array
    {
        return [
            'new' => 'À vérifier',
            'contacted' => 'Client contacté',
            'resolved' => 'Résolu',
            'ignored' => 'Sans action',
        ];
    }

    public function getFollowUpStatusLabelAttribute(): string
    {
        if (!$this->responded_at) {
            return 'En attente de réponse';
        }

        return $this->getFollowUpStatusOptions()[$this->follow_up_status] ?? 'À vérifier';
    }

    public function getFollowedUpLabelAttribute(): string
    {
        return $this->followed_up_at?->format('d/m/Y H:i') ?? '-';
    }

    public function beforeSave(): void
    {
        if ($this->isDirty('follow_up_status') && $this->follow_up_status !== 'new' && !$this->followed_up_at) {
            $this->followed_up_at = now();
        }
    }
}
