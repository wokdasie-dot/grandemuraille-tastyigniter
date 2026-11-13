<?php

declare(strict_types=1);

namespace Restoline\Content;

use Igniter\Local\Events\WorkingScheduleCreatedEvent;
use Igniter\Local\Models\Location;
use Igniter\Cart\Models\Order;
use Igniter\Reservation\Models\Reservation;
use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Admin\Classes\Navigation;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Classes\BaseExtension;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Override;
use Restoline\Content\Console\CheckSmsGatewayFallbacks;
use Restoline\Content\Console\CheckSmsGatewayHealth;
use Restoline\Content\Console\CleanTelephonyEvents;
use Restoline\Content\Console\CleanTableOrders;
use Restoline\Content\Console\CleanVoiceAudio;
use Restoline\Content\Console\CheckVoiceMessages;
use Restoline\Content\Console\GenerateTableAccessCodes;
use Restoline\Content\Console\ProcessVoiceMessages;
use Restoline\Content\Console\ReconcileEmailNotifications;
use Restoline\Content\Console\SendReservationFeedbackRequests;
use Restoline\Content\Console\SendReservationReminders;
use Restoline\Content\Models\BuffetPricing;
use Restoline\Content\Models\DailyCapacityOverride;
use Restoline\Content\Models\SmsReservationSetting;
use Restoline\Content\Console\SendBirthdayPromotions;
use Restoline\Content\Models\ContentItem;
use Restoline\Content\Models\EmailMarketingSetting;
use Restoline\Content\Models\GroupBenefitSetting;
use Restoline\Content\Models\GiftCard;
use Restoline\Content\Models\GiftCardTransaction;
use Restoline\Content\Models\HomePopup;
use Restoline\Content\Models\MarketingCampaign;
use Restoline\Content\Models\NewsletterSubscriber;
use Restoline\Content\Models\RestaurantNotificationSetting;
use Restoline\Content\Models\ReservationFeedback;
use Restoline\Content\Models\ReservationFeedbackSetting;
use Restoline\Content\Models\ReservationReminder;
use Restoline\Content\Models\ReservationReminderSetting;
use Restoline\Content\Models\ScheduleException;
use Restoline\Content\Models\SmsConversation;
use Restoline\Content\Models\SmsConversationMessage;
use Restoline\Content\Models\TelephonyEvent;
use Restoline\Content\Models\TelephonySetting;
use Restoline\Content\Models\VoiceMessage;
use Restoline\Content\Services\ReservationOtpService;
use Restoline\Content\Services\TakeawayOrderNotificationService;

class Extension extends BaseExtension
{
    #[Override]
    public function register(): void
    {
        Event::listen('igniter.admin.registerRoutes', function($router): void {
            $router->post('language/{locale}', [
                \Restoline\Content\Http\Controllers\AdminLanguageController::class,
                'update',
            ])->where('locale', 'fr|en|zh_CN')->name('restoline.admin.language.update');
        });

        if (app()->runningInConsole()) {
            $this->commands([
                CleanVoiceAudio::class,
                CleanTelephonyEvents::class,
                CleanTableOrders::class,
                CheckSmsGatewayFallbacks::class,
                CheckSmsGatewayHealth::class,
                CheckVoiceMessages::class,
                ProcessVoiceMessages::class,
                ReconcileEmailNotifications::class,
                SendReservationFeedbackRequests::class,
                SendReservationReminders::class,
                SendBirthdayPromotions::class,
                GenerateTableAccessCodes::class,
            ]);
        }
    }

