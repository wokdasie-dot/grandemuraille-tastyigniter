<?php
declare(strict_types=1);
namespace Restoline\Content\Http\Controllers;
use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Restoline\Content\Models\SmsReservationSetting;
class SmsReservationSettings extends AdminController
{
    public array $implement = [FormController::class];
    public array $formConfig = [
        'name'=>'Réglages réservations SMS',
        'model'=>SmsReservationSetting::class,
        'create'=>['title'=>'Réglages réservations SMS','redirect'=>'restoline/content/sms_reservation_settings/edit/{setting_id}','redirectClose'=>'restoline/content/sms_conversations'],
        'edit'=>['title'=>'Réglages réservations SMS','redirect'=>'restoline/content/sms_reservation_settings/edit/{setting_id}','redirectClose'=>'restoline/content/sms_conversations'],
        'configFile'=>'smsreservationsetting',
    ];
    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';
    public function __construct() { parent::__construct(); AdminMenu::setContext('restoline-client-communications'); }
    public function index() { $record = SmsReservationSetting::current(); return redirect(admin_url('restoline/content/sms_reservation_settings/edit/'.$record->getKey())); }
}
