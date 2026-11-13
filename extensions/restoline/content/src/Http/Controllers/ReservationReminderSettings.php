<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Restoline\Content\Models\ReservationReminderSetting;

class ReservationReminderSettings extends AdminController
{
    public array $implement = [FormController::class];

    public array $formConfig = [
        'name' => 'Réglages des rappels',
        'model' => ReservationReminderSetting::class,
        'edit' => [
            'title' => 'Réglages des rappels',
            'redirect' => 'restoline/content/reservation_reminder_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/reservation_reminders',
        ],
        'configFile' => 'reservationremindersetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();
        AdminMenu::setContext('restoline-client-communications');
    }

    public function index()
    {
        $record = ReservationReminderSetting::current();

        return redirect(admin_url('restoline/content/reservation_reminder_settings/edit/'.$record->getKey()));
    }
}
