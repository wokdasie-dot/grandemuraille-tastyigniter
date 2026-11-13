<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Models\TelephonySetting;
use Restoline\Content\Models\VoiceMessage;
use SplFileInfo;

class CleanVoiceAudio extends Command
{
    protected $signature = 'restoline:clean-voice-audio
        {--days=30 : Age minimum des anciens audios de synthese a supprimer}
        {--message-days=30 : Age minimum des anciens messages vocaux traites a supprimer de la base}
        {--dry-run : Affiche sans supprimer}';

    protected $description = 'Supprime les anciens fichiers audio du repondeur qui ne sont plus references.';

    public function handle(): int
    {
        $days = max(1, (int)$this->option('days'));
        $dryRun = (bool)$this->option('dry-run');
        $directory = public_path('audio/voice-charline');

        if (!is_dir($directory)) {
            $this->info('Dossier audio absent, rien a nettoyer.');

            return self::SUCCESS;
        }

        $keep = TelephonySetting::current()->voiceAudioFilenames();
        $threshold = now()->subDays($days)->getTimestamp();
        $deleted = 0;
        $kept = 0;
        $candidates = 0;
        $bytes = 0;

        foreach (glob($directory.'/voice-*.mp3') ?: [] as $path) {
            $file = new SplFileInfo($path);
            $filename = $file->getFilename();

            if (in_array($filename, $keep, true) || $file->getMTime() >= $threshold) {
                $kept++;
                continue;
            }

            $candidates++;
            $bytes += $file->getSize();

            if (!$dryRun && @unlink($path)) {
                $deleted++;
            }
        }

        $this->info(sprintf(
            '%s audios annonces: %d fichier(s) supprimable(s), %d supprime(s), %d conserve(s), %.2f Mo concernes.',
            $dryRun ? 'Simulation' : 'Nettoyage',
            $candidates,
            $deleted,
            $kept,
            $bytes / 1024 / 1024,
        ));

        $this->cleanVoiceMessages(max(7, (int)$this->option('message-days')), $dryRun);

        return self::SUCCESS;
    }

    private function cleanVoiceMessages(int $messageDays, bool $dryRun): void
    {
        if (!class_exists(VoiceMessage::class)) {
            return;
        }

        $expiredAudio = VoiceMessage::query()
            ->whereNotNull('audio_local_path')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $audioDeleted = 0;
        $audioBytes = 0;
        foreach ($expiredAudio as $message) {
            $path = (string)$message->audio_local_path;
            if ($path !== '' && is_file($path)) {
                $audioBytes += filesize($path) ?: 0;
                if (!$dryRun && @unlink($path)) {
                    $audioDeleted++;
                }
            }
            $publicPath = public_path('audio/voice-messages/'.$message->audioToken().'.mp3');
            if (is_file($publicPath)) {
                $audioBytes += filesize($publicPath) ?: 0;
                if (!$dryRun && @unlink($publicPath)) {
                    $audioDeleted++;
                }
            }

            if (!$dryRun) {
                $message->audio_local_path = null;
                $message->save();
            }
        }

        $oldMessages = VoiceMessage::query()
            ->whereIn('status', ['auto_created', 'needs_human', 'error'])
            ->where('created_at', '<', now()->subDays($messageDays));

        $oldCount = (clone $oldMessages)->count();
        if (!$dryRun && $oldCount > 0) {
            $oldMessages->delete();
        }

        $this->info(sprintf(
            '%s messages vocaux: %d audio(s) supprime(s), %.2f Mo liberes, %d ancien(s) message(s) nettoye(s).',
            $dryRun ? 'Simulation' : 'Nettoyage',
            $audioDeleted,
            $audioBytes / 1024 / 1024,
            $oldCount,
        ));
    }
}
