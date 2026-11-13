<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\RestaurantNotificationSetting;

class RestaurantNotificationSettings extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => RestaurantNotificationSetting::class,
            'title' => 'Notifications restaurateur',
            'emptyMessage' => 'Aucune configuration de notification.',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'restaurantnotificationsetting',
        ],
    ];

    public array $formConfig = [
        'name' => 'Notification restaurateur',
        'model' => RestaurantNotificationSetting::class,
        'create' => [
            'title' => 'Créer une configuration de notification',
            'redirect' => 'restoline/content/restaurant_notification_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/restaurant_notification_settings',
        ],
        'edit' => [
            'title' => 'Modifier une configuration de notification',
            'redirect' => 'restoline/content/restaurant_notification_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/restaurant_notification_settings',
        ],
        'delete' => [
            'redirect' => 'restoline/content/restaurant_notification_settings',
        ],
        'configFile' => 'restaurantnotificationsetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }
}
