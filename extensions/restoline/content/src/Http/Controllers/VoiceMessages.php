<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\VoiceMessage;

class VoiceMessages extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => VoiceMessage::class,
            'title' => 'Messages vocaux',
            'emptyMessage' => 'Aucun message vocal enregistré.',
            'defaultSort' => ['created_at', 'DESC'],
            'configFile' => 'voicemessage',
        ],
    ];

    public array $formConfig = [
        'name' => 'Message vocal',
        'model' => VoiceMessage::class,
        'preview' => [
            'title' => 'Détail du message vocal',
            'back' => 'restoline/content/voice_messages',
        ],
        'configFile' => 'voicemessage',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }
}
