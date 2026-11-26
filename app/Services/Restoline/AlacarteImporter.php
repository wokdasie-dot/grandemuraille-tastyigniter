<?php

declare(strict_types=1);

namespace App\Services\Restoline;

use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\Local\Models\Location;
use Igniter\System\Models\Settings;
use Igniter\Translate\Actions\TranslatableModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies data/alacarte.json into Menus and Categories once.
 * A later maintenance run leaves every existing record alone, so staff edits stay.
 */
final class AlacarteImporter
{
    public const string FLAG = 'alacarte_seeded';

    public const string ROOT_SLUG = 'alacarte';

    public function import(): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('menus') || !Schema::hasTable('menu_categories')) {
            return;
        }
        if (!Schema::hasColumn('menus', 'alacarte_number') || !Schema::hasColumn('menus', 'alacarte_seed_key')) {
            return;
        }
        if ((string)Settings::get(self::FLAG, '') === '1') {
            return;
        }

        $pack = $this->pack();
        if ($pack === []) {
            return;
        }

        $root = $this->ensureCategory(
            self::ROOT_SLUG,
            'À la carte',
            (string)($pack['promo']['fr'] ?? ''),
            null,
            0,
            [
                'en' => ['name' => 'À la carte', 'description' => (string)($pack['promo']['en'] ?? '')],
                'nl' => ['name' => 'À-la-cartekaart', 'description' => (string)($pack['promo']['nl'] ?? '')],
            ],
        );

        $locationId = $this->locationId();
        $order = 0;
        foreach ($pack['categories'] ?? [] as $category) {
            if (!is_array($category)) {
                continue;
            }
            $sectionId = (string)($category['id'] ?? '');
            $titles = is_array($category['title'] ?? null) ? $category['title'] : [];
            $frenchTitle = trim((string)($titles['fr'] ?? ''));
            if ($sectionId === '' || $frenchTitle === '') {
                continue;
            }

            $section = $this->ensureCategory(
                self::ROOT_SLUG.'-'.$sectionId,
                $frenchTitle,
                '',
                (int)$root->getKey(),
                $order,
                [
                    'en' => ['name' => (string)($titles['en'] ?? $frenchTitle)],
                    'nl' => ['name' => (string)($titles['nl'] ?? $frenchTitle)],
                ],
            );
            $order++;

            $dishOrder = 0;
            foreach ($category['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $number = trim((string)($item['num'] ?? ''));
                $names = is_array($item['name'] ?? null) ? $item['name'] : [];
                $frenchName = trim((string)($names['fr'] ?? ''));
                if ($frenchName === '') {
                    continue;
                }
                $seedKey = self::ROOT_SLUG.':'.$sectionId.':'.($number !== '' ? $number : 'item-'.$dishOrder);
                $this->ensureDish(
                    $seedKey,
                    $frenchName,
                    (string)($names['en'] ?? ''),
                    (string)($names['nl'] ?? ''),
                    (float)str_replace(',', '.', (string)($item['price'] ?? '0')),
                    $number,
                    $dishOrder,
                    (int)$section->getKey(),
                    $locationId,
                );
                $dishOrder++;
            }
        }

        if (method_exists(Category::class, 'fixTree')) {
            Category::fixTree();
        }

        Settings::set(self::FLAG, '1');
    }

    /**
     * @return array<string, mixed>
     */
    private function pack(): array
    {
        $path = base_path('data/alacarte.json');
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, array<string, string>> $translations
     */
    private function ensureCategory(
        string $slug,
        string $frenchName,
        string $frenchDescription,
        ?int $parentId,
        int $priority,
        array $translations,
    ): Category {
        $existingId = DB::table('categories')->where('permalink_slug', $slug)->value('category_id');
        if ($existingId) {
            $found = Category::query()->find((int)$existingId);
            if ($found instanceof Category) {
                return $found;
            }
        }

        $category = new Category();
        $category->name = $frenchName;
        $category->description = $frenchDescription;
        $category->parent_id = $parentId;
        $category->priority = $priority;
        $category->status = true;
        $category->permalink_slug = $slug;
        $category->save();

        DB::table('categories')->where('category_id', $category->getKey())->update([
            'permalink_slug' => $slug,
        ]);
        $this->translate($category, $translations);

        return $category;
    }

    private function ensureDish(
        string $seedKey,
        string $frenchName,
        string $englishName,
        string $dutchName,
        float $price,
        string $number,
        int $priority,
        int $categoryId,
        ?int $locationId,
    ): void {
        if (DB::table('menus')->where('alacarte_seed_key', $seedKey)->exists()) {
            return;
        }

        $menu = new Menu();
        $menu->menu_name = $frenchName;
        $menu->menu_description = '';
        $menu->menu_price = $price;
        $menu->minimum_qty = 1;
        $menu->menu_status = true;
        $menu->menu_priority = $priority;
        $menu->alacarte_number = $number;
        $menu->alacarte_seed_key = $seedKey;
        $menu->save();
        $menu->categories()->sync([$categoryId]);

        $translations = [];
        if ($englishName !== '') {
            $translations['en'] = ['menu_name' => $englishName];
        }
        if ($dutchName !== '') {
            $translations['nl'] = ['menu_name' => $dutchName];
        }
        $this->translate($menu, $translations);

        if ($locationId && Schema::hasTable('locationables')) {
            $attached = DB::table('locationables')
                ->where('location_id', $locationId)
                ->where('locationable_id', $menu->getKey())
                ->where('locationable_type', 'menus')
                ->exists();
            if (!$attached) {
                DB::table('locationables')->insert([
                    'location_id' => $locationId,
                    'locationable_id' => $menu->getKey(),
                    'locationable_type' => 'menus',
                    'options' => null,
                ]);
            }
        }
    }

    /**
     * @param array<string, array<string, string>> $byLocale
     */
    private function translate(object $model, array $byLocale): void
    {
        if ($byLocale === [] || !method_exists($model, 'isClassExtendedWith')) {
            return;
        }
        if (!$model->isClassExtendedWith(TranslatableModel::class)) {
            return;
        }

        foreach ($byLocale as $locale => $attributes) {
            foreach ($attributes as $key => $value) {
                if ($value === '') {
                    continue;
                }
                $model->setAttributeTranslatedValue($key, $value, $locale);
            }
        }
        $model->save();
    }

    private function locationId(): ?int
    {
        if (!class_exists(Location::class)) {
            return null;
        }

        $location = Location::getDefault();

        return $location ? (int)$location->getKey() : null;
    }
}
