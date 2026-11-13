<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use App\Services\Restoline\VoiceReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Igniter\Reservation\Models\Reservation;
use Restoline\Content\Models\RestolineServiceDevice;
use Restoline\Content\Models\ReservationServiceAction;
use Restoline\Content\Models\ReservationServiceOperation;
use Restoline\Content\Models\TableOrder;
use Restoline\Content\Models\TakeawayOrder;
use Restoline\Content\Models\TakeawayOrderAction;
use Restoline\Content\Services\TakeawayWorkflowService;

class RestolineServiceApiController extends Controller
{
    private const CONTRACT_VERSION = 'v1';

    public function health(Request $request): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }

        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'contract_version' => self::CONTRACT_VERSION,
            'device_id' => $device->device_id,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }

        $this->touchDevice($device);
        $date = $this->requestedDate($request);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        $orders = TableOrder::query()
            ->with('items')
            ->whereDate('valid_date', $date)
            ->orderByDesc('submitted_at')
            ->limit(150)
            ->get()
            ->map(fn(TableOrder $order) => [
                'id' => 'qr-'.$order->getKey(),
                'reference' => (string)$order->reference,
                'source' => 'qr_table',
                'status' => (string)$order->status,
                'print_status' => (string)$order->print_status,
                'received_at' => $order->submitted_at?->toIso8601String(),
                'printed_at' => $order->printed_at?->toIso8601String(),
                'table' => (string)$order->table_name,
                'note' => $order->note,
                'items' => $order->items->map(fn($item) => [
                    'id' => (int)$item->getKey(),
                    'name' => (string)$item->menu_name,
                    'description' => $item->menu_description,
                    'quantity' => (int)$item->quantity,
                ])->values(),
            ])->values();

        $reservationModels = Reservation::query()
            ->whereDate('reserve_date', $date)
            ->orderBy('reserve_time')
            ->limit(150)
            ->get();
        $operations = ReservationServiceOperation::query()
            ->whereIn('reservation_id', $reservationModels->pluck('reservation_id'))
            ->get()
            ->keyBy('reservation_id');
        $reservations = $reservationModels
            ->map(fn(Reservation $reservation) => $this->reservationPayload(
                $reservation,
                $operations->get($reservation->getKey()),
            ))->values();
        $takeaways = TakeawayOrder::query()
            ->whereDate('pickup_at', $date)
            ->orderBy('pickup_at')
            ->limit(150)
            ->get()
            ->map(fn(TakeawayOrder $takeaway) => $this->takeawayPayload($takeaway))
            ->values();

        return response()->json([
            'date' => $date,
            'server_time' => now()->toIso8601String(),
            'orders' => $orders,
            'reservations' => $reservations,
            'takeaways' => $takeaways,
        ]);
    }

    public function reservationSlots(Request $request): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->query(), [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();
        $service = $this->serviceFromTime((string)$request->query('time', '18:00'));
        $slots = app(VoiceReservationService::class)->availableSlots(
            $data['date'],
            $service,
            (int)$data['guests'],
        );
        $this->touchDevice($device);

        return response()->json([
            'date' => $data['date'],
            'service' => $service,
            'slots' => $slots,
        ]);
    }

    public function createReservation(Request $request): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $data = $this->validatedReservationInput($request, true);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            $reservation = app(VoiceReservationService::class)->createReservation([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? '',
                'guests' => (int)$data['guests'],
                'date' => $data['date'],
                'time' => $data['time'],
                'service' => $this->serviceFromTime($data['time']),
                'comment' => $this->staffComment((string)($data['note'] ?? '')),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Cette reservation ne peut pas etre enregistree pour ce creneau.'], 422);
        }

        $this->touchDevice($device);
        return response()->json([
            'ok' => true,
            'reservation' => $this->reservationPayload($reservation),
        ], 201);
    }

    public function updateReservation(Request $request, int $reservationId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $reservation = Reservation::query()->find($reservationId);
        if (!$reservation) {
            return response()->json(['message' => 'Reservation introuvable.'], 404);
        }
        $data = $this->validatedReservationInput($request, false);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if (isset($data['name'])) {
            [$firstName, $lastName] = $this->splitName($data['name']);
            $reservation->first_name = $firstName;
            $reservation->last_name = $lastName;
        }
        foreach (['phone' => 'telephone', 'email' => 'email', 'guests' => 'guest_num', 'date' => 'reserve_date'] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $reservation->{$column} = $data[$input];
            }
        }
        if (isset($data['time'])) {
            $reservation->reserve_time = $data['time'].':00';
        }
        if (array_key_exists('note', $data)) {
            $reservation->comment = $this->staffComment((string)$data['note']);
        }
        $reservation->save();
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'reservation' => $this->reservationPayload($reservation->fresh()),
        ]);
    }

    public function cancelReservation(Request $request, int $reservationId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $reservation = Reservation::query()->find($reservationId);
        if (!$reservation) {
            return response()->json(['message' => 'Reservation introuvable.'], 404);
        }
        if (!$reservation->isCanceled()) {
            $reservation->markAsCanceled([
                'notify' => false,
                'comment' => 'Annulation effectuee depuis Restoline Service.',
            ]);
        }
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'reservation' => $this->reservationPayload($reservation->fresh()),
        ]);
    }

    public function updateReservationOperationalStatus(Request $request, int $reservationId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $reservation = Reservation::query()->find($reservationId);
        if (!$reservation) {
            return response()->json(['message' => 'Reservation introuvable.'], 404);
        }
        if ($reservation->isCanceled()) {
            return response()->json(['message' => 'Une reservation annulee ne peut pas etre suivie en service.'], 422);
        }
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:planned,arrived,seated,completed,no_show'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:80'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();

        $previousAction = ReservationServiceAction::query()
            ->where('device_id', $device->getKey())
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();
        if ($previousAction) {
            if ((int)$previousAction->reservation_id !== (int)$reservation->getKey()) {
                return response()->json(['message' => 'Cle d action deja utilisee pour une autre reservation.'], 409);
            }
            $operation = ReservationServiceOperation::query()
                ->where('reservation_id', $reservation->getKey())
                ->first();
            return response()->json([
                'ok' => true,
                'idempotent_replay' => true,
                'reservation' => $this->reservationPayload($reservation, $operation),
            ]);
        }

        $operation = ReservationServiceOperation::query()->firstOrNew([
            'reservation_id' => $reservation->getKey(),
        ]);
        $previousStatus = $operation->exists ? (string)$operation->operational_status : 'planned';
        $operation->operational_status = $data['status'];
        $operation->updated_by_device_id = $device->getKey();
        $operation->save();

        ReservationServiceAction::query()->create([
            'reservation_id' => $reservation->getKey(),
            'device_id' => $device->getKey(),
            'idempotency_key' => $data['idempotency_key'],
            'action' => 'operational_status',
            'previous_status' => $previousStatus,
            'next_status' => $data['status'],
            'note' => trim((string)($data['note'] ?? '')) ?: null,
        ]);
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'reservation' => $this->reservationPayload($reservation, $operation->fresh()),
        ]);
    }

    public function createTakeaway(Request $request): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $data = $this->validatedTakeawayInput($request, true);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $takeaway = TakeawayOrder::query()->create([
            'status' => 'new',
            'customer_name' => $data['name'],
            'telephone' => $data['phone'],
            'pickup_at' => $this->takeawayPickupAt($data),
            'items' => $data['items'] ?? null,
            'note' => $data['note'] ?? null,
            'payment_note' => $data['payment_note'] ?? null,
            'created_by_device_id' => $device->getKey(),
        ]);
        $takeaway->reference = 'EMP-'.str_pad((string)$takeaway->getKey(), 5, '0', STR_PAD_LEFT);
        $takeaway->save();
        $this->touchDevice($device);

        return response()->json(['ok' => true, 'takeaway' => $this->takeawayPayload($takeaway)], 201);
    }

    public function updateTakeaway(Request $request, int $takeawayId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $takeaway = TakeawayOrder::query()->find($takeawayId);
        if (!$takeaway) {
            return response()->json(['message' => 'Commande a emporter introuvable.'], 404);
        }
        $data = $this->validatedTakeawayInput($request, false);
        if ($data instanceof JsonResponse) {
            return $data;
        }
        foreach (['name' => 'customer_name', 'phone' => 'telephone', 'items' => 'items', 'note' => 'note', 'payment_note' => 'payment_note'] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $takeaway->{$column} = $data[$input];
            }
        }
        if (array_key_exists('pickup_date', $data) || array_key_exists('pickup_time', $data)) {
            $takeaway->pickup_at = $this->takeawayPickupAt([
                'pickup_date' => $data['pickup_date'] ?? $takeaway->pickup_at->toDateString(),
                'pickup_time' => $data['pickup_time'] ?? $takeaway->pickup_at->format('H:i'),
            ]);
        }
        $takeaway->save();
        $this->touchDevice($device);

        return response()->json(['ok' => true, 'takeaway' => $this->takeawayPayload($takeaway->fresh())]);
    }

    public function updateTakeawayStatus(Request $request, int $takeawayId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $takeaway = TakeawayOrder::query()->find($takeawayId);
        if (!$takeaway) {
            return response()->json(['message' => 'Commande a emporter introuvable.'], 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:new,accepted,preparing,ready,handed_over,cancelled'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:80'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();
        $previousAction = TakeawayOrderAction::query()
            ->where('device_id', $device->getKey())
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();
        if ($previousAction) {
            if ((int)$previousAction->takeaway_id !== (int)$takeaway->getKey()) {
                return response()->json(['message' => 'Cle d action deja utilisee pour une autre commande.'], 409);
            }
            return response()->json(['ok' => true, 'idempotent_replay' => true, 'takeaway' => $this->takeawayPayload($takeaway)]);
        }

        $previousStatus = (string)$takeaway->status;
        $takeaway->status = $data['status'];
        if ($data['status'] === 'accepted' && !$takeaway->accepted_at) {
            $takeaway->accepted_at = now();
        }
        if ($data['status'] === 'ready' && !$takeaway->ready_at) {
            $takeaway->ready_at = now();
        }
        if ($data['status'] === 'handed_over' && !$takeaway->handed_over_at) {
            $takeaway->handed_over_at = now();
        }
        $takeaway->save();
        TakeawayOrderAction::query()->create([
            'takeaway_id' => $takeaway->getKey(),
            'device_id' => $device->getKey(),
            'idempotency_key' => $data['idempotency_key'],
            'action' => 'status',
            'previous_status' => $previousStatus,
            'next_status' => $data['status'],
            'note' => trim((string)($data['note'] ?? '')) ?: null,
        ]);
        $this->touchDevice($device);

        return response()->json(['ok' => true, 'takeaway' => $this->takeawayPayload($takeaway->fresh())]);
    }

    public function acceptTakeaway(Request $request, int $takeawayId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $takeaway = TakeawayOrder::query()->find($takeawayId);
        if (!$takeaway) {
            return response()->json(['message' => 'Commande a emporter introuvable.'], 404);
        }
        if ($takeaway->status !== 'new') {
            return response()->json(['message' => 'Cette commande ne peut plus etre acceptee.'], 422);
        }
        $data = $this->validatedTakeawayAction($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }
        if ($replay = $this->takeawayActionReplay($takeaway, $device->getKey(), $data['idempotency_key'])) {
            return $replay;
        }

        $workflow = app(TakeawayWorkflowService::class);
        $takeaway->status = 'accepted';
        $takeaway->accepted_at ??= now();
        $workflow->updateEstimate($takeaway, (int)$data['delay_minutes']);
        $notification = $workflow->notifyAccepted($takeaway->fresh());
        $this->recordTakeawayAction($takeaway, $device->getKey(), $data, 'accepted', 'new', 'accepted');
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'customer_notification' => $notification,
            'takeaway' => $this->takeawayPayload($takeaway->fresh()),
        ]);
    }

    public function updateTakeawayEstimate(Request $request, int $takeawayId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $takeaway = TakeawayOrder::query()->find($takeawayId);
        if (!$takeaway) {
            return response()->json(['message' => 'Commande a emporter introuvable.'], 404);
        }
        if (!in_array($takeaway->status, ['accepted', 'preparing'], true)) {
            return response()->json(['message' => 'Acceptez d abord cette commande avant de modifier son delai.'], 422);
        }
        $data = $this->validatedTakeawayAction($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }
        if ($replay = $this->takeawayActionReplay($takeaway, $device->getKey(), $data['idempotency_key'])) {
            return $replay;
        }

        $previousStatus = (string)$takeaway->status;
        $workflow = app(TakeawayWorkflowService::class);
        $workflow->updateEstimate($takeaway, (int)$data['delay_minutes']);
        $notification = $workflow->notifyDelay($takeaway->fresh());
        $this->recordTakeawayAction($takeaway, $device->getKey(), $data, 'estimate', $previousStatus, $previousStatus);
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'customer_notification' => $notification,
            'takeaway' => $this->takeawayPayload($takeaway->fresh()),
        ]);
    }

    public function markTakeawayReady(Request $request, int $takeawayId): JsonResponse
    {
        $device = $this->authenticatedDevice($request);
        if (!$device) {
            return $this->unauthorized();
        }
        $takeaway = TakeawayOrder::query()->find($takeawayId);
        if (!$takeaway) {
            return response()->json(['message' => 'Commande a emporter introuvable.'], 404);
        }
        if (!in_array($takeaway->status, ['accepted', 'preparing'], true)) {
            return response()->json(['message' => 'Cette commande doit etre acceptee ou en preparation avant d etre declaree prete.'], 422);
        }
        $data = $this->validatedTakeawayAction($request, false);
        if ($data instanceof JsonResponse) {
            return $data;
        }
        if ($replay = $this->takeawayActionReplay($takeaway, $device->getKey(), $data['idempotency_key'])) {
            return $replay;
        }

        $previousStatus = (string)$takeaway->status;
        $takeaway->status = 'ready';
        $takeaway->ready_at ??= now();
        $takeaway->save();
        $notification = app(TakeawayWorkflowService::class)->notifyReady($takeaway->fresh());
        $this->recordTakeawayAction($takeaway, $device->getKey(), $data, 'ready', $previousStatus, 'ready');
        $this->touchDevice($device);

        return response()->json([
            'ok' => true,
            'customer_notification' => $notification,
            'takeaway' => $this->takeawayPayload($takeaway->fresh()),
        ]);
    }

    private function requestedDate(Request $request): string|JsonResponse
    {
        $date = (string)$request->query('date', now()->toDateString());
        $validator = Validator::make(['date' => $date], [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:+365 days'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        return $date;
    }

    private function validatedReservationInput(Request $request, bool $creating): array|JsonResponse
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:2', 'max:100'],
            'phone' => [$creating ? 'required' : 'sometimes', 'string', 'min:8', 'max:24'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'guests' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:30'],
            'date' => [$creating ? 'required' : 'sometimes', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:+365 days'],
            'time' => [$creating ? 'required' : 'sometimes', 'date_format:H:i'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();
        if (!$creating && $data === []) {
            return response()->json(['message' => 'Aucune modification recue.'], 422);
        }
        if (array_key_exists('phone', $data)) {
            $phone = $this->normalizeBelgianPhone((string)$data['phone']);
            if ($phone === null) {
                return response()->json(['message' => 'Numero de telephone invalide.'], 422);
            }
            $data['phone'] = $phone;
        }

        return $data;
    }

    private function validatedTakeawayInput(Request $request, bool $creating): array|JsonResponse
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:2', 'max:120'],
            'phone' => [$creating ? 'required' : 'sometimes', 'string', 'min:8', 'max:24'],
            'pickup_date' => [$creating ? 'required' : 'sometimes', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:+365 days'],
            'pickup_time' => [$creating ? 'required' : 'sometimes', 'date_format:H:i'],
            'items' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'payment_note' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();
        if (!$creating && $data === []) {
            return response()->json(['message' => 'Aucune modification recue.'], 422);
        }
        if (array_key_exists('phone', $data)) {
            $phone = $this->normalizeBelgianPhone((string)$data['phone']);
            if ($phone === null) {
                return response()->json(['message' => 'Numero de telephone invalide.'], 422);
            }
            $data['phone'] = $phone;
        }

        return $data;
    }

    private function takeawayPickupAt(array $data): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['pickup_date'].' '.$data['pickup_time'],
            config('app.timezone'),
        );
    }

    private function normalizeBelgianPhone(string $phone): ?string
    {
        $value = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if (preg_match('/^0(\d{8,9})$/', $value, $matches) === 1) {
            $value = '+32'.$matches[1];
        } elseif (preg_match('/^32(\d{8,9})$/', $value, $matches) === 1) {
            $value = '+32'.$matches[1];
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $value) === 1 ? $value : null;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function staffComment(string $note): string
    {
        $note = trim($note);
        $prefix = 'Reservation saisie ou modifiee depuis Restoline Service.';
        return $note === '' ? $prefix : $prefix."\n".$note;
    }

    private function serviceFromTime(string $time): string
    {
        return (int)substr($time, 0, 2) < 16 ? 'lunch' : 'dinner';
    }

    private function reservationPayload(Reservation $reservation, ?ReservationServiceOperation $operation = null): array
    {
        return [
            'id' => 'reservation-'.$reservation->getKey(),
            'reservation_id' => (int)$reservation->getKey(),
            'status_id' => (int)$reservation->status_id,
            'status' => $reservation->status_name ?: 'A confirmer',
            'operational_status' => $operation?->operational_status ?? 'planned',
            'operational_updated_at' => $operation?->updated_at?->toIso8601String(),
            'time' => substr((string)$reservation->reserve_time, 0, 5),
            'guests' => (int)$reservation->guest_num,
            'customer' => [
                'name' => trim((string)$reservation->first_name.' '.(string)$reservation->last_name),
                'phone' => $reservation->telephone,
                'email' => $reservation->email,
            ],
            'note' => $reservation->comment,
            'updated_at' => $reservation->updated_at?->toIso8601String(),
        ];
    }

    private function takeawayPayload(TakeawayOrder $takeaway): array
    {
        return [
            'takeaway_id' => (int)$takeaway->getKey(),
            'reference' => (string)$takeaway->reference,
            'status' => (string)$takeaway->status,
            'customer' => [
                'name' => (string)$takeaway->customer_name,
                'phone' => (string)$takeaway->telephone,
                'email' => (string)$takeaway->customer_email,
            ],
            'pickup_at' => $takeaway->pickup_at?->toIso8601String(),
            'estimated_delay_minutes' => (int)($takeaway->estimated_delay_minutes ?? 30),
            'estimated_ready_at' => $takeaway->estimated_ready_at?->toIso8601String(),
            'customer_estimate_notified_at' => $takeaway->customer_estimate_notified_at?->toIso8601String(),
            'customer_ready_notified_at' => $takeaway->customer_ready_notified_at?->toIso8601String(),
            'items' => $takeaway->items,
            'note' => $takeaway->note,
            'payment_note' => $takeaway->payment_note,
            'updated_at' => $takeaway->updated_at?->toIso8601String(),
        ];
    }

    private function validatedTakeawayAction(Request $request, bool $requiresDelay = true): array|JsonResponse
    {
        $rules = [
            'idempotency_key' => ['required', 'string', 'min:16', 'max:80'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
        if ($requiresDelay) {
            $rules['delay_minutes'] = ['required', 'integer', 'min:5', 'max:240'];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        return $validator->validated();
    }

    private function takeawayActionReplay(TakeawayOrder $takeaway, int $deviceId, string $idempotencyKey): ?JsonResponse
    {
        $previousAction = TakeawayOrderAction::query()
            ->where('device_id', $deviceId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if (!$previousAction) {
            return null;
        }
        if ((int)$previousAction->takeaway_id !== (int)$takeaway->getKey()) {
            return response()->json(['message' => 'Cle d action deja utilisee pour une autre commande.'], 409);
        }

        return response()->json([
            'ok' => true,
            'idempotent_replay' => true,
            'takeaway' => $this->takeawayPayload($takeaway),
        ]);
    }

    private function recordTakeawayAction(
        TakeawayOrder $takeaway,
        int $deviceId,
        array $data,
        string $action,
        string $previousStatus,
        string $nextStatus,
    ): void {
        TakeawayOrderAction::query()->create([
            'takeaway_id' => $takeaway->getKey(),
            'device_id' => $deviceId,
            'idempotency_key' => $data['idempotency_key'],
            'action' => $action,
            'previous_status' => $previousStatus,
            'next_status' => $nextStatus,
            'note' => trim((string)($data['note'] ?? '')) ?: null,
        ]);
    }

    private function authenticatedDevice(Request $request): ?RestolineServiceDevice
    {
        $deviceId = trim((string)$request->header('X-Restoline-Device-Id'));
        $token = trim((string)$request->bearerToken());
        if ($deviceId === '' || $token === '') {
            return null;
        }

        $device = RestolineServiceDevice::query()
            ->where('device_key', $deviceId)
            ->where('status', 'active')
            ->first();

        if (!$device || !hash_equals((string)$device->token_hash, hash('sha256', $token))) {
            return null;
        }

        return $device;
    }

    private function touchDevice(RestolineServiceDevice $device): void
    {
        if ($device->last_seen_at && $device->last_seen_at->greaterThan(now()->subMinutes(5))) {
            return;
        }

        $device->last_seen_at = now();
        $device->save();
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Acces refuse.'], 401);
    }
}
