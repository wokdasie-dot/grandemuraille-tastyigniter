<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Middleware;

use Closure;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Flame\Translation\Localization;
use Illuminate\Http\Request;
use Restoline\Content\Http\Controllers\PublicLocaleController;

class RestrictPublicLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (!Igniter::runningInAdmin() && !in_array(app()->getLocale(), PublicLocaleController::LOCALES, true)) {
            /** @var Localization $localization */
            $localization = app('translator.localization');
            $localization->setLocale('fr');
        }

        return $next($request);
    }
}
