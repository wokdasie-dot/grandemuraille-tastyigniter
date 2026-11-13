<?php

namespace App\Services\Restoline;

use Carbon\Carbon;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Restoline\Content\Models\GroupBenefitSetting;

final class GroupBenefitSmsService
{
    public function sendIfEligible(Reservation $reservation): void
    {
        $setting = GroupBenefitSetting::current();
        $rule = $this->eligibleRule($reservation, $setting);
        if ($rule === null) {
            return;
        }

        $notificationKey = 'restoline:reservation:group-benefit-sms:'.$rule.':'.$reservation->reservation_id;
        if (!Cache::add($notificationKey, true, now()->addDays(45))) {
            return;
        }

        try {
            app(TwilioSmsService::class)->send(
                (string)$reservation->telephone,
                $rule === 'lunch' ? (string)$setting->lunch_sms_message : (string)$setting->sms_message,
            );
        } catch (\Throwable $exception) {
            Cache::forget($notificationKey);
            Log::warning('SMS avantage groupe non envoye.', [
                'reservation_id' => $reservation->reservation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function eligibleRule(Reservation $reservation, GroupBenefitSetting $setting): ?string
    {
        if (trim((string)$reservation->telephone) === '') {
            return null;
        }

        $date = $reservation->reserve_date;
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($reservation->reserve_date);
        }

        $time = substr((string)$reservation->reserve_time, 0, 5);
        $isDinner = $time >= '18:00';
        $isSundayLunch = $date->isSunday() && $time >= '12:00' && $time < '16:00';
        $isTuesdayToSaturdayLunch = $date->isoWeekday() >= 2
            && $date->isoWeekday() <= 6
            && $time >= '12:00'
            && $time < '16:00';

        if (
            $setting->lunch_status
            && (int)$reservation->guest_num >= (int)$setting->lunch_min_guests
            && $isTuesdayToSaturdayLunch
        ) {
            return 'lunch';
        }

        if (
            $setting->status
            && (int)$reservation->guest_num >= (int)$setting->min_guests
            && (($setting->send_for_dinner && $isDinner) || ($setting->send_for_sunday_lunch && $isSundayLunch))
        ) {
            return 'main';
        }

        return null;
    }
}
