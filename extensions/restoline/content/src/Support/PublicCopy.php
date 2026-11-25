<?php

declare(strict_types=1);

namespace Restoline\Content\Support;

/**
 * Public design v5 strings. Keys such as "maison.title" and "maison.title.em"
 * cannot be nested in a Laravel language file, so the validated FR/EN/NL copy
 * is read from data/v5-i18n.json for the session locale.
 */
final class PublicCopy
{
    public static function line(string $key): string
    {
        $value = self::pack()['ui'][$key] ?? '';

        return is_string($value) ? $value : '';
    }

    public static function html(string $key): string
    {
        return nl2br(e(self::line($key)), false);
    }

    /**
     * Printed-carte lines for a French menu name. Names stay in French, as on
     * the validated carte; the lines follow the session locale.
     *
     * @return list<string>
     */
    public static function menuLines(string $frenchName): array
    {
        $lines = self::pack()['menus'][$frenchName] ?? [];
        if (!is_array($lines)) {
            return [];
        }

        $clean = [];
        foreach ($lines as $line) {
            if (!is_string($line)) {
                continue;
            }
            $line = trim($line);
            if ($line !== '') {
                $clean[] = $line;
            }
        }

        return $clean;
    }

    /**
     * Menu heading for the session locale. The lookup key stays the French
     * name stored on the menu, so English and Dutch do not show that French title.
     */
    public static function menuTitle(string $frenchName): string
    {
        $titles = self::pack()['menu_titles'] ?? [];
        $title = is_array($titles) ? ($titles[$frenchName] ?? '') : '';

        return is_string($title) && $title !== '' ? $title : $frenchName;
    }

    /**
     * Strings the front-end script needs (motion toggle, mobile menu).
     *
     * @return array<string, string>
     */
    public static function script(): array
    {
        $out = [];
        foreach (self::pack()['ui'] ?? [] as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'motion.') || str_starts_with($key, 'menu.')) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function pack(): array
    {
        static $cache = [];

        $locale = app()->getLocale();
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $path = base_path('data/v5-i18n.json');
        $all = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
        if (!is_array($all)) {
            $all = [];
        }

        $pack = $all[$locale] ?? $all['fr'] ?? [];

        return $cache[$locale] = is_array($pack) ? $pack : [];
    }

    /**
     * À la carte copied from the official www carte (FR names and prices
     * unchanged). Titles and dish names follow the session locale.
     *
     * @return list<array{id:string,title:string,items:list<array{num:string,name:string,price:string,price_label:string}>}>
     */
    public static function carte(): array
    {
        $locale = app()->getLocale();
        if (!in_array($locale, ['fr', 'en', 'nl'], true)) {
            $locale = 'fr';
        }

        $categories = [];
        foreach (self::cartePack()['categories'] ?? [] as $category) {
            if (!is_array($category)) {
                continue;
            }
            $id = (string)($category['id'] ?? '');
            $title = $category['title'][$locale] ?? ($locale === 'fr' ? ($category['title']['fr'] ?? '') : '');
            $items = [];
            foreach ($category['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $price = (string)($item['price'] ?? '');
                $items[] = [
                    'num' => (string)($item['num'] ?? ''),
                    'name' => (string)($item['name'][$locale] ?? ($locale === 'fr' ? ($item['name']['fr'] ?? '') : '')),
                    'price' => $price,
                    'price_label' => self::price($price),
                ];
            }
            $categories[] = [
                'id' => $id,
                'title' => (string)$title,
                'items' => $items,
            ];
        }

        return $categories;
    }

    public static function cartePromo(): string
    {
        $locale = app()->getLocale();
        $promo = self::cartePack()['promo'] ?? [];
        if (!is_array($promo)) {
            return '';
        }

        return (string)($promo[$locale] ?? $promo['fr'] ?? '');
    }

    public static function price(string $amount): string
    {
        if ($amount === '') {
            return '';
        }

        return str_replace('.', ',', $amount).' €';
    }

    /**
     * @return array<string, mixed>
     */
    private static function cartePack(): array
    {
        static $pack = null;
        if (is_array($pack)) {
            return $pack;
        }

        $path = base_path('data/alacarte.json');
        $all = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];

        return $pack = is_array($all) ? $all : [];
    }

}
