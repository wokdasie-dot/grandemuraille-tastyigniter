<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\ReservationReminder;

class ReservationReminders extends AdminController
{
    public array $implement = [ListController::class];

    public array $listConfig = [
        'list' => [
            'model' => ReservationReminder::class,
            'title' => 'Rappels réservation',
            'emptyMessage' => 'Aucun rappel envoyé pour le moment.',
            'defaultSort' => ['created_at', 'DESC'],
            'configFile' => 'reservationreminder',
        ],
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();
        AdminMenu::setContext('restoline-client-communications');
    }
}
