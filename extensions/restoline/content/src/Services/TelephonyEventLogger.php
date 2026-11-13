<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TelephonyEventLogger
{
    public function record(string $channel, string $eventType, array $data = []): void
    {
        try {
            if (!Schema::hasTable('restoline_telephony_events')) {
                return;
            }

            $this->pruneOldEvents();

            $payload = $data['payload'] ?? [];
            if (!is_array($payload)) {
                $payload = ['value' => $payload];
            }

            foreach ($data as $key => $value) {
                if (!in_array($key, [
                    'call_sid',
                    'message_sid',
                    'from',
                    'to',
                    'digit',
                    'reservation_id',
                    'status',
                    'duration_seconds',
                    'payload',
                    'created_at',
                ], true)) {
                    $payload[$key] = $value;
                }
            }

            $now = now();
            DB::table('restoline_telephony_events')->insert([
                'channel' => $channel,
                'event_type' => $eventType,
                'call_sid' => $this->cleanString($data['call_sid'] ?? null, 80),
                'message_sid' => $this->cleanString($data['message_sid'] ?? null, 80),
                'from_hash' => $this->hashPhone($data['from'] ?? null),
                'to_hash' => $this->hashPhone($data['to'] ?? null),
                'menu_digit' => $this->cleanString($data['digit'] ?? null, 10),
                'reservation_id' => $data['reservation_id'] ?? null,
                'status' => $this->cleanString($data['status'] ?? null, 80),
                'duration_seconds' => $this->duration($data['duration_seconds'] ?? null),
                'payload' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $data['created_at'] ?? $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $exception) {
            Log::debug('Telephony event not recorded.', [
                'channel' => $channel,
                'event_type' => $eventType,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function pruneOldEvents(): void
    {
        $retentionDays = max(30, (int)env('RESTOLINE_TELEPHONY_STATS_RETENTION_DAYS', 30));
        $cacheKey = 'restoline:telephony-events:pruned:'.now()->format('Y-m-d');

        if (Cache::has($cacheKey)) {
            return;
        }

        DB::table('restoline_telephony_events')
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();

        Cache::put($cacheKey, true, now()->addDay());
    }

    private function hashPhone(mixed $phone): ?string
    {
        $phone = $this->cleanString($phone, 120);
        if ($phone === null || $phone === '') {
            return null;
        }

        $phone = preg_replace('/[\s.\/()-]+/', '', $phone) ?? $phone;

        return hash_hmac('sha256', $phone, (string)config('app.key', 'restoline'));
    }

    private function cleanString(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function duration(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int)$value);
    }
}
