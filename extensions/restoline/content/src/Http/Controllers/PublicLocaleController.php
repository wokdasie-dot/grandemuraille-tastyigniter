<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Flame\Translation\Localization;
use Illuminate\Http\RedirectResponse;

class PublicLocaleController
{
    public const array LOCALES = ['fr', 'en', 'nl'];

    public function update(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);

        /** @var Localization $localization */
        $localization = app('translator.localization');
        $localization->setSessionLocale($locale);
        $localization->setLocale($locale);

        $previous = url()->previous();
        $target = str_contains($previous, '/locale/') ? url('/') : $previous;

        return redirect()->to($target);
    }
}
