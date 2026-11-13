<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Restoline\Content\Models\GroupBenefitSetting;

class GroupBenefitSettings extends AdminController
{
    public array $implement = [
        FormController::class,
    ];

    public array $formConfig = [
        'name' => 'Avantage groupes',
        'model' => GroupBenefitSetting::class,
        'create' => [
            'title' => 'Avantage groupes',
            'redirect' => 'restoline/content/group_benefit_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/group_benefit_settings/edit/{setting_id}',
        ],
        'edit' => [
            'title' => 'Avantage groupes',
            'redirect' => 'restoline/content/group_benefit_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/group_benefit_settings/edit/{setting_id}',
        ],
        'configFile' => 'groupbenefitsetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }

    public function index()
    {
        $record = GroupBenefitSetting::current();

        return redirect(admin_url('restoline/content/group_benefit_settings/edit/'.$record->getKey()));
    }
}
