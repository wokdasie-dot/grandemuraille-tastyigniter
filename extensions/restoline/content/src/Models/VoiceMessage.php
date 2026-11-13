<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class VoiceMessage extends Model
{
    protected $table = 'restoline_voice_messages';

    protected $primaryKey = 'message_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'payload' => 'array',
        'extracted_payload' => 'array',
        'confidence' => 'float',
        'recording_duration' => 'integer',
        'processed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            'received' => 'Reçu',
            'processing' => 'Traitement en cours',
            'transcribed' => 'Transcrit',
            'auto_created' => 'Réservation créée',
            'empty_recording' => 'Message vide',
            'needs_human' => 'À vérifier',
            'error' => 'Erreur',
        ];
    }

    public function getStatusOptions(): array
    {
        return self::statusOptions();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? (string)$this->status;
    }

    public function getCreatedLabelAttribute(): string
    {
        return $this->created_at?->format('d/m/Y H:i') ?? '';
    }

    public function getProcessedLabelAttribute(): string
    {
        return $this->processed_at?->format('d/m/Y H:i') ?? '';
    }

    public function getCallerLabelAttribute(): string
    {
        return (string)($this->caller_phone ?: 'Numéro masqué/inconnu');
    }

    public function getExtractedSummaryAttribute(): string
    {
        $payload = is_array($this->extracted_payload) ? $this->extracted_payload : [];
        $parts = [];

        foreach (['name', 'date', 'time', 'guests', 'phone'] as $key) {
            if (!empty($payload[$key])) {
                $parts[] = $key.': '.$payload[$key];
            }
        }

        return implode(' · ', $parts);
    }

    public function getConfidenceLabelAttribute(): string
    {
        if ($this->confidence === null || $this->confidence === '') {
            return '';
        }

        return (string)round((float)$this->confidence * 100).'%';
    }

    public function getAudioLinkAttribute(): string
    {
        $url = (string)($this->recording_url ?: '');
        if ($url === '') {
            return '';
        }

        return str_ends_with($url, '.mp3') ? $url : $url.'.mp3';
    }

    public function getAudioDashboardUrlAttribute(): string
    {
        $localPath = (string)($this->audio_local_path ?: '');
        if ($localPath !== '' && is_file($localPath)) {
            return route('restoline.voice-messages.audio', [
                'message' => $this->getKey(),
                'token' => $this->audioToken(),
            ]);
        }

        $relativePath = 'audio/voice-messages/'.$this->audioToken().'.mp3';
        if (!is_file(public_path($relativePath))) {
            return '';
        }

        return url('/'.$relativePath);
    }

    public function getAudioStatusLabelAttribute(): string
    {
        return $this->audio_dashboard_url !== '' ? 'Disponible' : 'En préparation';
    }

    public function getReservationAdminUrlAttribute(): string
    {
        if (!$this->reservation_id) {
            return '';
        }

        return admin_url('reservations/edit/'.$this->reservation_id);
    }

    public function getManualReservationUrlAttribute(): string
    {
        if ($this->reservation_id) {
            return $this->reservation_admin_url;
        }

        return admin_url('reservations/create?'.http_build_query([
            'voice_message_id' => $this->getKey(),
        ]));
    }

    public function audioToken(): string
    {
        return hash_hmac(
            'sha256',
            $this->getKey().'|'.(string)$this->recording_sid,
            (string)config('app.key'),
        );
    }
}
