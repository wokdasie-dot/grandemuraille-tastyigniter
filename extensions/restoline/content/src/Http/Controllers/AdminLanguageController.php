<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\System\Models\Language;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLanguageController
{
    private const ALLOWED_LOCALES = ['fr', 'en', 'zh_CN'];

    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::ALLOWED_LOCALES, true), 404);

        $user = AdminAuth::user();
        abort_unless($user, 403);

        $language = Language::query()
            ->where('code', $locale)
            ->where('status', true)
            ->firstOrFail();

        $user->forceFill(['language_id' => $language->getKey()])->save();
        $user->unsetRelation('language');

        return redirect()->to(admin_url($this->returnPath($request)));
    }

    private function returnPath(Request $request): string
    {
        $path = trim((string)$request->input('return_path', 'dashboard'), '/');

        return $path !== ''
            && !str_contains($path, '..')
            && preg_match('#^[A-Za-z0-9_\-/]+$#', $path)
            ? $path
            : 'dashboard';
    }
}
