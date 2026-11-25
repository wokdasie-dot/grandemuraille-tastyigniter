<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Flame\Translation\Localization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicLocaleController
{
    public const array LOCALES = ['fr', 'en', 'nl'];

    public const string COOKIE = 'gm_locale';

    public static function switchUrl(string $code): string
    {
        $path = '/'.ltrim(request()->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        return url('/locale/'.$code).'?to='.rawurlencode($path);
    }

    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);

        /** @var Localization $localization */
        $localization = app('translator.localization');
        $localization->setSessionLocale($locale);
        app()->setLocale($locale);

        $to = $request->query('to');
        $target = url('/');
        if (is_string($to) && str_starts_with($to, '/') && !str_starts_with($to, '//') && !str_contains($to, '/locale/')) {
            $target = url($to);
        } else {
            $previous = url()->previous();
            if (!str_contains($previous, '/locale/')) {
                $target = $previous;
            }
        }

        return redirect()
            ->to($target)
            ->cookie(self::COOKIE, $locale, 60 * 24 * 400, '/', null, null, false, false, 'lax');
    }
}
