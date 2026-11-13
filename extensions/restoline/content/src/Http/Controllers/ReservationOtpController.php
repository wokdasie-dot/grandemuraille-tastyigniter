<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Restoline\Content\Services\ReservationOtpService;
use RuntimeException;

class ReservationOtpController
{
    public function verify(
        Request $request,
        string $hash,
        ReservationOtpService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'code' => ['required', 'digits:4'],
        ], [
            'code.required' => 'Saisissez le code reçu par SMS.',
            'code.digits' => 'Le code doit contenir exactement 4 chiffres.',
        ]);

        $reservation = $service->findReservation($hash);
        abort_unless($reservation, 404);

        if (!$service->requiresPhoneValidation($reservation)) {
            return back()->with('otp_success', true);
        }

        try {
            $service->verify($reservation, $data['code']);

            return back()->with('otp_success', true);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }
    }

    public function resend(
        string $hash,
        ReservationOtpService $service,
    ): RedirectResponse {
        $reservation = $service->findReservation($hash);
        abort_unless($reservation, 404);

        if (!$service->requiresPhoneValidation($reservation)) {
            return back()->with('otp_success', true);
        }

        try {
            $service->sendCode($reservation);

            return back()->with('otp_status', 'Un nouveau code à 4 chiffres vient d’être envoyé.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }
    }
}
