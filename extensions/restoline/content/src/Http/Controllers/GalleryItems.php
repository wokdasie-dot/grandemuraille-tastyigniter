<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\GalleryItem;

class GalleryItems extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => GalleryItem::class,
            'title' => 'restoline.content::default.admin.gallery.list_title',
            'emptyMessage' => 'restoline.content::default.admin.gallery.empty',
            'defaultSort' => ['sort_order', 'ASC'],
            'configFile' => 'galleryitem',
        ],
    ];

    public array $formConfig = [
        'name' => 'restoline.content::default.admin.gallery.form_name',
        'model' => GalleryItem::class,
        'create' => [
            'title' => 'restoline.content::default.admin.gallery.create_title',
            'redirect' => 'restoline/content/gallery_items/edit/{gallery_id}',
            'redirectClose' => 'restoline/content/gallery_items',
            'redirectNew' => 'restoline/content/gallery_items/create',
        ],
        'edit' => [
            'title' => 'restoline.content::default.admin.gallery.edit_title',
            'redirect' => 'restoline/content/gallery_items/edit/{gallery_id}',
            'redirectClose' => 'restoline/content/gallery_items',
            'redirectNew' => 'restoline/content/gallery_items/create',
        ],
        'preview' => [
            'title' => 'restoline.content::default.admin.gallery.preview_title',
            'back' => 'restoline/content/gallery_items',
        ],
        'delete' => [
            'redirect' => 'restoline/content/gallery_items',
        ],
        'configFile' => 'galleryitem',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Gallery';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-gallery');
    }
}
