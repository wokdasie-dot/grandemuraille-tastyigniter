<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\HomePopup;

class HomePopups extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => HomePopup::class,
            'title' => 'Pop-up accueil',
            'emptyMessage' => 'Aucune pop-up configurée.',
            'defaultSort' => ['priority', 'DESC'],
            'configFile' => 'homepopup',
        ],
    ];

    public array $formConfig = [
        'name' => 'Pop-up accueil',
        'model' => HomePopup::class,
        'create' => [
            'title' => 'Créer une pop-up',
            'redirect' => 'restoline/content/home_popups/edit/{popup_id}',
            'redirectClose' => 'restoline/content/home_popups',
            'redirectNew' => 'restoline/content/home_popups/create',
        ],
        'edit' => [
            'title' => 'Modifier la pop-up',
            'redirect' => 'restoline/content/home_popups/edit/{popup_id}',
            'redirectClose' => 'restoline/content/home_popups',
            'redirectNew' => 'restoline/content/home_popups/create',
        ],
        'preview' => [
            'title' => 'Aperçu de la pop-up',
            'back' => 'restoline/content/home_popups',
        ],
        'delete' => [
            'redirect' => 'restoline/content/home_popups',
        ],
        'configFile' => 'homepopup',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-home-popups');
    }
}
