<?php

namespace App\Services\Restoline;

use Carbon\Carbon;
use Igniter\Local\Models\Location;
use Igniter\Reservation\Classes\BookingManager;
use Igniter\Reservation\Models\DiningTable;
use Igniter\Reservation\Models\Reservation;
use Igniter\Admin\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class VoiceReservationService
{
    private const STATE_TTL_MINUTES = 20;
    private const ANNUAL_LEAVE_START = '2026-07-06';
    private const ANNUAL_LEAVE_END = '2026-08-03';
    private const ANNUAL_REOPEN_DATE = '2026-08-04';

    private const SERVICE_SLOTS = [
        // Les réservations téléphoniques et SMS doivent proposer les mêmes
        // créneaux de 15 minutes que le formulaire du site.
        'lunch' => ['12:00', '12:15', '12:30', '12:45', '13:00', '13:15', '13:30', '13:45', '14:00'],
        'dinner' => ['18:00', '18:15', '18:30', '18:45', '19:00', '19:15', '19:30', '19:45', '20:00', '20:15', '20:30', '20:45', '21:00', '21:15', '21:30', '21:45'],
    ];

    public function start(string $callSid, string $phone): array
    {
        return $this->store($callSid, [
            'phone' => $phone,
            'started_at' => now()->toIso8601String(),
            'steps' => 0,
        ]);
    }

    public function touch(string $callSid, string $phone = ''): array
    {
        $state = $this->state($callSid);
        if (empty($state['started_at'])) {
            $state['started_at'] = now()->toIso8601String();
        }
        if ($phone !== '' && empty($state['phone'])) {
            $state['phone'] = $phone;
        }
        $state['steps'] = (int)($state['steps'] ?? 0) + 1;

        return $this->store($callSid, $state);
    }

    public function state(string $callSid): array
    {
        return Cache::get($this->cacheKey($callSid), []);
    }

    public function merge(string $callSid, array $values): array
    {
        return $this->store($callSid, array_merge($this->state($callSid), $values));
    }

    public function availableSlots(string $date, string $service, ?int $guests = null): array
    {
        if ($this->isAnnualLeaveDate($date)) {
            return [];
        }

        $location = $this->location();
        $manager = app(BookingManager::class);
        $manager->useLocation($location);
        $carbonDate = Carbon::createFromFormat('Y-m-d', $date, $location->location_timezone ?? config('app.timezone'));
        $slots = $manager->makeTimeSlots($carbonDate);

        $serviceSlots = self::SERVICE_SLOTS[$service] ?? [];
        $slots = $slots->filter(
            fn($slot): bool => in_array($slot->format('H:i'), $serviceSlots, true),
        );

        if ($guests === null) {
            return $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->values()
                ->take(9)
                ->all();
        }

        $largestTable = (int)DiningTable::query()
            ->whereHasLocation($location->location_id)
            ->where('is_enabled', true)
            ->max('max_capacity');

        // Les groupes peuvent occuper plusieurs tables. Le placement final
        // des groupes est confirme par le restaurant dans le tableau de bord.
        if ($guests > $largestTable) {
            return $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->values()
                ->take(9)
                ->all();
        }

        $fullyBooked = $manager->isTimeslotsFullyBookedOn($slots, $carbonDate, $guests);
        return $slots
            ->reject(fn($slot, $timestamp) => array_key_exists($timestamp, $fullyBooked))
            ->map(fn($slot) => $slot->format('H:i'))
            ->values()
            ->take(9)
            ->all();
    }

    public function annualLeaveAnnouncement(): string
    {
        $today = now(config('app.timezone'))->startOfDay();
        $announcementStart = Carbon::create(2026, 7, 1, 0, 0, 0, config('app.timezone'))->startOfDay();
        $announcementEnd = Carbon::createFromFormat('Y-m-d', self::ANNUAL_LEAVE_END, config('app.timezone'))->endOfDay();
        if (!$today->betweenIncluded($announcementStart, $announcementEnd)) {
            return '';
        }

        return 'Notre restaurant est fermer pour congés annuels. La réouverture est prévue le mardi 4 août a partir de midi. ';
    }

    public function annualLeaveUnavailableMessage(): string
    {
        return 'Le restaurant est fermer pour congé annuel du 6 juillet 2026 au 3 août 2026 inclus. La réouverture est prévue le mardi 4 août a partir de midi.';
    }

    public function isAnnualLeaveDate(string $date): bool
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date, config('app.timezone'))->startOfDay();
            $start = Carbon::createFromFormat('Y-m-d', self::ANNUAL_LEAVE_START, config('app.timezone'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', self::ANNUAL_LEAVE_END, config('app.timezone'))->endOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $day->betweenIncluded($start, $end);
    }

    public function createReservation(array $state): Reservation
    {
        foreach (['date', 'time', 'guests', 'name', 'phone'] as $required) {
            if (empty($state[$required])) {
                throw new RuntimeException("Donnee de reservation manquante: {$required}");
            }
        }

        $location = $this->location();
        $manager = app(BookingManager::class);
        $manager->useLocation($location);

        $dateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $state['date'].' '.$state['time'],
            $location->location_timezone ?? config('app.timezone'),
        );

        $slots = $this->availableSlots($state['date'], $state['service'], (int)$state['guests']);
        if (!in_array($state['time'], $slots, true)) {
            throw new RuntimeException('Ce creneau vient de devenir indisponible.');
        }

        $reservation = $manager->getReservation();
        $reservation->guest_num = (int)$state['guests'];
        $reservation->first_name = trim($state['name']);
        $reservation->last_name = '';
        $reservation->email = trim((string)($state['email'] ?? ''));
        $reservation->telephone = $state['phone'];
        $reservation->comment = $state['comment']
            ?? 'Reservation recue par le repondeur telephonique Restoline.';
        $reservation->reserve_date = $dateTime->format('Y-m-d');
        $reservation->reserve_time = $dateTime->format('H:i:s');
        $reservation->duration = $location->getReservationStayTime();
        $statusId = (int)($state['status_id'] ?? setting('default_reservation_status'));
        $reservation->status = $statusId;
        $reservation->save();

        $status = Status::find($statusId);
        $reservation->addStatusHistory($status, ['notify' => false]);

        return $reservation->fresh();
    }

    public function sendAcknowledgement(Reservation $reservation): void
    {
        $date = $reservation->reserve_date->format('d/m/Y');
        $time = Carbon::parse($reservation->reserve_time)->format('H:i');
        $this->createManagementLink($reservation);
        $name = trim((string)$reservation->first_name.' '.(string)$reservation->last_name);
        $hasName = $name !== '' && !in_array(mb_strtolower($name), ['client', 'client sms'], true);
        $identity = $hasName ? ' au nom de '.mb_substr($name, 0, 35) : ' liee a ce numero';
        $message = sprintf(
            "La Grande Muraille de Chine: Reservation confirmee%s. %d pers - %s a %s. Gerer: https://nouveau.grandemuraillespa.be/reservation/gerer",
            $identity,
            (int)$reservation->guest_num,
            $date,
            $time,
        );

        try {
            app(TwilioSmsService::class)->send($reservation->telephone, $message);
        } catch (\Throwable $exception) {
            Log::warning('SMS de reservation telephonique non envoye.', [
                'reservation_id' => $reservation->reservation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendWebsiteLink(string $phone): void
    {
        app(TwilioSmsService::class)->send(
            $phone,
            "La Grande Muraille de Chine : reservation en ligne sur https://nouveau.grandemuraillespa.be/grande-muraille/reservation",
        );
    }

    public function sendSmsReservationInstructions(string $phone): void
    {
        $message = implode("\n", [
            "La Grande Muraille de Chine",
            "",
            "Pour reserver par SMS, repondez simplement avec :",
            "votre nom, la date, l'heure, le nombre de personnes et une remarque si besoin.",
            "",
            "Exemple :",
            "Dupont 28/06/2026 19h30 4 personnes anniversaire",
            "",
            "Pour modifier ou annuler votre reservation, utilisez :",
            "https://nouveau.grandemuraillespa.be/reservation/gerer",
            "",
            "Pour appeler le restaurant : 087 22 08 88.",
            "Vos donnees servent uniquement a traiter votre reservation.",
        ]);

        app(TwilioSmsService::class)->send($phone, $message);
    }

    private function createManagementLink(Reservation $reservation): string
    {
        $token = PrivateEnvironment::get('RESTOLINE_INTERNAL_API_TOKEN', '');
        $endpoint = PrivateEnvironment::get(
            'RESTOLINE_WOK_MANAGEMENT_API_URL',
            'https://nouveau.grandemuraillespa.be/',
        );

        if ($token === '' || $endpoint === '') {
            return 'https://nouveau.grandemuraillespa.be/reservation/gerer';
        }

        $handle = curl_init($endpoint);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'tasty_reservation_id' => $reservation->reservation_id,
                'name' => trim($reservation->first_name.' '.$reservation->last_name),
                'phone' => $reservation->telephone,
                'email' => $reservation->email,
                'guests' => $reservation->guest_num,
                'date' => $reservation->reserve_date->format('Y-m-d'),
                'time' => Carbon::parse($reservation->reserve_time)->format('H:i'),
            ], JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$token,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $response = is_string($body) ? json_decode($body, true) : null;

        if ($status >= 200 && $status < 300 && is_array($response) && !empty($response['manage_url'])) {
            return (string)$response['manage_url'];
        }

        Log::warning('Lien de gestion telephonique non cree.', [
            'reservation_id' => $reservation->reservation_id,
            'status' => $status,
        ]);

        return 'https://nouveau.grandemuraillespa.be/reservation/gerer';
    }

    public function clear(string $callSid): void
    {
        Cache::forget($this->cacheKey($callSid));
    }

    private function store(string $callSid, array $state): array
    {
        Cache::put($this->cacheKey($callSid), $state, now()->addMinutes(self::STATE_TTL_MINUTES));

        return $state;
    }

    private function location(): Location
    {
        $locationId = (int)PrivateEnvironment::get('RESTOLINE_LOCATION_ID', '1');
        $location = Location::find($locationId);
        if (!$location) {
            throw new RuntimeException('Restaurant introuvable.');
        }

        return $location;
    }

    private function cacheKey(string $callSid): string
    {
        return 'restoline:voice:'.preg_replace('/[^A-Za-z0-9_-]/', '', $callSid);
    }
}
