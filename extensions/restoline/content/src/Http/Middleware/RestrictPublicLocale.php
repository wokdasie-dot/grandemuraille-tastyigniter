<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Middleware;

use Closure;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Flame\Translation\Localization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Restoline\Content\Http\Controllers\PublicLocaleController;

class RestrictPublicLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (!Igniter::runningInAdmin() && !$request->is('locale/*')) {
            $this->applyPublicLocale($request);
        }

        return $next($request);
    }

    private function applyPublicLocale(Request $request): void
    {
        /** @var Localization $localization */
        $localization = app('translator.localization');
        $chosen = $localization->getSessionLocale() ?: $request->cookie(PublicLocaleController::COOKIE);
        $locale = in_array($chosen, PublicLocaleController::LOCALES, true) ? $chosen : 'fr';

        app()->setLocale($locale);
        \Carbon\Carbon::setLocale($locale);
        $localization->setLocale($locale);
        if ($localization->getSessionLocale() !== $locale) {
            $localization->setSessionLocale($locale);
        }
        if ($request->cookie(PublicLocaleController::COOKIE) !== $locale) {
            Cookie::queue(cookie(
                PublicLocaleController::COOKIE,
                $locale,
                60 * 24 * 400,
                '/',
                null,
                null,
                false,
                false,
                'lax',
            ));
        }
    }
}
