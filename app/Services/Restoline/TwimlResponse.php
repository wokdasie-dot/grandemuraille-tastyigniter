<?php

namespace App\Services\Restoline;

use Illuminate\Http\Response;

final class TwimlResponse
{
    public static function gather(
        string $message,
        string $action,
        string $input = 'dtmf',
        ?int $numDigits = null,
        string $fallback = 'Je n ai pas compris. Reessayons.',
    ): Response {
        $attributes = [
            'input' => $input,
            'action' => $action,
            'method' => 'POST',
            'timeout' => '6',
        ];

        if ($numDigits !== null) {
            $attributes['numDigits'] = (string)$numDigits;
        }
        if (str_contains($input, 'speech')) {
            $attributes['language'] = 'fr-FR';
            $attributes['speechTimeout'] = '2';
            $attributes['speechModel'] = 'googlev2_telephony_short';
            $attributes['hints'] = 'nom, nom de famille, aujourd hui, demain, apres demain, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, lundi prochain, mardi prochain, mercredi prochain, jeudi prochain, vendredi prochain, samedi prochain, dimanche prochain';
        }

        $xml = '<Response><Gather'.self::attributes($attributes).'>'
            .self::voice($message)
            .'</Gather>'
            .self::voice($fallback)
            .'<Redirect method="POST">'.self::escape($action).'</Redirect></Response>';

        return self::response($xml);
    }

    public static function gatherSpeechAfterBeep(
        string $message,
        string $action,
        string $fallback = 'Je n ai pas compris. Reessayons.',
    ): Response {
        $attributes = [
            'input' => 'speech',
            'action' => $action,
            'method' => 'POST',
            'timeout' => '5',
            'language' => 'fr-FR',
            'speechTimeout' => '2',
            'speechModel' => 'googlev2_telephony_short',
            'hints' => 'nom, nom de famille, aujourd hui, demain, apres demain, lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche, lundi prochain, mardi prochain, mercredi prochain, jeudi prochain, vendredi prochain, samedi prochain, dimanche prochain',
        ];

        $xml = '<Response><Gather'.self::attributes($attributes).'>'
            .self::voice($message)
            .'<Play>'.self::escape(url('/audio/restoline-beep.wav')).'</Play>'
            .'</Gather>'
            .self::voice($fallback)
            .'<Redirect method="POST">'.self::escape($action).'</Redirect></Response>';

        return self::response($xml);
    }

    public static function say(string $message): string
    {
        return self::voice($message);
    }

    public static function finish(string $message): Response
    {
        return self::response('<Response>'.self::voice($message).'<Hangup/></Response>');
    }

    private static function voice(string $message): string
    {
        $audioUrl = self::audioUrl($message);
        if ($audioUrl !== null) {
            return '<Play>'.self::escape($audioUrl).'</Play>';
        }

        return '<Say language="fr-FR" voice="Polly.Lea-Generative">'.self::escape($message).'</Say>';
    }

    private static function audioUrl(string $message): ?string
    {
        $filename = 'voice-'.sha1(trim($message)).'.mp3';
        $relativePath = 'audio/voice-charline/'.$filename;
        $publicPath = public_path($relativePath);

        if (!is_file($publicPath)) {
            return null;
        }

        return url('/'.$relativePath);
    }

    private static function response(string $xml): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    private static function attributes(array $attributes): string
    {
        $result = '';
        foreach ($attributes as $key => $value) {
            $result .= ' '.$key.'="'.self::escape($value).'"';
        }

        return $result;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
