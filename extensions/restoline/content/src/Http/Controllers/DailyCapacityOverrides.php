<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\DailyCapacityOverride;

class DailyCapacityOverrides extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => DailyCapacityOverride::class,
            'title' => 'restoline.content::default.admin.daily_capacity.list_title',
            'emptyMessage' => 'restoline.content::default.admin.daily_capacity.empty',
            'defaultSort' => ['date', 'DESC'],
            'configFile' => 'dailycapacityoverride',
        ],
    ];

    public array $formConfig = [
        'name' => 'restoline.content::default.admin.daily_capacity.form_name',
        'model' => DailyCapacityOverride::class,
        'create' => [
            'title' => 'restoline.content::default.admin.daily_capacity.create_title',
            'redirect' => 'restoline/content/daily_capacity_overrides/edit/{override_id}',
            'redirectClose' => 'restoline/content/daily_capacity_overrides',
            'redirectNew' => 'restoline/content/daily_capacity_overrides/create',
        ],
        'edit' => [
            'title' => 'restoline.content::default.admin.daily_capacity.edit_title',
            'redirect' => 'restoline/content/daily_capacity_overrides/edit/{override_id}',
            'redirectClose' => 'restoline/content/daily_capacity_overrides',
            'redirectNew' => 'restoline/content/daily_capacity_overrides/create',
        ],
        'preview' => [
            'title' => 'restoline.content::default.admin.daily_capacity.preview_title',
            'back' => 'restoline/content/daily_capacity_overrides',
        ],
        'delete' => [
            'redirect' => 'restoline/content/daily_capacity_overrides',
        ],
        'configFile' => 'dailycapacityoverride',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.DailyCapacity';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-daily-capacity');
    }

    public function index(): void
    {
        $this->asExtension(ListController::class)->index();

        $today = now()->toDateString();
        $this->vars['todayOverride'] = DailyCapacityOverride::query()
            ->whereDate('date', $today)
            ->first();
    }

    public function onToggleLunchToday()
    {
        $override = DailyCapacityOverride::toggleLunchForToday();

        $message = $override->lunch_closed
            ? lang('restoline.content::default.admin.daily_capacity.lunch_message_closed')
            : lang('restoline.content::default.admin.daily_capacity.lunch_message_open');

        flash()->success($message)->now();

        return $this->redirect('restoline/content/daily_capacity_overrides');
    }

    public function onToggleDinnerToday()
    {
        $override = DailyCapacityOverride::toggleDinnerForToday();

        $message = $override->dinner_closed
            ? lang('restoline.content::default.admin.daily_capacity.dinner_message_closed')
            : lang('restoline.content::default.admin.daily_capacity.dinner_message_open');

        flash()->success($message)->now();

        return $this->redirect('restoline/content/daily_capacity_overrides');
    }
}
