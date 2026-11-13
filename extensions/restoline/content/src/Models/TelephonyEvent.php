<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class TelephonyEvent extends Model
{
    protected $table = 'restoline_telephony_events';

    protected $primaryKey = 'event_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function channelOptions(): array
    {
        return [
            'voice' => 'Répondeur',
            'sms' => 'SMS réservation',
        ];
    }

    public static function eventOptions(): array
    {
        return [
            'voice_incoming' => 'Appel entrant',
            'voice_sms_number_notice' => 'Appel sur numéro SMS',
            'voice_status' => 'Statut appel Twilio',
            'voice_menu_choice' => 'Choix menu vocal',
            'voice_reservation_voicemail_started' => 'Message vocal réservation démarré',
            'voice_reservation_voicemail' => 'Message vocal réservation reçu',
            'voice_reservation_voicemail_email_failed' => 'Email message vocal échoué',
            'voice_date_choice' => 'Choix date',
            'voice_exact_date_entered' => 'Date tapée',
            'voice_service_choice' => 'Choix midi/soir',
            'voice_time_choice' => 'Choix horaire',
            'voice_guests_entered' => 'Nombre personnes',
            'voice_name_captured' => 'Nom dicté',
            'voice_confirm_choice' => 'Confirmation vocale',
            'voice_reservation_confirmed' => 'Réservation créée',
            'voice_reservation_failed' => 'Échec création réservation',
            'voice_sms_link_requested' => 'Lien réservation envoyé',
            'voice_sms_instructions_requested' => 'Instructions SMS envoyées',
            'voice_tariffs_requested' => 'Tarifs buffet demandés',
            'voice_hours_requested' => 'Horaires demandés',
            'voice_rate_limited' => 'Appel limité sécurité',
            'voice_session_limited' => 'Session trop longue',
            'voice_unavailable' => 'Créneau indisponible',
            'voice_menu_invalid_limited' => 'Touches invalides répétées',
            'sms_incoming' => 'SMS entrant',
            'sms_incoming_disabled' => 'SMS entrant ignoré',
            'sms_status' => 'Statut SMS Twilio',
            'sms_gateway_accepted' => 'SMS accepté par GSM',
            'sms_gateway_status' => 'Statut SMS GSM',
            'sms_gateway_fallback_twilio' => 'Secours Twilio utilisé',
            'sms_twilio_sent' => 'SMS envoyé par Twilio',
            'sms_rate_limited' => 'SMS limité sécurité',
            'sms_modify_request' => 'Demande modification SMS',
            'sms_cancel_request' => 'Demande annulation SMS',
            'sms_unknown' => 'SMS non reconnu',
            'sms_incomplete' => 'SMS incomplet',
            'sms_unavailable' => 'Créneau SMS indisponible',
            'sms_reservation_created' => 'Réservation SMS créée',
            'sms_error' => 'Erreur SMS',
        ];
    }

    public function getChannelOptions(): array
    {
        return self::channelOptions();
    }

    public function getEventTypeOptions(): array
    {
        return self::eventOptions();
    }

    public function getCreatedLabelAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : '';
    }

    public function getChannelLabelAttribute(): string
    {
        return self::channelOptions()[$this->channel] ?? (string)$this->channel;
    }

    public function getEventLabelAttribute(): string
    {
        return self::eventOptions()[$this->event_type] ?? (string)$this->event_type;
    }

    public function getPayloadSummaryAttribute(): string
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        $summary = [];

        foreach (['option', 'date', 'service', 'time', 'guests', 'caller_phone', 'recording_duration', 'recording_url', 'provider', 'event', 'reason', 'body_length', 'attempts', 'error_code'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $summary[] = $key.': '.$payload[$key];
            }
        }

        return implode(' · ', $summary);
    }
}
