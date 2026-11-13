<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Restoline\Content\Models\GroupBenefitSetting;
use Restoline\Content\Models\MarketingCampaign;
use Restoline\Content\Models\NewsletterSubscriber;
use Restoline\Content\Models\ReservationClientNotificationSetting;
use Restoline\Content\Models\ReservationFeedback;
use Restoline\Content\Models\ReservationFeedbackSetting;
use Restoline\Content\Models\ReservationReminder;
use Restoline\Content\Models\ReservationReminderSetting;
use Restoline\Content\Models\SmsConversation;
use Restoline\Content\Models\SmsReservationSetting;

class ClientCommunications extends AdminController
{
    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();
        AdminMenu::setContext('restoline-client-communications');
    }

    public function index()
    {
        $this->pageTitle = 'Communication clients';
        $reminderSettings = ReservationReminderSetting::current();
        $groupSettings = GroupBenefitSetting::current();
        $smsSettings = SmsReservationSetting::current();
        $feedbackSettings = ReservationFeedbackSetting::current();
        $clientNotificationSettings = ReservationClientNotificationSetting::current();

        return $this->makeView('index', [
            'reminderSettings' => $reminderSettings,
            'groupSettings' => $groupSettings,
            'smsSettings' => $smsSettings,
            'feedbackSettings' => $feedbackSettings,
            'clientNotificationSettings' => $clientNotificationSettings,
            'activeBirthdayCampaigns' => MarketingCampaign::query()
                ->where('type', 'birthday')->where('status', true)->count(),
            'activeSubscribers' => NewsletterSubscriber::query()->active()->count(),
            'feedbacksToReview' => ReservationFeedback::query()
                ->whereNotNull('responded_at')
                ->where('follow_up_status', 'new')->count(),
            'remindersSentToday' => ReservationReminder::query()
                ->whereDate('sent_at', now()->toDateString())->count(),
            'reminderFailures' => ReservationReminder::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->where(function($query): void {
                    $query->where('email_status', 'failed')->orWhere('sms_status', 'failed');
                })->count(),
            'smsNeedsHuman' => SmsConversation::query()
                ->whereIn('status', ['needs_human', 'human_handling'])->count(),
        ]);
    }

    public function index_onSaveReservationNotificationSettings()
    {
        $data = $this->validate(post(), [
            'confirmation_mode' => ['required', 'in:email_then_sms_fallback,email_and_sms'],
        ]);

        ReservationClientNotificationSetting::current()->fill($data)->save();
        flash()->success('Le mode de confirmation des réservations a été enregistré.');

        return redirect(admin_url('restoline/content/client_communications'));
    }
}
