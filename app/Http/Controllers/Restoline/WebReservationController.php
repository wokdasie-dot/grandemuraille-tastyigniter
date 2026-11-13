<?php

namespace App\Http\Controllers\Restoline;

use App\Http\Controllers\Controller;
use App\Services\Restoline\PrivateEnvironment;
use App\Services\Restoline\VoiceReservationService;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

final class WebReservationController extends Controller
{
    public function __construct(
        private readonly VoiceReservationService $reservations,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (!$this->authorized($request)) {
            return response()->json(['ok' => false, 'message' => 'Acces refuse.'], 401);
        }

        $data = $this->validatePayload($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if ($limited = $this->enforceReservationRateLimits($request, $data, 'create')) {
            return $limited;
        }

        try {
            $reference = strtoupper(trim((string)$request->input('reference', '')));
            if ($reference !== '' && preg_match('/^[A-F0-9]{16}$/', $reference)) {
                $existing = Reservation::query()
                    ->where('location_id', (int)PrivateEnvironment::get('RESTOLINE_LOCATION_ID', '1'))
                    ->where('comment', 'like', '%Web reference '.$reference.'%')
                    ->first();
                if ($existing) {
                    return response()->json([
                        'ok' => true,
                        'reservation_id' => $existing->reservation_id,
                        'status_id' => $existing->status_id,
                        'hash' => $existing->hash,
                        'idempotent' => true,
                    ]);
                }
            }

            $phoneValidationNote = (int)$data['guests'] >= 7
                ? 'Téléphone à valider par code SMS.'
                : 'Validation SMS non requise pour table de 1 à 6 personnes.';

            $reservation = $this->reservations->createReservation([
                'name' => $data['name'],
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'],
                'guests' => $data['guests'],
                'date' => $data['date'],
                'time' => $data['time'],
                'service' => ((int)substr($data['time'], 0, 2)) < 16 ? 'lunch' : 'dinner',
                'comment' => $phoneValidationNote." Réservation reçue depuis le site La Grande Muraille de Chine."
                    .($reference !== '' ? ' Web reference '.$reference.'.' : ''),
            ]);

            return response()->json([
                'ok' => true,
                'reservation_id' => $reservation->reservation_id,
                'status_id' => $reservation->status_id,
                'hash' => $reservation->hash,
            ], 201);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'La reservation ne peut pas etre enregistree pour ce creneau.',
            ], 422);
        }
    }

    public function update(Request $request, int $reservationId): JsonResponse
    {
        if (!$this->authorized($request)) {
            return response()->json(['ok' => false, 'message' => 'Acces refuse.'], 401);
        }

        $data = $this->validatePayload($request, false);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if ($limited = $this->enforceReservationRateLimits($request, $data, 'update')) {
            return $limited;
        }

        $reservation = $this->findReservation($reservationId);
        if (!$reservation) {
            return response()->json(['ok' => false, 'message' => 'Reservation introuvable.'], 404);
        }

        $service = ((int)substr($data['time'], 0, 2)) < 16 ? 'lunch' : 'dinner';
        $slots = $this->reservations->availableSlots($data['date'], $service, (int)$data['guests']);
        if (!in_array($data['time'], $slots, true)) {
            return response()->json(['ok' => false, 'message' => 'Ce creneau est indisponible.'], 422);
        }

        $reservation->guest_num = (int)$data['guests'];
        $reservation->reserve_date = $data['date'];
        $reservation->reserve_time = $data['time'].':00';
        $reservation->status_id = (int)setting('default_reservation_status');
        $reservation->comment = trim(
            ($reservation->comment ? $reservation->comment."\n" : '')
            .'Modification demandee par le client depuis son lien Restoline.',
        );
        $reservation->save();
        $reservation->addStatusHistory((int)setting('default_reservation_status'), ['notify' => false]);

        return response()->json([
            'ok' => true,
            'reservation_id' => $reservation->reservation_id,
            'status_id' => $reservation->status_id,
        ]);
    }

    public function cancel(Request $request, int $reservationId): JsonResponse
    {
        if (!$this->authorized($request)) {
            return response()->json(['ok' => false, 'message' => 'Acces refuse.'], 401);
        }

        $reservation = $this->findReservation($reservationId);
        if (!$reservation) {
            return response()->json(['ok' => false, 'message' => 'Reservation introuvable.'], 404);
        }

        if (!$reservation->isCanceled()) {
            $reservation->markAsCanceled([
                'notify' => false,
                'comment' => 'Annulation demandee par le client depuis son lien Restoline.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'reservation_id' => $reservation->reservation_id,
            'status_id' => $reservation->fresh()->status_id,
        ]);
    }

    private function validatePayload(Request $request, bool $includeIdentity = true): array|JsonResponse
    {
        $rules = [
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ];

        if ($includeIdentity) {
            $rules += [
                'name' => ['required', 'string', 'min:2', 'max:100'],
                'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
                'email' => ['nullable', 'email', 'max:190'],
            ];
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        return $validator->validated();
    }

    private function findReservation(int $reservationId): ?Reservation
    {
        return Reservation::query()
            ->where('reservation_id', $reservationId)
            ->where('location_id', (int)PrivateEnvironment::get('RESTOLINE_LOCATION_ID', '1'))
            ->first();
    }

    private function enforceReservationRateLimits(Request $request, array $data, string $action): ?JsonResponse
    {
        $ipKey = 'restoline:web-reservation:'.$action.':ip:'.sha1((string)$request->ip());
        $slotKey = 'restoline:web-reservation:'.$action.':slot:'.sha1(
            implode('|', [
                (string)($data['date'] ?? ''),
                (string)($data['time'] ?? ''),
                (string)($data['guests'] ?? ''),
            ]),
        );
        $phoneKey = null;
        if (!empty($data['phone'])) {
            $phoneKey = 'restoline:web-reservation:'.$action.':phone:'.sha1((string)$data['phone']);
        }

        $limits = [
            [$ipKey, 20, 'Trop de demandes depuis votre connexion. Réessayez dans 10 minutes.'],
            [$slotKey, 12, 'Trop de tentatives sur ce créneau. Réessayez dans 10 minutes.'],
        ];

        if ($phoneKey) {
            $limits[] = [$phoneKey, 5, 'Trop de réservations avec ce numéro. Réessayez dans 10 minutes.'];
        }

        foreach ($limits as [$key, $max, $message]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return response()->json(['ok' => false, 'message' => $message], 429);
            }
        }

        foreach ($limits as [$key]) {
            RateLimiter::hit($key, 600);
        }

        return null;
    }

    private function authorized(Request $request): bool
    {
        $expected = PrivateEnvironment::get('RESTOLINE_INTERNAL_API_TOKEN', '');
        $provided = (string)$request->bearerToken();

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }
}