    #[Override]
    public function boot(): void
    {
        $this->app->make('router')->pushMiddlewareToGroup(
            'igniter',
            \Restoline\Content\Http\Middleware\RestrictPublicLocale::class,
        );

        if (class_exists(\Igniter\Translate\Actions\TranslatableModel::class)) {
            $this->registerTranslatableModels();
        }

        if (Igniter::runningInAdmin()) {
            AdminMenu::registerCallback(function(Navigation $manager): void {
                $manager->registerMainItems([
                    MainMenuItem::partial('restoline-language-switcher', 'restoline.content::_partials.admin_language_switcher')
                        ->priority(25),
                ]);
            });
        }

        Relation::morphMap([
            'restoline_buffet_pricing' => BuffetPricing::class,
            'restoline_daily_capacity_override' => DailyCapacityOverride::class,
            'restoline_content_item' => ContentItem::class,
            'restoline_newsletter_subscriber' => NewsletterSubscriber::class,
            'restoline_marketing_campaign' => MarketingCampaign::class,
            'restoline_email_marketing_setting' => EmailMarketingSetting::class,
            'restoline_group_benefit_setting' => GroupBenefitSetting::class,
            'restoline_gift_card' => GiftCard::class,
            'restoline_gift_card_transaction' => GiftCardTransaction::class,
            'restoline_home_popup' => HomePopup::class,
            'restoline_restaurant_notification_setting' => RestaurantNotificationSetting::class,
            'restoline_reservation_feedback' => ReservationFeedback::class,
            'restoline_reservation_feedback_setting' => ReservationFeedbackSetting::class,
            'restoline_reservation_reminder' => ReservationReminder::class,
            'restoline_reservation_reminder_setting' => ReservationReminderSetting::class,
            'restoline_sms_conversation' => SmsConversation::class,
            'restoline_sms_conversation_message' => SmsConversationMessage::class,
            'restoline_sms_gateway_message' => \Restoline\Content\Models\SmsGatewayMessage::class,
            'restoline_telephony_event' => TelephonyEvent::class,
            'restoline_telephony_setting' => TelephonySetting::class,
            'restoline_sms_reservation_setting' => SmsReservationSetting::class,
            'restoline_voice_message' => VoiceMessage::class,
        ]);

        app()->booted(function(): void {
            Event::forget('igniter.reservation.confirmed');
        });

        Event::listen('igniter.reservation.statusAdded', function(
            Reservation $reservation,
        ): void {
            app(ReservationOtpService::class)->sendStatusNotifications($reservation);
        });

        Event::listen('igniter.reservation.isFullyBookedOn', function(
            \DateTime $dateTime,
            $noOfGuests,
        ): ?bool {
            return DailyCapacityOverride::isFullyBookedForDateTime(\Carbon\Carbon::instance($dateTime))
                ? true
                : null;
        });

        Event::listen('igniter.workingSchedule.timeslotValid', function ($schedule, $timeslot): ?bool {
            $time = $timeslot->format('H:i');
            $limits = config('restoline.reservation.last_arrival', []);
            $lunch = (string)($limits['lunch'] ?? '14:00');
            $dinner = (string)($limits['dinner'] ?? '22:00');
            if ($time < '17:00' && $time > $lunch) {
                return false;
            }
            if ($time >= '17:00' && $time > $dinner) {
                return false;
            }

            return null;
        });

        Event::listen('admin.navigation.extendItems', function ($navigation): void {
            foreach ([
                'orders',
            ] as $item) {
                $navigation->removeNavItem($item);
            }
            foreach ([
                'mealtimes' => 'restaurant',
                'inventory' => 'restaurant',
                'coupons' => 'marketing',
                'reviews' => 'marketing',
                'sliders' => 'design',
                'resources' => 'tools',
                'automation' => 'tools',
            ] as $item => $parent) {
                $navigation->removeNavItem($item, $parent);
            }
        });

        Event::listen('igniter.reservation.beforeSaveReservation', function(
            Reservation $reservation,
            array $data,
        ): void {
            $phone = trim((string)($data['telephone'] ?? ''));
            $digits = preg_replace('/\D+/', '', $phone) ?? '';
            if ($phone === '' || preg_match('/^[0-9\s\-\+\(\)\.]+$/', $phone) !== 1 || strlen($digits) < 8 || strlen($digits) > 15) {
                throw new \Igniter\Flame\Exception\ApplicationException(
                    (string)__('validation.phone', ['attribute' => (string)lang('igniter.reservation::default.label_telephone')]),
                );
            }

            if (!\Restoline\Content\Support\OnlineBooking::isEnabled()) {
                throw new \Igniter\Flame\Exception\ApplicationException(
                    'Les réservations en ligne sont momentanément désactivées. Merci de nous appeler au '.config('restoline.restaurant.phone_display').'.',
                );
            }

            $dateTime = \Carbon\Carbon::parse((string)($data['sdateTime'] ?? ''));

            if (DailyCapacityOverride::isFullyBookedForDateTime($dateTime, $reservation->location_id ?: 1)) {
                throw new \Igniter\Flame\Exception\ApplicationException(
                    'Ce service est complet. Veuillez choisir un autre créneau.',
                );
            }
        });

        // Bouton « activer / desactiver la reservation en ligne » dans l'onglet Reservations du dashboard.
        Event::listen('admin.controller.beforeInit', function($controller): void {
            if (!$controller instanceof \Igniter\Reservation\Http\Controllers\Reservations) {
                return;
            }

            $controller->addDynamicMethod('onToggleOnlineBooking', function() {
                $enabled = \Restoline\Content\Support\OnlineBooking::toggle();

                \Igniter\Flame\Flash\Facades\Flash::success(
                    $enabled
                        ? 'Réservation en ligne activée : les clients peuvent à nouveau réserver.'
                        : 'Réservation en ligne désactivée : plus aucune réservation ne peut être envoyée depuis le site.',
                );

                return redirect(admin_url('reservations'));
            });
        });

        Event::listen('admin.controller.beforeRemap', function($controller): void {
            if (!$controller instanceof \Igniter\Reservation\Http\Controllers\Reservations) {
                return;
            }

            $toolbar = $controller->widgets['toolbar'] ?? null;
            if (!$toolbar instanceof \Igniter\Admin\Widgets\Toolbar) {
                return;
            }

            $enabled = \Restoline\Content\Support\OnlineBooking::isEnabled();

            $toolbar->addButton('toggle_online_booking', [
                'type' => 'button',
                'label' => $enabled
                    ? '<i class="fa fa-toggle-on"></i>&nbsp;Désactiver la réservation en ligne'
                    : '<i class="fa fa-toggle-off"></i>&nbsp;Activer la réservation en ligne',
                'class' => $enabled ? 'btn btn-warning' : 'btn btn-success',
                'data-request' => 'onToggleOnlineBooking',
                'data-request-confirm' => $enabled
                    ? 'Désactiver la réservation en ligne ? Le formulaire du site sera fermé.'
                    : 'Réactiver la réservation en ligne ?',
                'data-attach-loading' => '',
            ]);
        });

        Event::listen(WorkingScheduleCreatedEvent::class, function(
            WorkingScheduleCreatedEvent $event,
        ): void {
            if (!$event->model instanceof Location || $event->schedule->getType() !== Location::OPENING) {
                return;
            }

            $startDate = now()->subDays(2)->startOfDay();
            $endDate = now()->addDays($event->schedule->days() + 2)->endOfDay();
            $exceptions = [];

            ScheduleException::query()
                ->where('status', true)
                ->where('location_id', $event->model->getKey())
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->orderBy('exception_id')
                ->get()
                ->each(function(ScheduleException $exception) use (&$exceptions, $startDate, $endDate): void {
                    foreach ($exception->datesBetween($startDate, $endDate) as $date) {
                        $exceptions[$date] = $exception->workingPeriods();
                    }
                });

            $event->schedule->setExceptions($exceptions);
        });

        Route::middleware('web')->group(function(): void {
            Route::redirect('wok', config('restoline.restaurant.reservation_path'), 302);

            Route::get('locale/{locale}', [
                \Restoline\Content\Http\Controllers\PublicLocaleController::class,
                'update',
            ])->where('locale', 'fr|en|nl')->name('restoline.locale');

            Route::get(config('restoline.restaurant.manage_reservation_path'), [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'index',
            ])->name('restoline.manage.index');

            Route::get(config('restoline.restaurant.manage_reservation_path').'/acces/{hash}', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'directAccess',
            ])->middleware('signed')->name('restoline.manage.direct');

            Route::get('r/{token}', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'reminderAccess',
            ])->where('token', '[A-Za-z0-9]{12}')->name('restoline.manage.reminder');

            Route::post(config('restoline.restaurant.manage_reservation_path').'/code', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'sendCode',
            ])->name('restoline.manage.send-code');

