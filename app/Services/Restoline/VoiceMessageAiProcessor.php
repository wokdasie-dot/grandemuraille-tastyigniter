<?php

namespace App\Services\Restoline;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\VoiceMessage;

final class VoiceMessageAiProcessor
{
    public function __construct(private readonly VoiceReservationService $reservations) {}

    public function process(VoiceMessage $message): void
    {
        if (!$this->claimMessage($message)) {
            return;
        }

        try {
            if ((int)$message->recording_duration <= 0) {
                $message->status = 'empty_recording';
                $message->error_message = 'Aucun message vocal enregistré.';
                $message->processed_at = now();
                $message->save();

                Log::info('Message vocal vide ignoré.', [
                    'message_id' => $message->getKey(),
                ]);

                return;
            }

            $apiKey = (string)PrivateEnvironment::get('OPENAI_API_KEY', '');
            if ($apiKey === '') {
                $this->markNeedsHuman($message, 'Clé OpenAI absente.');
                return;
            }

            $audioPath = $this->downloadAudio($message);
            if ((int)$message->recording_duration > 0 && (int)$message->recording_duration < 6) {
                $message->status = 'needs_human';
                $message->error_message = 'Message vocal trop court pour un traitement automatique.';
                $message->processed_at = now();
                $message->save();

                $this->notifyRestaurant($message, 'URGENT - Message vocal trop court', [
                    'Le message vocal dure seulement '.$message->recording_duration.' seconde(s).',
                    'Numéro appelant: '.($message->caller_phone ?: 'inconnu'),
                    'Message dashboard: '.admin_url('restoline/content/voice_messages/preview/'.$message->getKey()),
                    'Audio dashboard: '.($message->audio_dashboard_url ?: 'en préparation'),
                    'Action conseillée: écouter si possible, puis rappeler le client si nécessaire.',
                ]);

                return;
            }

            $transcript = $this->transcribe($apiKey, $audioPath);
            $message->transcript = $transcript;
            $message->status = 'transcribed';
            $message->save();

            $extracted = $this->extractReservation($apiKey, $transcript, $message);
            $extracted = $this->normalizeExtractedPayload($extracted, $transcript);
            $message->extracted_payload = $extracted;
            $message->confidence = (float)($extracted['confidence'] ?? 0);
            $message->save();

            $callerPhone = $this->normalizeBelgianPhone((string)$message->caller_phone);
            $spokenPhone = $this->normalizeBelgianPhone((string)($extracted['phone'] ?? ''));
            $phone = $this->isMobilePhone($callerPhone) ? $callerPhone : $spokenPhone;
            $extraSmsPhones = [];

            $missing = $this->missingFields($extracted);
            if ($missing !== []) {
                $this->markNeedsHuman($message, 'Information manquante ou incertaine: '.implode(', ', $missing));
                return;
            }

            if ($this->isMobilePhone($callerPhone) && $this->isMobilePhone($spokenPhone) && !$this->samePhone($callerPhone, $spokenPhone)) {
                $extraSmsPhones[] = $spokenPhone;
            }

            if (!$this->isMobilePhone($phone)) {
                $this->markNeedsHuman($message, 'Numéro GSM absent ou non reconnu.');
                return;
            }

            $date = (string)$extracted['date'];
            if ($this->reservations->isAnnualLeaveDate($date)) {
                $this->markNeedsHuman($message, 'Date demandée pendant une fermeture configurée.');
                return;
            }

            $comment = 'Réservation créée depuis un message vocal.';
            if ($this->isMobilePhone($callerPhone)) {
                $comment .= ' Numéro appelant utilisé pour la réservation: '.$callerPhone.'.';
            }
            if (trim((string)($extracted['name'] ?? '')) === '') {
                $comment .= ' Nom absent dans le message vocal, réservation liée au numéro de téléphone.';
            }
            if ($this->isMobilePhone($spokenPhone) && !$this->samePhone($phone, $spokenPhone)) {
                $comment .= ' Numéro dicté différent, copie SMS envoyée: '.$spokenPhone.'.';
            }
            $comment .= ' Transcript: '.$transcript;

            $customerName = trim((string)($extracted['name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'Client téléphone';
            }

            $reservation = $this->reservations->createReservation([
                'date' => $date,
                'time' => (string)$extracted['time'],
                'guests' => (int)$extracted['guests'],
                'name' => mb_substr($customerName, 0, 80),
                'phone' => $phone,
                'service' => $this->serviceForTime((string)$extracted['time']),
                'comment' => $comment,
                'status_id' => (int)setting('confirmed_reservation_status'),
            ]);

            $this->reservations->sendAcknowledgement($reservation, $extraSmsPhones);
            $message->status = 'auto_created';
            $message->reservation_id = $reservation->getKey();
            $message->processed_at = now();
            $message->error_message = null;
            $message->save();

            $this->notifyRestaurant($message, 'Réservation vocale créée automatiquement', [
                'Réservation #'.$reservation->getKey(),
                trim($reservation->first_name.' '.$reservation->last_name),
                $reservation->guest_num.' personne(s)',
                $reservation->reserve_date->format('d/m/Y').' '.Carbon::parse($reservation->reserve_time)->format('H:i'),
                'Téléphone: '.$reservation->telephone,
                $extraSmsPhones !== [] ? 'Copie SMS envoyée aussi à: '.implode(', ', $extraSmsPhones) : 'Copie SMS: non',
            ]);
        } catch (\Throwable $exception) {
            if ($this->shouldRetryAudioLater($message, $exception)) {
                return;
            }

            Log::error('Traitement IA message vocal échoué.', [
                'message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);
            $this->markNeedsHuman($message, 'Erreur IA: '.$exception->getMessage());
        }
    }

    private function shouldRetryAudioLater(VoiceMessage $message, \Throwable $exception): bool
    {
        if (!str_contains($exception->getMessage(), 'Téléchargement audio impossible: 404')) {
            return false;
        }

        $payload = is_array($message->payload) ? $message->payload : [];
        $retryCount = (int)($payload['audio_retry_count'] ?? 0) + 1;
        $payload['audio_retry_count'] = $retryCount;
        $payload['audio_retry_last_at'] = now()->toIso8601String();

        $message->payload = $payload;
        $message->status = $retryCount >= 5 ? 'needs_human' : 'received';
        $message->error_message = $retryCount >= 5
            ? 'Audio Twilio indisponible après plusieurs essais.'
            : 'Audio Twilio pas encore disponible, nouvel essai automatique prévu.';
        $message->processed_at = $retryCount >= 5 ? now() : null;
        $message->save();

        if ($retryCount >= 5) {
            $this->notifyRestaurant($message, 'URGENT - Message vocal audio indisponible', [
                'Twilio ne fournit pas encore l’audio après 5 essais.',
                'Numéro appelant: '.($message->caller_phone ?: 'inconnu'),
                'Message dashboard: '.admin_url('restoline/content/voice_messages/preview/'.$message->getKey()),
                'Audio Twilio: '.$message->audio_link,
            ]);
        } else {
            Log::info('Audio Twilio pas encore disponible, message vocal remis en attente.', [
                'message_id' => $message->getKey(),
                'retry_count' => $retryCount,
            ]);
        }

        return true;
    }

    private function claimMessage(VoiceMessage $message): bool
    {
        $updated = VoiceMessage::query()
            ->whereKey($message->getKey())
            ->whereIn('status', ['received', 'error', 'needs_human'])
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            Log::info('Message vocal ignore: deja en traitement ou deja traite.', [
                'message_id' => $message->getKey(),
                'status' => $message->status,
            ]);

            return false;
        }

        $message->refresh();

        return true;
    }

    private function downloadAudio(VoiceMessage $message): string
    {
        $url = $message->audio_link;
        if ($url === '') {
            throw new \RuntimeException('Lien audio absent.');
        }

        $accountSid = (string)PrivateEnvironment::get('TWILIO_ACCOUNT_SID', '');
        $authToken = (string)PrivateEnvironment::get('TWILIO_AUTH_TOKEN', '');
        $directory = storage_path('app/restoline/voice-messages');
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier audio.');
        }

        $path = $directory.'/voice-'.$message->getKey().'.mp3';
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERPWD => $accountSid.':'.$authToken,
        ]);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $body === '' || $status >= 400) {
            throw new \RuntimeException('Téléchargement audio impossible: '.$status.' '.$error);
        }

