<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Attach\HasMedia;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\System\Models\Concerns\Switchable;

class ContentItem extends Model
{
    use HasMedia;
    use Switchable;
    use Validation;

    protected $table = 'restoline_content_items';

    protected $primaryKey = 'content_id';

    protected $guarded = [];

    public $timestamps = true;

    public $mediable = [
        'image' => ['multiple' => false],
    ];

    protected $casts = [
        'status' => 'boolean',
        'published_at' => 'datetime',
    ];

    public $rules = [
        ['title', 'restoline.content::default.admin.common.title', 'required|string|min:2|max:255'],
        ['type', 'restoline.content::default.admin.common.type', 'required|in:home,news,holiday,takeaway'],
        ['content', 'restoline.content::default.admin.content_items.field_content', 'required|string'],
        ['status', 'restoline.content::default.admin.common.published', 'required|boolean'],
    ];

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'home' => lang('restoline.content::default.admin.content_items.type_home'),
            'holiday' => lang('restoline.content::default.admin.content_items.type_holiday'),
            'takeaway' => lang('restoline.content::default.admin.content_items.type_takeaway'),
            default => lang('restoline.content::default.admin.content_items.type_news'),
        };
    }

    public function getTypeOptions(): array
    {
        return [
            'home' => lang('restoline.content::default.admin.content_items.type_home'),
            'news' => lang('restoline.content::default.admin.content_items.type_news'),
            'holiday' => lang('restoline.content::default.admin.content_items.type_holiday'),
            'takeaway' => lang('restoline.content::default.admin.content_items.type_takeaway'),
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return resolve(MediaLibrary::class)->getMediaUrl($this->image_path);
        }

        if ($this->image?->path) {
            return $this->image->path;
        }

        return $this->firstContentImageUrl();
    }

    private function firstContentImageUrl(): ?string
    {
        if (!$this->content || !preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)$this->content, $matches)) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', true)
            ->where(function($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
