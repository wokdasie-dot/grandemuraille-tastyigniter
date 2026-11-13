<?php

use App\Http\Controllers\Restoline\ResendWebhookController;
use App\Http\Controllers\Restoline\WebReservationController;
use Illuminate\Support\Facades\Route;

Route::post('restoline/resend/webhook/{token}', ResendWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('restoline.resend.webhook');

Route::prefix('restoline/web/reservations')
    ->middleware('throttle:60,1')
    ->name('restoline.web.reservations.')
    ->group(function (): void {
        Route::post('/', [WebReservationController::class, 'store'])->name('store');
        Route::patch('/{reservationId}', [WebReservationController::class, 'update'])->name('update');
        Route::delete('/{reservationId}', [WebReservationController::class, 'cancel'])->name('cancel');
    });
