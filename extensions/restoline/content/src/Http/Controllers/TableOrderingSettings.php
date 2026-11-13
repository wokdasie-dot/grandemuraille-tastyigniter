<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Restoline\Content\Models\TableOrderingSetting;

class TableOrderingSettings extends AdminController
{
    public array $implement = [
        FormController::class,
    ];

    public array $formConfig = [
        'name' => 'Configuration commande à table',
        'model' => TableOrderingSetting::class,
        'create' => [
            'title' => 'Configuration commande à table',
            'redirect' => 'restoline/content/table_ordering_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/table_access_codes',
        ],
        'edit' => [
            'title' => 'Configuration commande à table',
            'redirect' => 'restoline/content/table_ordering_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/table_access_codes',
        ],
        'configFile' => 'tableorderingsetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.TableOrdering';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-table-ordering');
    }

    public function index()
    {
        $record = TableOrderingSetting::current();

        return redirect(admin_url('restoline/content/table_ordering_settings/edit/'.$record->getKey()));
    }
}
