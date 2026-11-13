<?php

declare(strict_types=1);

use Igniter\System\Models\Language;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $language = Language::query()->firstOrNew(['code' => 'zh_CN']);

        if (!$language->exists) {
            $language->forceFill([
                'name' => '中文（简体）',
                'image' => 'cn.png',
                'idiom' => 'Chinese Simplified',
                'can_delete' => false,
                'is_default' => false,
            ]);
        }

        $language->status = true;
        $language->save();

        Language::applySupportedLanguages();
        Language::clearInternalCache();
    }

    public function down(): void
    {
        $language = Language::query()->where('code', 'zh_CN')->first();

        if ($language && !$language->isDefault()) {
            $language->delete();
            Language::applySupportedLanguages();
            Language::clearInternalCache();
        }
    }
};
