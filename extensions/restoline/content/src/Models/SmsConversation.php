<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;

class SmsConversation extends Model
{
    protected $table = 'restoline_sms_conversations';

    protected $primaryKey = 'conversation_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'draft_payload' => 'array',
        'last_message_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'last_outbound_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            'open' => 'Ouverte',
            'awaiting_customer' => 'Réponse client attendue',
            'reservation_created' => 'Réservation créée',
            'needs_human' => 'À vérifier',
            'human_handling' => 'Pris en charge par le restaurant',
            'closed' => 'Clôturée',
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

    public function getLastMessagePreviewAttribute(): string
    {
        return mb_substr(trim((string)$this->last_message), 0, 180);
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        $outbound = SmsConversationMessage::query()
            ->where('conversation_id', $this->getKey())
            ->where('direction', 'outbound')
            ->latest('message_id')
            ->first();

        if (!$outbound) {
            return 'Aucun SMS sortant';
        }

        $gateway = SmsGatewayMessage::query()
            ->where('to_number', $this->phone)
            ->latest('sms_gateway_message_id')
            ->first();

        if ($gateway && $gateway->created_at && $outbound->created_at && $gateway->created_at >= $outbound->created_at) {
            return self::deliveryStatusLabel((string)$gateway->status, (bool)$gateway->fallback_sent_at);
        }

        return self::deliveryStatusLabel((string)$outbound->status, false);
    }

    private static function deliveryStatusLabel(string $status, bool $fallback): string
    {
        if ($fallback || $status === 'fallback_twilio_sent') {
            return 'Secours Twilio envoyé';
        }

        return match (mb_strtolower($status)) {
            'delivered' => 'Livré au client',
            'sent' => 'Envoyé par GSM',
            'accepted', 'processed' => 'Accepté par GSM',
            'failed', 'error', 'rejected', 'cancelled', 'canceled' => 'Échec d’envoi',
            'sending' => 'En cours d’envoi',
            'reservation_confirmed' => 'Confirmation envoyée',
            'reservation_modified' => 'Modification envoyée',
            'reservation_cancelled' => 'Annulation envoyée',
            'question_sent' => 'Question envoyée',
            'request_forwarded' => 'Transmis à l’équipe',
            'needs_human' => 'À traiter par l’équipe',
            default => $status !== '' ? 'Traité: '.$status : 'Statut inconnu',
        };
    }

    public function getDraftSummaryAttribute(): string
    {
        $draft = is_array($this->draft_payload) ? $this->draft_payload : [];
        $parts = [];

        foreach (['name', 'date', 'time', 'guests'] as $key) {
            if (!empty($draft[$key])) {
                $parts[] = $key.': '.$draft[$key];
            }
        }

        return implode(' · ', $parts);
    }

    public function getReservationAdminUrlAttribute(): string
    {
        if (!$this->reservation_id) {
            return '';
        }

        return admin_url('reservations/edit/'.$this->reservation_id);
    }

    public function getMessagesSummaryAttribute(): string
    {
        return SmsConversationMessage::query()
            ->where('conversation_id', $this->getKey())
            ->orderBy('message_id')
            ->get()
            ->map(function(SmsConversationMessage $message): string {
                $direction = $message->direction === 'outbound' ? 'Restaurant' : 'Client';
                $date = $message->created_at?->format('d/m/Y H:i') ?? '';
                $intent = $message->intent ? ' ['.$message->intent.']' : '';

                $status = $message->direction === 'outbound'
                    ? ' ['.self::deliveryStatusLabel((string)$message->status, false).']'
                    : '';

                $error = $message->error_message ? ' - Erreur: '.mb_substr((string)$message->error_message, 0, 180) : '';

                return trim($date.' '.$direction.$intent.$status.': '.trim((string)$message->body).$error);
            })
            ->implode("\n\n");
    }
}
