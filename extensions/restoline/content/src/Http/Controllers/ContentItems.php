<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\ContentItem;

class ContentItems extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => ContentItem::class,
            'title' => 'restoline.content::default.admin.content_items.list_title',
            'emptyMessage' => 'restoline.content::default.admin.content_items.empty',
            'defaultSort' => ['published_at', 'DESC'],
            'configFile' => 'contentitem',
        ],
    ];

    public array $formConfig = [
        'name' => 'restoline.content::default.admin.content_items.form_name',
        'model' => ContentItem::class,
        'create' => [
            'title' => 'restoline.content::default.admin.content_items.create_title',
            'redirect' => 'restoline/content/content_items/edit/{content_id}',
            'redirectClose' => 'restoline/content/content_items',
            'redirectNew' => 'restoline/content/content_items/create',
        ],
        'edit' => [
            'title' => 'restoline.content::default.admin.content_items.edit_title',
            'redirect' => 'restoline/content/content_items/edit/{content_id}',
            'redirectClose' => 'restoline/content/content_items',
            'redirectNew' => 'restoline/content/content_items/create',
        ],
        'preview' => [
            'title' => 'restoline.content::default.admin.content_items.preview_title',
            'back' => 'restoline/content/content_items',
        ],
        'delete' => [
            'redirect' => 'restoline/content/content_items',
        ],
        'configFile' => 'contentitem',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-content');
    }
}
