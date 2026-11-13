<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\VoiceMessage;

class CheckVoiceMessages extends Command
{
    protected $signature = 'restoline:check-voice-messages {--minutes=10 : Age minimum avant alerte}';

    protected $description = 'Alerte le restaurant si un message vocal reste bloque ou sans audio.';

    public function handle(): int
    {
        $minutes = max(3, (int)$this->option('minutes'));
        $threshold = now()->subMinutes($minutes);
        $messages = VoiceMessage::query()
            ->where('created_at', '<=', $threshold)
            ->where(function($query): void {
                $query->whereIn('status', ['received', 'processing', 'error'])
                    ->orWhere(function($query): void {
                        $query->where('status', 'needs_human')
                            ->where(function($query): void {
                                $query->whereNull('audio_local_path')
                                    ->orWhere('audio_local_path', '');
                            });
                    });
            })
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        $alerted = 0;
        foreach ($messages as $message) {
            $payload = is_array($message->payload) ? $message->payload : [];
            if (!empty($payload['watchdog_alert_sent_at'])) {
                continue;
            }

            $this->notifyRestaurant($message);
            $payload['watchdog_alert_sent_at'] = now()->toIso8601String();
            $message->payload = $payload;
            $message->save();
            $alerted++;
        }

        $this->info($alerted.' alerte(s) message vocal envoyee(s).');

        return self::SUCCESS;
    }

    private function notifyRestaurant(VoiceMessage $message): void
    {
        $to = (string)env('RESTAURANT_NOTIFICATION_EMAIL', config('mail.from.address', 'jinzhu1031@gmail.com'));
        $audioDashboard = (string)$message->audio_dashboard_url;
        $lines = [
            "La Grande Muraille de Chine",
            'ALERTE - Message vocal non traite correctement',
            'Message vocal #'.$message->getKey(),
            'Recu le: '.$message->created_at?->format('d/m/Y H:i:s'),
            'Statut: '.$message->status_label,
            'Duree: '.(int)$message->recording_duration.' seconde(s)',
            'Numero appelant: '.($message->caller_phone ?: 'inconnu'),
            'Erreur: '.($message->error_message ?: 'non precisee'),
            'Message dashboard: '.admin_url('restoline/content/voice_messages/preview/'.$message->getKey()),
            'Audio dashboard: '.($audioDashboard !== '' ? $audioDashboard : 'indisponible'),
            'Audio Twilio: '.$message->audio_link,
            '',
            'Action: verifier le message dans le dashboard ou via Twilio, puis rappeler le client si necessaire.',
        ];

        try {
            Mail::raw(implode("\n", $lines), fn($mail) => $mail->to($to)->subject('URGENT - Message vocal a verifier'));
        } catch (\Throwable $exception) {
            Log::warning('Alerte watchdog message vocal non envoyee.', [
                'message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
