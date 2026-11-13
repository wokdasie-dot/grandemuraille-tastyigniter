<?php

namespace App\Services\Restoline;

final class PrivateEnvironment
{
    private static ?array $values = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $environmentValue = env($key);
        if (is_string($environmentValue) && $environmentValue !== '') {
            return $environmentValue;
        }

        $values = self::values();

        return $values[$key] ?? $default;
    }

    private static function values(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        self::$values = [];
        $path = env('RESTOLINE_PRIVATE_ENV');

        if (!is_string($path) || $path === '' || !is_readable($path)) {
            if ($path === null || $path === '') {
                try {
                    if (function_exists('app') && app()->bound('log') && app()->environment('production')) {
                        logger()->warning('RESTOLINE_PRIVATE_ENV is unset. No private environment file was loaded.');
                    }
                } catch (\Throwable) {
                }
            }

            return self::$values;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return self::$values;
    }
}
