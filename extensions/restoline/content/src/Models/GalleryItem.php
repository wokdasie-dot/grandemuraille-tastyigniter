<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\System\Models\Concerns\Switchable;

class GalleryItem extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_gallery_items';

    protected $primaryKey = 'gallery_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public $rules = [
        ['image_path', 'restoline.content::default.admin.gallery.field_image', 'required|string|max:255'],
        ['title', 'restoline.content::default.admin.gallery.field_title', 'nullable|string|max:255'],
        ['caption', 'restoline.content::default.admin.gallery.field_caption', 'nullable|string|max:255'],
        ['sort_order', 'restoline.content::default.admin.common.order', 'integer'],
        ['status', 'restoline.content::default.admin.common.active', 'boolean'],
    ];

    public static function listForDisplay(int $limit = 12)
    {
        return self::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('gallery_id')
            ->limit($limit)
            ->get();
    }

    public function getImageUrlAttribute(): string
    {
        $path = trim((string)$this->image_path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//')) {
            return $path;
        }

        if (str_starts_with($path, 'media/')) {
            return resolve(MediaLibrary::class)->getMediaUrl($path);
        }

        return asset($path);
    }
}
