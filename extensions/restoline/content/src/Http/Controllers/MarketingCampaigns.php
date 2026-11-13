<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\MarketingCampaign;

class MarketingCampaigns extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => MarketingCampaign::class,
            'title' => 'Promotions marketing',
            'emptyMessage' => 'Aucune promotion.',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'marketingcampaign',
        ],
    ];

    public array $formConfig = [
        'name' => 'Promotion',
        'model' => MarketingCampaign::class,
        'create' => [
            'title' => 'Créer une promotion',
            'redirect' => 'restoline/content/marketing_campaigns/edit/{campaign_id}',
            'redirectClose' => 'restoline/content/marketing_campaigns',
        ],
        'edit' => [
            'title' => 'Modifier une promotion',
            'redirect' => 'restoline/content/marketing_campaigns/edit/{campaign_id}',
            'redirectClose' => 'restoline/content/marketing_campaigns',
        ],
        'delete' => [
            'redirect' => 'restoline/content/marketing_campaigns',
        ],
        'configFile' => 'marketingcampaign',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }
}
