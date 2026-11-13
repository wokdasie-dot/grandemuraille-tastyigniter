<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\ReservationFeedback;
use Restoline\Content\Models\ReservationFeedbackSetting;

class ReservationFeedbacks extends AdminController
{
    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => ReservationFeedback::class,
            'title' => 'Retours réservation',
            'emptyMessage' => 'Aucun retour reçu pour le moment.',
            'defaultSort' => ['created_at', 'DESC'],
            'configFile' => 'reservationfeedback',
        ],
    ];

    public array $formConfig = [
        'name' => 'Retour réservation',
        'model' => ReservationFeedback::class,
        'preview' => [
            'title' => 'Détail du retour',
            'back' => 'restoline/content/reservation_feedbacks',
        ],
        'edit' => [
            'title' => 'Détail du retour',
            'redirect' => 'restoline/content/reservation_feedbacks/edit/{feedback_id}',
            'redirectClose' => 'restoline/content/reservation_feedbacks',
        ],
        'configFile' => 'reservationfeedback',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }

    public function index(): void
    {
        $this->vars['feedbackSettings'] = ReservationFeedbackSetting::current();
        $this->asExtension('ListController')->index();
    }

    public function index_onSaveSettings()
    {
        $data = $this->validate(post(), [
            'enabled' => ['required', 'boolean'],
            'sample_percent' => ['required', 'integer', 'in:20,40,60,100'],
            'days_after_visit' => ['required', 'integer', 'min:1', 'max:7'],
            'cooldown_days' => ['required', 'integer', 'min:0', 'max:365'],
            'google_review_enabled' => ['required', 'boolean'],
            'email_subject' => ['required', 'string', 'max:190'],
            'email_heading' => ['required', 'string', 'max:190'],
            'email_message' => ['required', 'string', 'max:1500'],
        ]);

        ReservationFeedbackSetting::current()->fill($data)->save();
        flash()->success('Les réglages des avis clients ont été enregistrés.');

        return redirect(admin_url('restoline/content/reservation_feedbacks'));
    }
}
