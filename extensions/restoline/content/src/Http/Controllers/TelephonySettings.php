<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Restoline\Content\Models\TelephonySetting;

class TelephonySettings extends AdminController
{
    public array $implement = [
        FormController::class,
    ];

    public array $formConfig = [
        'name' => 'Répondeur téléphonique',
        'model' => TelephonySetting::class,
        'request' => 'Restoline\Content\Models\TelephonySetting',
        'create' => [
            'title' => 'Configuration répondeur téléphonique',
            'redirect' => 'restoline/content/telephony_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/telephony_settings/edit/{setting_id}',
        ],
        'edit' => [
            'title' => 'Configuration répondeur téléphonique',
            'redirect' => 'restoline/content/telephony_settings/edit/{setting_id}',
            'redirectClose' => 'restoline/content/telephony_settings/edit/{setting_id}',
        ],
        'configFile' => 'telephonysetting',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }

    public function index()
    {
        $record = TelephonySetting::current();

        return redirect(admin_url('restoline/content/telephony_settings/edit/'.$record->getKey()));
    }

    public function onRegenerateVoiceAudio()
    {
        $record = TelephonySetting::current();
        $result = $record->ensureVoiceAudioFiles(true);

        $message = sprintf(
            'Audios répondeur : %d régénéré(s), %d déjà prêt(s), %d échoué(s).',
            $result['generated'],
            $result['skipped'],
            $result['failed'],
        );

        if ($result['failed'] > 0) {
            flash()->error($message)->now();
        } else {
            flash()->success($message)->now();
        }
    }
}
