<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Carbon\Carbon;
use Igniter\Reservation\Classes\BookingManager;
use Igniter\Reservation\Models\DiningTable;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Restoline\Content\Services\ReservationOtpService;
use Restoline\Content\Models\ReservationReminder;
use Restoline\Content\Services\TurnstileService;
use Throwable;

class ManageReservationController
{
    public function index(Request $request): Response
    {
        $reservation = $this->authorizedReservation($request);

        return response()->view('restoline.content::manage-reservation', [
            'codeRequested' => $request->session()->has('restoline_manage_code_requested'),
            'reservation' => $reservation,
            'maxDate' => $reservation
                ? now()->addDays($reservation->location->getMaxReservationAdvanceTime())->toDateString()
                : null,
        ]);
    }

    public function directAccess(Request $request, string $hash): RedirectResponse
    {
        $canceledStatusId = $this->canceledStatusId();
        $reservation = Reservation::query()
            ->whereHash($hash)
            ->where('location_id', 1)
            ->whereDate('reserve_date', '>=', now()->toDateString())
            ->where('status_id', '!=', $canceledStatusId)
            ->first();

        if (!$reservation) {
            return redirect()->route('restoline.manage.index')
                ->with('status', 'Cette réservation est déjà annulée ou n’est plus disponible en ligne.');
        }

        $request->session()->forget('restoline_manage_code_requested');
        $request->session()->put('restoline_manage_reservation', [
            'reservation_id' => $reservation->reservation_id,
            'authorized_until' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('restoline.manage.index')
            ->with('status', 'Acces autorise. Vous pouvez modifier votre reservation.');
    }

    public function reminderAccess(Request $request, string $token): RedirectResponse
    {
        $reminder = ReservationReminder::query()->where('token', $token)->first();
        $reservation = $reminder
            ? Reservation::query()
                ->whereKey($reminder->reservation_id)
                ->where('location_id', 1)
                ->whereDate('reserve_date', '>=', now()->toDateString())
                ->where('status_id', '!=', $this->canceledStatusId())
                ->first()
            : null;

        if (!$reminder || !$reservation) {
            return redirect()->route('restoline.manage.index')
                ->with('status', 'Ce lien de rappel est expiré ou la réservation a déjà été annulée.');
        }

        if (!$reminder->opened_at) {
            $reminder->opened_at = now();
            $reminder->save();
        }

        $request->session()->forget('restoline_manage_code_requested');
        $request->session()->put('restoline_manage_reservation', [
            'reservation_id' => $reservation->reservation_id,
            'authorized_until' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('restoline.manage.index')
            ->with('status', 'Voici votre réservation. Vous pouvez vérifier ou modifier le nombre de personnes.');
    }

    public function sendCode(
        Request $request,
        TurnstileService $turnstile,
        ReservationOtpService $notifications,
    ): RedirectResponse
    {
        if (!$turnstile->verifyRequest($request)) {
            return back()
                ->withErrors(['identifier' => 'La vérification anti-spam a échoué. Réessayez.'])
                ->withInput();
        }

        $data = $request->validate([
            'identifier' => ['required', 'email:rfc,dns', 'max:190'],
        ], [
            'identifier.required' => 'Saisissez votre adresse e-mail.',
            'identifier.email' => 'Saisissez une adresse e-mail valide.',
        ]);

        $email = mb_strtolower(trim($data['identifier']));
        $rateKey = 'restoline:manage:send:'.sha1($request->ip().'|'.$email);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return back()->withErrors(['identifier' => 'Trop de demandes. Réessayez dans 10 minutes.']);
        }

        RateLimiter::hit($rateKey, 600);

        $reservations = Reservation::query()
            ->where('location_id', 1)
            ->whereDate('reserve_date', '>=', now()->toDateString())
            ->where('status_id', '!=', $this->canceledStatusId())
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('reserve_date')
            ->orderBy('reserve_time')
            ->get();

        $reservation = $reservations->first();
        if ($reservations->isEmpty() || !$reservation->email) {
            return back()->withErrors([
                'identifier' => 'Aucune réservation future ne correspond à ces informations.',
            ]);
        }

        $code = (string)random_int(1000, 9999);
        Cache::put($this->otpKey($request), [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'reservation_ids' => $reservations->pluck('reservation_id')->all(),
        ], 600);

        try {
            $notifications->sendManagementAccessCode((string)$reservation->email, $code);
        } catch (Throwable) {
            return back()->withErrors([
                'identifier' => 'L’e-mail n’a pas pu être envoyé. Réessayez plus tard.',
            ]);
        }

        $request->session()->put('restoline_manage_code_requested', true);

        return back()->with('status', 'Un code à 4 chiffres vient de vous être envoyé par e-mail.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:4'],
        ], [
            'code.required' => 'Saisissez le code reçu par e-mail.',
            'code.digits' => 'Le code doit contenir exactement 4 chiffres.',
        ]);

        $payload = Cache::get($this->otpKey($request));
        if (!$payload) {
            return back()->withErrors(['code' => 'Le code a expiré. Demandez un nouveau code.']);
        }

        $attempts = (int)($payload['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($this->otpKey($request));

            return back()->withErrors(['code' => 'Trop de tentatives. Demandez un nouveau code.']);
        }

        if (!Hash::check($data['code'], $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->otpKey($request), $payload, 600);

            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        $reservation = Reservation::query()
            ->whereIn('reservation_id', $payload['reservation_ids'] ?? [])
            ->whereDate('reserve_date', '>=', now()->toDateString())
            ->orderBy('reserve_date')
            ->orderBy('reserve_time')
            ->first();

        Cache::forget($this->otpKey($request));
        $request->session()->forget('restoline_manage_code_requested');

        abort_unless($reservation, 404);

        $request->session()->put('restoline_manage_reservation', [
            'reservation_id' => $reservation->reservation_id,
            'authorized_until' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('restoline.manage.index')
            ->with('status', 'Accès autorisé. Vous pouvez modifier votre réservation.');
    }

    public function update(
        Request $request,
        BookingManager $manager,
        ReservationOtpService $notifications,
    ): RedirectResponse {
        $reservation = $this->authorizedReservation($request);
        abort_unless($reservation, 403);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'guest_num' => ['required', 'integer', 'min:1', 'max:30'],
            'reserve_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.now()
                    ->addDays($reservation->location->getMaxReservationAdvanceTime())
                    ->toDateString(),
            ],
            'reserve_time' => ['required', 'date_format:H:i'],
        ], [
            'customer_name.required' => 'Saisissez le nom de la réservation.',
            'guest_num.required' => 'Saisissez le nombre de personnes.',
            'guest_num.integer' => 'Le nombre de personnes doit être un nombre entier.',
            'guest_num.min' => 'La réservation doit comprendre au moins une personne.',
            'guest_num.max' => 'Pour plus de 30 personnes, contactez directement le restaurant.',
            'reserve_date.required' => 'Choisissez une date.',
            'reserve_date.after_or_equal' => 'La date de réservation ne peut pas être passée.',
            'reserve_date.before_or_equal' => 'Cette date est trop éloignée.',
            'reserve_time.required' => 'Choisissez une heure.',
            'reserve_time.date_format' => 'Utilisez une heure au format 24 h.',
        ]);

        $dateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['reserve_date'].' '.$data['reserve_time'],
            config('app.timezone'),
        );
        $currentDateTime = $reservation->reservation_datetime;
        $dateTimeChanged = !$currentDateTime || !$currentDateTime->equalTo($dateTime);

        $manager->useLocation($reservation->location);
        if ($dateTimeChanged) {
            $availableTimes = collect($manager->makeTimeSlots($dateTime->copy()->startOfDay()))
                ->map(fn($slot): string => $slot->format('H:i'));

            if (!$availableTimes->contains($dateTime->format('H:i'))) {
                throw ValidationException::withMessages([
                    'reserve_time' => 'Ce créneau ne fait pas partie des horaires de réservation disponibles.',
                ]);
            }
        }

        $duration = (int)($reservation->duration ?: $reservation->location->getReservationStayTime());
        $table = $this->findAvailableTable(
            $reservation,
            $dateTime,
            (int)$data['guest_num'],
            $duration,
        );

        if (!$table) {
            throw ValidationException::withMessages([
                'reserve_time' => 'Ce créneau n’est plus disponible pour ce nombre de personnes.',
            ]);
        }

        [$firstName, $lastName] = $this->splitName($data['customer_name']);

        DB::transaction(function() use (
            $reservation,
            $firstName,
            $lastName,
            $data,
            $dateTime,
            $duration,
            $table,
        ): void {
            $reservation->first_name = $firstName;
            $reservation->last_name = $lastName;
            $reservation->guest_num = (int)$data['guest_num'];
            $reservation->reserve_date = $dateTime->format('Y-m-d');
            $reservation->reserve_time = $dateTime->format('H:i:s');
            $reservation->duration = $duration;
            $reservation->save();
            $reservation->addReservationTables([$table->id]);
        });

        $reservation->refresh();
        $notifications->sendModificationNotifications($reservation);

        return redirect()->route('restoline.manage.index')
            ->with('status', 'Votre réservation a bien été modifiée. Une confirmation vous a été envoyée.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $reservation = $this->authorizedReservation($request);
        if (!$reservation) {
            $request->session()->forget('restoline_manage_reservation');

            return redirect()->route('restoline.manage.index')
                ->with('status', 'Cette réservation est déjà annulée ou n’est plus disponible en ligne.');
        }

        $cancelLockKey = 'restoline:manage:cancel:'.$reservation->reservation_id;
        if (!Cache::add($cancelLockKey, true, now()->addSeconds(45))) {
            return redirect()->route('restoline.manage.index')
                ->with('status', 'Annulation déjà en cours. Merci de patienter quelques secondes.');
        }

        try {
            if ((int)$reservation->status_id === $this->canceledStatusId()) {
                $request->session()->forget('restoline_manage_reservation');

                return redirect()->route('restoline.manage.index')
                    ->with('status', 'Votre réservation était déjà annulée.');
            }

            if (!$reservation->reservation_datetime || $reservation->reservation_datetime->isPast()) {
                return back()->withErrors([
                    'reservation' => 'Cette réservation ne peut plus être annulée en ligne. Contactez le restaurant.',
                ]);
            }

            if (!$reservation->markAsCanceled(['notify' => true])) {
                return back()->withErrors([
                    'reservation' => 'L’annulation n’a pas pu être enregistrée.',
                ]);
            }

            $request->session()->forget('restoline_manage_reservation');

            return redirect()->route('restoline.manage.index')
                ->with('status', 'Votre réservation a bien été annulée. Une confirmation d’annulation vous a été envoyée.');
        } finally {
            Cache::forget($cancelLockKey);
        }
    }

    private function otpKey(Request $request): string
    {
        return 'restoline:manage:otp:'.sha1($request->session()->getId());
    }

    private function authorizedReservation(Request $request): ?Reservation
    {
        $access = $request->session()->get('restoline_manage_reservation');
        if (
            !is_array($access)
            || empty($access['reservation_id'])
            || (int)($access['authorized_until'] ?? 0) < now()->timestamp
        ) {
            $request->session()->forget('restoline_manage_reservation');

            return null;
        }

        return Reservation::query()
            ->with(['location', 'tables'])
            ->whereKey((int)$access['reservation_id'])
            ->where('location_id', 1)
            ->whereDate('reserve_date', '>=', now()->toDateString())
            ->where('status_id', '!=', $this->canceledStatusId())
            ->first();
    }

    private function canceledStatusId(): int
    {
        return \Restoline\Content\Support\ReservationStatusIds::canceled();
    }

    private function findAvailableTable(
        Reservation $reservation,
        Carbon $dateTime,
        int $guests,
        int $duration,
    ): ?DiningTable {
        return DiningTable::query()
            ->whereIsReservable()
            ->whereIsAvailableAt($reservation->location_id)
            ->whereCanAccommodate($guests)
            ->whereDoesntHave('reservations', function($query) use (
                $reservation,
                $dateTime,
                $duration,
            ): void {
                $query
                    ->where('reservations.reservation_id', '!=', $reservation->reservation_id)
                    ->whereNotIn('status_id', [0, $this->canceledStatusId()])
                    ->where(function($query) use ($dateTime, $duration): void {
                        $query
                            ->where(function($query) use ($dateTime): void {
                                $query->whereBetweenStayTime($dateTime->copy()->addMinute());
                            })
                            ->orWhere(function($query) use ($dateTime, $duration): void {
                                $query->whereBetweenStayTime(
                                    $dateTime->copy()->addMinutes(max(1, $duration - 1)),
                                );
                            });
                    });
            })
            ->select('dining_tables.*')
            ->orderBy('dining_tables.priority', 'desc')
            ->first();
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }
}
