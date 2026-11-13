<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key') ?: env('TURNSTILE_SITE_KEY');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    public function isEnabled(): bool
    {
        return $this->siteKey() !== null && $this->secretKey() !== null;
    }

    public function verifyRequest(Request $request): bool
    {
        return $this->verifyToken(
            (string)$request->input('cf-turnstile-response', ''),
            (string)$request->ip(),
        );
    }

    public function verifyToken(?string $token, ?string $ip = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $token = trim((string)$token);
        if ($token === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $this->secretKey(),
                'response' => $token,
                'remoteip' => $ip,
            ]);

        if (!$response->ok()) {
            return false;
        }

        return (bool)($response->json('success') ?? false);
    }

    private function secretKey(): ?string
    {
        $key = config('services.turnstile.secret_key') ?: env('TURNSTILE_SECRET_KEY');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }
}
