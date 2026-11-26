<?php

declare(strict_types=1);

namespace App\Services\Restoline;

use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\Translate\Actions\TranslatableModel;
use Illuminate\Support\Facades\Schema;
use Restoline\Content\Support\PublicCopy;

/**
 * The public à la carte, read from Menus and Categories after the seed.
 */
final class AlacarteCatalog
{
    /**
     * @return array{categories: list<array{id: string, title: string, items: list<array{num: string, name: string, price: string, price_label: string}>}>, promo: string}
     */
    public static function page(): array
    {
        $fromDatabase = self::fromDatabase();
        if ($fromDatabase !== null) {
            return $fromDatabase;
        }

        return [
            'categories' => PublicCopy::carte(),
            'promo' => PublicCopy::cartePromo(),
        ];
    }

    /**
     * @return array{categories: list<array{id: string, title: string, items: list<array{num: string, name: string, price: string, price_label: string}>}>, promo: string}|null
     */
    private static function fromDatabase(): ?array
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('menus') || !Schema::hasColumn('menus', 'alacarte_number')) {
            return null;
        }

        $root = Category::query()->where('permalink_slug', AlacarteImporter::ROOT_SLUG)->first();
        if (!$root instanceof Category) {
            return null;
        }

        $locale = app()->getLocale();
        $categories = [];
        $sections = Category::query()
            ->where('parent_id', $root->getKey())
            ->where('status', true)
            ->orderBy('priority')
            ->orderBy('category_id')
            ->orderBy('permalink_slug')
            ->get();

        foreach ($sections as $section) {
            if (!$section instanceof Category) {
                continue;
            }
            $items = [];
            $menus = $section->menus()
                ->where('menu_status', true)
                ->orderBy('menu_priority')
                ->orderBy('menu_id')
                ->get();
            foreach ($menus as $menu) {
                if (!$menu instanceof Menu) {
                    continue;
                }
                $price = number_format((float)$menu->menu_price, 2, '.', '');
                $items[] = [
                    'num' => trim((string)($menu->alacarte_number ?? '')),
                    'name' => self::translated($menu, 'menu_name', $locale),
                    'price' => $price,
                    'price_label' => str_replace('.', ',', $price).' €',
                ];
            }
            if ($items === []) {
                continue;
            }
            $categories[] = [
                'id' => (string)$section->permalink_slug,
                'title' => self::translated($section, 'name', $locale),
                'items' => $items,
            ];
        }

        return [
            'categories' => $categories,
            'promo' => trim(self::translated($root, 'description', $locale)),
        ];
    }

    private static function translated(object $model, string $attribute, string $locale): string
    {
        $attributes = method_exists($model, 'getAttributes') ? $model->getAttributes() : [];
        $base = trim((string)($attributes[$attribute] ?? ''));
        if (!in_array($locale, ['en', 'nl'], true) || !method_exists($model, 'isClassExtendedWith')) {
            return $base;
        }
        if (!$model->isClassExtendedWith(TranslatableModel::class)) {
            return $base;
        }

        $translated = trim((string)$model->getAttributeTranslatedValue($attribute, $locale));

        return $translated !== '' ? $translated : $base;
    }
}
