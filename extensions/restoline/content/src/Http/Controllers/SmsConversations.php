<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\SmsConversation;
use Restoline\Content\Models\SmsReservationSetting;
use App\Services\Restoline\SmsReservationConversationService;

class SmsConversations extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => SmsConversation::class,
            'title' => 'Réservations par SMS',
            'emptyMessage' => 'Aucune conversation SMS.',
            'defaultSort' => ['last_message_at', 'DESC'],
            'configFile' => 'smsconversation',
        ],
    ];

    public array $formConfig = [
        'name' => 'Conversation SMS',
        'model' => SmsConversation::class,
        'preview' => [
            'title' => 'Détail conversation SMS',
            'back' => 'restoline/content/sms_conversations',
        ],
        'configFile' => 'smsconversation',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }

    public function onSendReply(): void
    {
        $conversation = SmsConversation::findOrFail((int)post('conversation_id'));
        $service = app(SmsReservationConversationService::class);
        $service->sendManualReply($conversation, (string)post('message'));
        flash()->success('SMS envoyé au client.')->now();
    }

    public function onToggleAutomation(): void
    {
        $setting = SmsReservationSetting::current();
        $setting->enabled = !$setting->enabled;
        $setting->save();
        flash()->success($setting->enabled ? 'Réservations SMS activées.' : 'Réservations SMS désactivées.')->now();
    }

    public function onTakeOver(): void
    {
        $conversation = SmsConversation::findOrFail((int)post('conversation_id'));
        $conversation->status = 'human_handling';
        $conversation->save();
        flash()->success('Conversation prise en charge par le restaurant.')->now();
    }

    public function onResumeAutomation(): void
    {
        $conversation = SmsConversation::findOrFail((int)post('conversation_id'));
        $conversation->status = 'open';
        $conversation->save();
        flash()->success('Automatisation reprise pour cette conversation.')->now();
    }
}
