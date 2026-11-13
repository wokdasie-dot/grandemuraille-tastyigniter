<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\ScheduleException;

class ScheduleExceptions extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => ScheduleException::class,
            'title' => 'restoline.content::default.admin.schedule_exceptions.list_title',
            'emptyMessage' => 'restoline.content::default.admin.schedule_exceptions.empty',
            'defaultSort' => ['start_date', 'DESC'],
            'configFile' => 'scheduleexception',
        ],
    ];

    public array $formConfig = [
        'name' => 'restoline.content::default.admin.schedule_exceptions.form_name',
        'model' => ScheduleException::class,
        'create' => [
            'title' => 'restoline.content::default.admin.schedule_exceptions.create_title',
            'redirect' => 'restoline/content/schedule_exceptions/edit/{exception_id}',
            'redirectClose' => 'restoline/content/schedule_exceptions',
            'redirectNew' => 'restoline/content/schedule_exceptions/create',
        ],
        'edit' => [
            'title' => 'restoline.content::default.admin.schedule_exceptions.edit_title',
            'redirect' => 'restoline/content/schedule_exceptions/edit/{exception_id}',
            'redirectClose' => 'restoline/content/schedule_exceptions',
            'redirectNew' => 'restoline/content/schedule_exceptions/create',
        ],
        'preview' => [
            'title' => 'restoline.content::default.admin.schedule_exceptions.preview_title',
            'back' => 'restoline/content/schedule_exceptions',
        ],
        'delete' => [
            'redirect' => 'restoline/content/schedule_exceptions',
        ],
        'configFile' => 'scheduleexception',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-schedule-exceptions');
    }
}
