<?php

namespace App\Http\Controllers\Restoline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VoiceMessageAudioController extends Controller
{
    public function show(Request $request, int $message): BinaryFileResponse
    {
        $voiceMessage = DB::table('restoline_voice_messages')
            ->where('message_id', $message)
            ->first();

        abort_if(!$voiceMessage, 404);

        $expectedToken = hash_hmac(
            'sha256',
            $message.'|'.(string)$voiceMessage->recording_sid,
            (string)config('app.key'),
        );

        abort_unless(hash_equals($expectedToken, (string)$request->query('token', '')), 403);

        $path = (string)($voiceMessage->audio_local_path ?: '');

        abort_if($path === '' || !is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
