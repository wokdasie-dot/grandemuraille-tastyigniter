<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\BuffetPricing;

class BuffetPricings extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => BuffetPricing::class,
            'title' => 'restoline.content::default.admin.buffet_pricing.list_title',
            'emptyMessage' => 'restoline.content::default.admin.buffet_pricing.empty',
            'defaultSort' => ['sort_order', 'ASC'],
            'configFile' => 'buffetpricing',
        ],
    ];

    public array $formConfig = [
        'name' => 'restoline.content::default.admin.buffet_pricing.form_name',
        'model' => BuffetPricing::class,
        'create' => [
            'title' => 'restoline.content::default.admin.buffet_pricing.create_title',
            'redirect' => 'restoline/content/buffet_pricings/edit/{pricing_id}',
            'redirectClose' => 'restoline/content/buffet_pricings',
            'redirectNew' => 'restoline/content/buffet_pricings/create',
        ],
        'edit' => [
            'title' => 'restoline.content::default.admin.buffet_pricing.edit_title',
            'redirect' => 'restoline/content/buffet_pricings/edit/{pricing_id}',
            'redirectClose' => 'restoline/content/buffet_pricings',
            'redirectNew' => 'restoline/content/buffet_pricings/create',
        ],
        'preview' => [
            'title' => 'restoline.content::default.admin.buffet_pricing.preview_title',
            'back' => 'restoline/content/buffet_pricings',
        ],
        'delete' => [
            'redirect' => 'restoline/content/buffet_pricings',
        ],
        'configFile' => 'buffetpricing',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.BuffetPricing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-buffet-pricing');
    }
}
