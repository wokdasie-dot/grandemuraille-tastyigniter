<?php

declare(strict_types=1);

namespace Restoline\Content\Console;

use Illuminate\Console\Command;
use Restoline\Content\Models\VoiceMessage;

class ProcessVoiceMessages extends Command
{
    protected $signature = 'restoline:process-voice-messages {message_id?}';

    protected $description = 'Traite les messages vocaux de réservation avec l’IA.';

    public function handle(): int
    {
        $service = app(\App\Services\Restoline\VoiceMessageAiProcessor::class);
        $id = $this->argument('message_id');

        $requeued = VoiceMessage::query()
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'received',
                'error_message' => 'Traitement précédent interrompu, nouvel essai automatique prévu.',
                'updated_at' => now(),
            ]);

        if ($requeued > 0) {
            $this->warn($requeued.' message(s) vocal(aux) remis en file après blocage.');
        }

        $query = VoiceMessage::query()->orderBy('created_at');

        if ($id) {
            $query->where('message_id', (int)$id);
            $query->whereIn('status', ['received', 'error', 'needs_human']);
        } else {
            $query->where('status', 'received');
            $query->limit(10);
        }

        $count = 0;
        foreach ($query->get() as $message) {
            $service->process($message);
            $count++;
        }

        $this->info($count.' message(s) traité(s).');

        return self::SUCCESS;
    }
}
