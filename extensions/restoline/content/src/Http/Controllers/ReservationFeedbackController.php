<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use App\Services\Restoline\PrivateEnvironment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\ReservationFeedback;
use Restoline\Content\Models\ReservationFeedbackSetting;
use Restoline\Content\Services\ReservationFeedbackService;

class ReservationFeedbackController
{
    public function show(string $token): Response
    {
        $feedback = $this->findFeedback($token);
        if (!$feedback->opened_at) {
            $feedback->opened_at = now();
            $feedback->save();
        }

        return response()->view('restoline.content::reservation-feedback', [
            'feedback' => $feedback,
            'googleUrl' => route('restoline.reservation-feedback.google', ['token' => $feedback->token]),
            'googleEnabled' => ReservationFeedbackSetting::current()->google_review_enabled,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'visit_answer' => ['required', 'in:ok,problem'],
            'reservation_answer' => ['required', 'in:ok,bug'],
            'reservation_channel' => ['required', 'in:online,phone,unknown'],
            'problem_type' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:1500'],
        ]);

        $feedback = $this->findFeedback($token);
        $shouldNotify = false;

        DB::transaction(function() use ($feedback, $data, &$shouldNotify): void {
            $feedback = ReservationFeedback::query()
                ->whereKey($feedback->feedback_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($feedback->responded_at) {
                return;
            }

            $feedback->fill([
                'visit_answer' => $data['visit_answer'],
                'reservation_answer' => $data['reservation_answer'],
                'reservation_channel' => $data['reservation_channel'],
                'problem_type' => $data['problem_type'] ?? null,
                'message' => $data['message'] ?? null,
                'responded_at' => now(),
            ]);
            $feedback->save();
            $shouldNotify = true;
        });

        if ($shouldNotify) {
            $this->notifyRestaurant($feedback->fresh());
        }

        return redirect()
            ->route('restoline.reservation-feedback.show', ['token' => $feedback->token])
            ->with('status', 'Merci, votre retour a bien été envoyé.');
    }

    public function google(string $token, ReservationFeedbackService $feedbacks): RedirectResponse
    {
        $feedback = $this->findFeedback($token);
        if (!$feedback->google_clicked_at) {
            $feedback->google_clicked_at = now();
            $feedback->save();
        }

        return redirect()->away($feedbacks->googleReviewUrl($feedback));
    }

    private function findFeedback(string $token): ReservationFeedback
    {
        return ReservationFeedback::query()->where('token', $token)->firstOrFail();
    }

    private function notifyRestaurant(ReservationFeedback $feedback): void
    {
        $to = trim((string)PrivateEnvironment::get(
            'RESTAURANT_NOTIFICATION_EMAIL',
            env('RESTAURANT_NOTIFICATION_EMAIL', 'jinzhu1031@gmail.com'),
        ));
        if ($to === '') {
            return;
        }

        $subject = $feedback->reservation_answer === 'bug' || $feedback->visit_answer === 'problem'
            ? 'Retour client avec problème - La Grande Muraille de Chine'
            : 'Nouveau retour client - La Grande Muraille de Chine';

        $lines = [
            "La Grande Muraille de Chine",
            '',
            'Un client a répondu au sondage après réservation.',
            '',
            'Réservation: #'.$feedback->reservation_id,
            'Client: '.($feedback->customer_name ?: '-'),
            'Téléphone: '.($feedback->telephone ?: '-'),
            'Email: '.($feedback->email ?: '-'),
            'Date venue: '.($feedback->reserve_date?->format('d/m/Y') ?: '-').' à '.(substr((string)$feedback->reserve_time, 0, 5) ?: '-'),
            'Canal réservation: '.$feedback->reservation_channel_label,
            'Retour visite: '.$feedback->visit_answer_label,
            'Retour réservation: '.$feedback->reservation_answer_label,
            'Type de problème: '.($feedback->problem_type ?: '-'),
            '',
            'Message client:',
            $feedback->message ?: '-',
            '',
            'Suivi dashboard:',
            admin_url('restoline/content/reservation_feedbacks/edit/'.$feedback->feedback_id),
        ];

        try {
            Mail::raw(implode("\n", $lines), fn($message) => $message->to($to)->subject($subject));
        } catch (\Throwable $exception) {
            Log::warning('Email retour client non envoyé au restaurant.', [
                'feedback_id' => $feedback->feedback_id,
                'reservation_id' => $feedback->reservation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
