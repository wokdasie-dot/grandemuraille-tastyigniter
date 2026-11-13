<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Carbon\Carbon;
use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\ListController;
use Restoline\Content\Models\TelephonyEvent;

class TelephonyEvents extends AdminController
{
    public array $implement = [
        ListController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => TelephonyEvent::class,
            'title' => 'Statistiques répondeur',
            'emptyMessage' => 'Aucun événement téléphonique enregistré.',
            'defaultSort' => ['created_at', 'DESC'],
            'configFile' => 'telephonyevent',
        ],
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.Marketing';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('restoline-client-communications');
    }

    public function summary()
    {
        $this->pageTitle = 'Résumé mensuel répondeur';

        $month = (string)request()->query('month', Carbon::now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $events = TelephonyEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();
        $todayEvents = $events->filter(fn($event) => $event->created_at?->isToday());
        $last7DaysEvents = $events->filter(fn($event) => $event->created_at && $event->created_at->gte(now()->subDays(6)->startOfDay()));

        $voiceEvents = $events->where('channel', 'voice');
        $smsEvents = $events->where('channel', 'sms');
        $eventLabels = TelephonyEvent::eventOptions();

        $eventTypes = $events
            ->groupBy('event_type')
            ->map(fn($items, $type) => [
                'label' => $eventLabels[$type] ?? (string)$type,
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(12);

        $menuChoices = $events
            ->filter(fn($event) => $event->event_type === 'voice_menu_choice' && is_array($event->payload) && ($event->payload['option'] ?? null))
            ->groupBy(fn($event) => (string)$event->payload['option'])
            ->map(fn($items, $option) => [
                'option' => $option,
                'count' => $items->count(),
            ])
            ->sortKeys();

        $statusCounts = $events
            ->filter(fn($event) => filled($event->status))
            ->groupBy('status')
            ->map(fn($items, $status) => [
                'status' => (string)$status,
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(10);
        $doubtfulEventTypes = [
            'voice_reservation_failed',
            'voice_reservation_voicemail_email_failed',
            'voice_session_limited',
            'voice_unavailable',
            'voice_rate_limited',
            'voice_menu_invalid_limited',
            'sms_error',
            'sms_gateway_fallback_twilio',
        ];
        $doubtfulStatuses = ['Failed', 'failed', 'undelivered', 'Undelivered', 'Cancelled', 'cancelled'];
        $problemEvents = $events
            ->filter(fn($event) => in_array($event->event_type, $doubtfulEventTypes, true)
                || in_array((string)$event->status, $doubtfulStatuses, true))
            ->take(25);
        $dailyCounts = $events
            ->groupBy(fn($event) => $event->created_at?->format('Y-m-d') ?: '-')
            ->map(fn($items, $date) => [
                'date' => (string)$date,
                'calls' => $items->where('channel', 'voice')->pluck('call_sid')->filter()->unique()->count(),
                'voice_messages' => $items->where('event_type', 'voice_reservation_voicemail')->count(),
                'reservations' => $items->where('event_type', 'voice_reservation_confirmed')->count(),
                'sms_delivered' => $items->where('event_type', 'sms_gateway_status')->where('status', 'Delivered')->count(),
                'problems' => $items->filter(fn($event) => in_array($event->event_type, $doubtfulEventTypes, true)
                    || in_array((string)$event->status, $doubtfulStatuses, true))->count(),
            ])
            ->sortByDesc('date')
            ->values()
            ->take(14);

        $averageDuration = (int)round((float)$voiceEvents
            ->filter(fn($event) => $event->duration_seconds !== null)
            ->avg('duration_seconds'));

        return $this->makeView('summary', [
            'month' => $month,
            'periodLabel' => $start->translatedFormat('F Y'),
            'events' => $events,
            'eventTypes' => $eventTypes,
            'menuChoices' => $menuChoices,
            'statusCounts' => $statusCounts,
            'averageDuration' => $averageDuration,
            'todayCalls' => $todayEvents->where('channel', 'voice')->pluck('call_sid')->filter()->unique()->count(),
            'todayVoiceMessages' => $todayEvents->where('event_type', 'voice_reservation_voicemail')->count(),
            'last7Calls' => $last7DaysEvents->where('channel', 'voice')->pluck('call_sid')->filter()->unique()->count(),
            'last7VoiceMessages' => $last7DaysEvents->where('event_type', 'voice_reservation_voicemail')->count(),
            'last7ConfirmedReservations' => $last7DaysEvents->where('event_type', 'voice_reservation_confirmed')->count(),
            'smsGatewayDelivered' => $smsEvents->where('event_type', 'sms_gateway_status')->where('status', 'Delivered')->count(),
            'smsGatewayFailed' => $smsEvents->where('event_type', 'sms_gateway_status')->filter(fn($event) => in_array((string)$event->status, ['Failed', 'failed', 'Cancelled', 'cancelled'], true))->count(),
            'problemEvents' => $problemEvents,
            'dailyCounts' => $dailyCounts,
            'totalVoiceEvents' => $voiceEvents->count(),
            'totalSmsEvents' => $smsEvents->count(),
            'totalCalls' => $voiceEvents->pluck('call_sid')->filter()->unique()->count(),
            'totalSms' => $smsEvents->pluck('message_sid')->filter()->unique()->count(),
            'confirmedReservations' => $events->where('event_type', 'voice_reservation_confirmed')->count(),
            'failedRequests' => $problemEvents->count(),
        ]);
    }
}