            Route::post(config('restoline.restaurant.manage_reservation_path').'/verifier', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'verify',
            ])->name('restoline.manage.verify');

            Route::post(config('restoline.restaurant.manage_reservation_path').'/modifier', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'update',
            ])->name('restoline.manage.update');

            Route::post(config('restoline.restaurant.manage_reservation_path').'/annuler', [
                \Restoline\Content\Http\Controllers\ManageReservationController::class,
                'cancel',
            ])->name('restoline.manage.cancel');

        });

    }

    #[Override]
    public function registerNavigation(): array
    {
        return [
            'restoline' => [
                'priority' => 21,
                'class' => 'restoline',
                'icon' => 'fa-concierge-bell',
                'href' => admin_url('restoline/content/content_items'),
                'title' => lang('restoline.content::default.navigation.restoline'),
                'child' => [
                    'restoline-content' => [
                        'priority' => 10,
                        'class' => 'restoline-content',
                        'href' => admin_url('restoline/content/content_items'),
                        'title' => lang('restoline.content::default.navigation.content'),
                        'permission' => 'Restoline.Content.Manage',
                    ],
                    'restoline-schedule-exceptions' => [
                        'priority' => 20,
                        'class' => 'restoline-schedule-exceptions',
                        'href' => admin_url('restoline/content/schedule_exceptions'),
                        'title' => lang('restoline.content::default.navigation.schedule_exceptions'),
                        'permission' => 'Restoline.Content.ScheduleExceptions',
                    ],
                    'restoline-booking-settings' => [
                        'priority' => 30,
                        'class' => 'restoline-booking-settings',
                        'href' => admin_url('locations/settings/1'),
                        'title' => lang('restoline.content::default.navigation.booking_settings'),
                        'permission' => 'Admin.Locations',
                    ],
                    'restoline-buffet-pricing' => [
                        'priority' => 40,
                        'class' => 'restoline-buffet-pricing',
                        'href' => admin_url('restoline/content/buffet_pricings'),
                        'title' => lang('restoline.content::default.navigation.buffet_pricing'),
                        'permission' => 'Restoline.Content.BuffetPricing',
                    ],
                    'restoline-daily-capacity' => [
                        'priority' => 50,
                        'class' => 'restoline-daily-capacity',
                        'href' => admin_url('restoline/content/daily_capacity_overrides'),
                        'title' => lang('restoline.content::default.navigation.daily_capacity'),
                        'permission' => 'Restoline.Content.DailyCapacity',
                    ],
                    'restoline-gallery' => [
                        'priority' => 60,
                        'class' => 'restoline-gallery',
                        'href' => admin_url('restoline/content/gallery_items'),
                        'title' => lang('restoline.content::default.navigation.gallery'),
                        'permission' => 'Restoline.Content.Gallery',
                    ],
                ],
            ],
        ];
    }

    #[Override]
    public function registerPermissions(): array
    {
        return [
            'Restoline.Content.Manage' => [
                'description' => 'Gérer le contenu public du site',
                'group' => 'La Grande Muraille',
            ],
            'Restoline.Content.ScheduleExceptions' => [
                'description' => 'Gérer les ouvertures et fermetures exceptionnelles',
                'group' => 'La Grande Muraille',
            ],
            'Restoline.Content.BuffetPricing' => [
                'description' => 'Gérer les tarifs du buffet',
                'group' => 'La Grande Muraille',
            ],
            'Restoline.Content.DailyCapacity' => [
                'description' => 'Gérer les disponibilités du jour (complet midi/soir)',
                'group' => 'La Grande Muraille',
            ],
            'Restoline.Content.Gallery' => [
                'description' => 'Gérer la galerie photos de la page d\'accueil',
                'group' => 'La Grande Muraille',
            ],
        ];
    }

    private function registerTranslatableModels(): void
    {
        $fields = [
            BuffetPricing::class => ['label'],
            ContentItem::class => ['title', 'summary', 'content'],
            \Restoline\Content\Models\GalleryItem::class => ['title', 'caption'],
        ];

        foreach ($fields as $class => $attributes) {
            $class::extend(function($model) use ($attributes): void {
                $model->implement[] = \Igniter\Translate\Actions\TranslatableModel::class;
                $model->addDynamicMethod('translatable', fn(): array => $attributes);
            });
        }
    }
}
