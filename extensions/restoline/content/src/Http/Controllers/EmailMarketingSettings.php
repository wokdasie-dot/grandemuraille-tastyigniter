<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\EmailMarketingSetting;

class EmailMarketingSettings extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => EmailMarketingSetting::class,
            'title' => 'Configuration emailing marketing',
            'emptyMessage' => 'Aucune configuration emailing.',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'emailmarketingsetting',
        ],
    ];

    public array $formConfig = [
        'name' => 'Configuration emailing',
        'model' => EmailMarketingSetting::class,
        'create' => [
            'title' => 'Creer une configuration emailing',
            'redirect' => 'restoline/content/email_marketing_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/email_marketing_settings',
        ],
        'edit' => [
            'title' => 'Modifier une configuration emailing',
            'redirect' => 'restoline/content/email_marketing_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/email_marketing_settings',
        ],
        'delete' => [
            'redirect' => 'restoline/content/email_marketing_settings',
        ],
        'configFile' => 'emailmarketingsetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }
}
