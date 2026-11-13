<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\Log;

class TelephonySetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_telephony_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'status' => 'boolean',
        'annual_leave_enabled' => 'boolean',
        'call_limit_per_hour' => 'integer',
        'call_limit_per_day' => 'integer',
        'max_call_seconds' => 'integer',
        'max_call_steps' => 'integer',
    ];

    public $rules = [
        ['status', 'Actif', 'required|boolean'],
        ['welcome_message', 'Message accueil', 'required|string|max:1000'],
        ['annual_leave_enabled', 'Annonce congés active', 'required|boolean'],
        ['annual_leave_message', 'Annonce congés', 'nullable|string|max:1000'],
        ['main_menu_message', 'Options du menu principal', 'required|string|max:1500'],
        ['phone_reservation_message', 'Annonce réservation téléphone', 'required|string|max:1000'],
        ['invalid_menu_message', 'Message choix invalide', 'required|string|max:1000'],
        ['tariffs_message', 'Annonce tarifs buffet', 'required|string|max:2500'],
        ['opening_hours_message', 'Annonce horaires ouverture', 'required|string|max:1500'],
        ['sms_number_notice', 'Annonce numéro SMS appelé', 'required|string|max:1000'],
        ['rate_limited_message', 'Message limite appels', 'required|string|max:1000'],
        ['session_limited_message', 'Message session trop longue', 'required|string|max:1000'],
        ['call_limit_per_hour', 'Limite appels par heure', 'required|integer|min:1|max:50'],
        ['call_limit_per_day', 'Limite appels par jour', 'required|integer|min:1|max:200'],
        ['max_call_seconds', 'Durée maximale appel', 'required|integer|min:60|max:1800'],
        ['max_call_steps', 'Nombre maximal étapes', 'required|integer|min:5|max:60'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create(static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'status' => true,
            'welcome_message' => "Bonjour, La Grande Muraille de Chine.",
            'annual_leave_enabled' => true,
            'annual_leave_message' => "Conges annuels. Reouverture mardi 4 aout a midi.",
            'main_menu_message' => "Pour recevoir le lien de reservation en ligne par SMS, tapez 2. Pour connaitre nos horaires d'ouverture, tapez 3. Pour connaitre nos tarifs buffet, tapez 4. Pour laisser une reservation vocale, restez en ligne apres le message.",
            'phone_reservation_message' => "Apres le bip, laissez votre reservation en precisant votre nom, votre numero de telephone, la date, l'heure et le nombre de personnes. Vous recevrez un SMS de confirmation dans les minutes qui suivent, ou au debut du service. Sans confirmation, merci de rappeler le restaurant pendant les heures d'ouverture.",
            'invalid_menu_message' => "Choix invalide. Pour recevoir le lien de reservation en ligne par SMS, tapez 2. Pour connaitre nos horaires, tapez 3. Pour connaitre nos tarifs buffet, tapez 4. Pour laisser une reservation vocale, restez en ligne.",
            'tariffs_message' => "Nos tarifs buffet. Du mardi au samedi midi : 21 euros, ou 27 euros avec boissons. Du mardi au jeudi soir : 29 euros, ou 37 euros avec boissons. Vendredi soir et samedi soir : 33 euros, ou 41 euros avec boissons. Dimanche et jours feries, midi et soir : 31 euros, ou 39 euros avec boissons. Enfants de 4 a 11 ans : moitie prix. Senior, uniquement le dimanche midi : 29 euros, ou 37 euros avec boissons. Pour revenir au menu principal, tapez 1.",
            'opening_hours_message' => "Nos horaires d'ouverture. Le restaurant est ouvert du mardi au dimanche. Le midi, de 12 heures a 15 heures. Le soir, de 18 heures a 23 heures. Nous sommes fermes le lundi, sauf jour ferie. Pour revenir au menu principal, tapez 1.",
            'sms_number_notice' => "Numero reserve aux SMS. Pour appeler le restaurant : 065 78 33 78.",
            'rate_limited_message' => "Trop d'appels. Merci de reserver sur notre site ou de reessayer plus tard.",
            'session_limited_message' => "Session terminee. Merci de reserver sur notre site ou de rappeler le restaurant.",
            'call_limit_per_hour' => 4,
            'call_limit_per_day' => 12,
            'max_call_seconds' => 480,
            'max_call_steps' => 18,
        ];
    }

    public function greeting(): string
    {
        $parts = [(string)$this->welcome_message];

        if ($this->annual_leave_enabled && trim((string)$this->annual_leave_message) !== '') {
            $parts[] = (string)$this->annual_leave_message;
        }

        $parts[] = (string)$this->main_menu_message;

        return implode(' ', array_filter(array_map('trim', $parts)));
    }

    public function afterSave(): void
    {
        $this->ensureVoiceAudioFiles();
    }

    public function ensureVoiceAudioFiles(bool $force = false): array
    {
        $result = [
            'generated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($this->voiceMessages() as $message) {
            $status = $this->ensureVoiceAudioFile($message, $force);
            $result[$status]++;
        }

        return $result;
    }

    public function voiceAudioStatus(): string
    {
        $missing = 0;
        $total = 0;

        foreach ($this->voiceMessages() as $message) {
            $total++;
            if (!is_file($this->voiceAudioPath($message))) {
                $missing++;
            }
        }

        return $missing === 0
            ? 'OK - '.$total.' fichier(s) audio prets'
            : 'A verifier - '.$missing.' fichier(s) audio manquant(s) sur '.$total;
    }

    public function getVoiceAudioStatusAttribute(): string
    {
        return $this->voiceAudioStatus();
    }

    public function voiceAudioFilenames(): array
    {
        return array_values(array_unique(array_map(
            fn(string $message): string => 'voice-'.sha1(trim($message)).'.mp3',
            $this->voiceMessages(),
        )));
    }

    private function voiceMessages(): array
    {
        return array_values(array_filter(array_map('trim', [
            $this->greeting(),
            (string)$this->welcome_message,
            (string)$this->annual_leave_message,
            (string)$this->main_menu_message,
            (string)$this->phone_reservation_message,
            (string)$this->invalid_menu_message,
            (string)$this->tariffs_message,
            (string)$this->opening_hours_message,
            (string)$this->sms_number_notice,
            (string)$this->rate_limited_message,
            (string)$this->session_limited_message,
            'Je n ai pas compris. Reessayons.',
            "Je n'ai pas reçu de message. Merci de rappeler pendant les horaires d'ouverture.",
            "Merci. Votre demande de reservation est bien enregistree. Un SMS de confirmation vous sera envoye apres validation. Sans confirmation, merci de contacter le restaurant pendant les horaires d'ouverture.",
            "Merci, et a bientot chez La Grande Muraille de Chine.",
        ])));
    }

    private function ensureVoiceAudioFile(string $message, bool $force = false): string
    {
        $targetPath = $this->voiceAudioPath($message);
        if (!$force && is_file($targetPath)) {
            return 'skipped';
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if ($force && is_file($targetPath)) {
            @unlink($targetPath);
        }

        $python = (string) env('RESTOLINE_VOICE_PYTHON', '');
        if ($python === '' || !is_file($python)) {
            Log::warning('Restoline voice audio generation skipped: no readable interpreter was configured.');

            return 'failed';
        }

        $tmpText = tempnam(sys_get_temp_dir(), 'restoline-voice-').'.txt';
        $tmpAudio = tempnam(sys_get_temp_dir(), 'restoline-voice-').'.mp3';

        file_put_contents($tmpText, $message);

        $command = escapeshellarg($python)
            .' -m edge_tts --voice fr-BE-CharlineNeural'
            .' --file '.escapeshellarg($tmpText)
            .' --write-media '.escapeshellarg($tmpAudio);

        exec($command.' 2>&1', $output, $exitCode);

        if ($exitCode === 0 && is_file($tmpAudio) && filesize($tmpAudio) > 0) {
            rename($tmpAudio, $targetPath);
            @chmod($targetPath, 0664);
            $status = 'generated';
        } else {
            Log::warning('Restoline voice audio generation failed', [
                'exit_code' => $exitCode,
                'output' => implode("\n", $output),
            ]);
            $status = 'failed';
        }

        @unlink($tmpText);
        @unlink($tmpAudio);

        return $status;
    }

    private function voiceAudioPath(string $message): string
    {
        return public_path('audio/voice-charline/voice-'.sha1(trim($message)).'.mp3');
    }
}