        file_put_contents($path, $body);
        chmod($path, 0640);
        $this->publishDashboardAudio($message, $body);
        $message->audio_local_path = $path;
        $message->expires_at = now()->addDays(7);
        $message->save();

        return $path;
    }

    private function publishDashboardAudio(VoiceMessage $message, string $body): void
    {
        $directory = public_path('audio/voice-messages');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier audio public.');
        }

        $path = $directory.'/'.$message->audioToken().'.mp3';
        file_put_contents($path, $body);
        chmod($path, 0644);
    }

    private function transcribe(string $apiKey, string $audioPath): string
    {
        $model = (string)PrivateEnvironment::get('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-mini-transcribe');
        $response = $this->multipartOpenAi(
            'https://api.openai.com/v1/audio/transcriptions',
            $apiKey,
            [
                'model' => $model,
                'language' => 'fr',
                'response_format' => 'json',
                'file' => new \CURLFile($audioPath, 'audio/mpeg', basename($audioPath)),
            ],
        );

        $text = trim((string)($response['text'] ?? ''));
        if ($text === '') {
            throw new \RuntimeException('Transcript vide.');
        }

        return $text;
    }

    private function extractReservation(string $apiKey, string $transcript, VoiceMessage $message): array
    {
        $model = (string)PrivateEnvironment::get('OPENAI_EXTRACT_MODEL', 'gpt-4.1-mini');
        $today = now(config('app.timezone'))->format('Y-m-d');
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => 'string'],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'time' => ['type' => 'string', 'description' => 'HH:MM 24h'],
                'guests' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
                'phone' => ['type' => 'string'],
                'notes' => ['type' => 'string'],
                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                'missing_fields' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['name', 'date', 'time', 'guests', 'phone', 'notes', 'confidence', 'missing_fields'],
        ];

        $payload = [
            'model' => $model,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => "Aujourd'hui: {$today}. Fuseau: Europe/Brussels. Restaurant ouvert mardi-dimanche midi/soir, fermé lundi. Si une date est donnée sans année et est déjà passée cette année, prends l'année suivante. Si le client dit samedi, vendredi, demain, après-demain, mardi prochain, calcule la date exacte. Pour une demande sans heure exacte mais avec service explicite, utilise 12:00 pour midi et 18:30 pour soir. Si le client donne seulement une date ou un jour sans dire midi, soir, déjeuner, dîner, ni heure précise, mets time vide et ajoute time dans missing_fields. Numéro appelant: ".((string)$message->caller_phone ?: 'inconnu').". Extrait uniquement les infos de réservation depuis ce message vocal:\n".$transcript,
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'voice_reservation',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = $this->jsonOpenAi('https://api.openai.com/v1/responses', $apiKey, $payload);
        $json = $response['output_text'] ?? null;
        if (!is_string($json) || $json === '') {
            $json = $this->firstResponseText($response);
        }

        $decoded = json_decode((string)$json, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Réponse IA JSON invalide.');
        }

        return $decoded;
    }

    private function normalizeExtractedPayload(array $extracted, string $transcript): array
    {
        $missing = array_values(array_filter((array)($extracted['missing_fields'] ?? [])));
        if (in_array('time', $missing, true)) {
            $defaultTime = $this->defaultTimeFromTranscript($transcript);
            if ($defaultTime !== '') {
                $extracted['time'] = $defaultTime;
                $missing = array_values(array_filter(
                    $missing,
                    fn($field): bool => $field !== 'time',
                ));
                $extracted['notes'] = trim((string)($extracted['notes'] ?? '').' Heure par défaut appliquée selon le service annoncé.');
            }
        }

        $extracted['missing_fields'] = $missing;

        foreach (['date', 'time', 'phone'] as $field) {
            if (in_array($field, $missing, true)) {
                $extracted[$field] = '';
            }
        }

        if (in_array('guests', $missing, true)) {
            $extracted['guests'] = 0;
        }

        return $extracted;
    }

    private function defaultTimeFromTranscript(string $transcript): string
    {
        $text = mb_strtolower($transcript);
        $text = str_replace(['’', "'"], ' ', $text);

        if (preg_match('/\b(ce|demain|apres-demain|après-demain)?\s*(soir|soiree|soirée|diner|dîner)\b/u', $text)) {
            return '18:30';
        }

        if (preg_match('/\b(ce|demain|apres-demain|après-demain)?\s*(midi|dejeuner|déjeuner|lunch)\b/u', $text)) {
            return '12:00';
        }

        return '';
    }

    private function missingFields(array $extracted): array
    {
        $missing = array_values(array_filter(
            (array)($extracted['missing_fields'] ?? []),
            fn($field): bool => $field !== 'name',
        ));
        foreach (['date', 'time', 'guests'] as $field) {
            if (empty($extracted[$field])) {
                $missing[] = $field;
            }
        }
        if ((float)($extracted['confidence'] ?? 0) < 0.72) {
            $missing[] = 'confiance IA faible';
        }

        return array_values(array_unique($missing));
    }

    private function serviceForTime(string $time): string
    {
        return ((int)substr($time, 0, 2)) < 16 ? 'lunch' : 'dinner';
    }

    private function normalizeBelgianPhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }
        if (str_starts_with($phone, '0')) {
            $phone = '+32'.substr($phone, 1);
        }

        return $phone;
    }

    private function isMobilePhone(string $phone): bool
    {
        return (bool)preg_match('/^\+324\d{8}$/', $phone);
    }

    private function samePhone(string $left, string $right): bool
    {
        return $this->normalizeBelgianPhone($left) === $this->normalizeBelgianPhone($right);
    }

    private function markNeedsHuman(VoiceMessage $message, string $reason): void
    {
        $message->status = 'needs_human';
        $message->error_message = $reason;
        $message->processed_at = now();
        $message->save();

        $this->notifyRestaurant($message, 'URGENT - Message vocal à vérifier', [
            $reason,
            'Numéro appelant: '.($message->caller_phone ?: 'inconnu'),
            'Message dashboard: '.admin_url('restoline/content/voice_messages/preview/'.$message->getKey()),
            'Créer réservation: '.$message->manual_reservation_url,
            'Audio dashboard: '.($message->audio_dashboard_url ?: 'en préparation'),
            'Audio Twilio: '.$message->audio_link,
            'Infos reconnues: '.($message->extracted_summary ?: 'non disponible'),
            'Transcript: '.($message->transcript ?: 'non disponible'),
        ]);
    }

    private function notifyRestaurant(VoiceMessage $message, string $subject, array $lines): void
    {
        $to = (string)PrivateEnvironment::get('RESTAURANT_NOTIFICATION_EMAIL', 'jinzhu1031@gmail.com');
        try {
            Mail::raw(
                implode("\n", array_merge([
                    "La Grande Muraille de Chine",
                    'Message vocal #'.$message->getKey(),
                    'Reçu le: '.$message->created_at?->format('d/m/Y H:i:s'),
                    '',
                ], $lines)),
                fn($mail) => $mail->to($to)->subject($subject),
            );
        } catch (\Throwable $exception) {
            Log::warning('Email message vocal non envoyé.', [
                'message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function jsonOpenAi(string $url, string $apiKey, array $payload): array
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $status >= 400) {
            throw new \RuntimeException('Erreur OpenAI '.$status.' '.$error.' '.$body);
        }

        return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }

    private function multipartOpenAi(string $url, string $apiKey, array $fields): array
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
        ]);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $status >= 400) {
            throw new \RuntimeException('Erreur transcription OpenAI '.$status.' '.$error.' '.$body);
        }

        return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }

    private function firstResponseText(array $response): string
    {
        foreach ((array)($response['output'] ?? []) as $item) {
            foreach ((array)($item['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return '';
    }
}
