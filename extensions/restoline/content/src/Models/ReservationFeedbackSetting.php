<?php

declare(strict_types=1);

namespace Restoline\Content\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Concerns\Switchable;

class ReservationFeedbackSetting extends Model
{
    use Switchable;
    use Validation;

    protected $table = 'restoline_reservation_feedback_settings';

    protected $primaryKey = 'setting_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'enabled' => 'boolean',
        'sample_percent' => 'integer',
        'days_after_visit' => 'integer',
        'cooldown_days' => 'integer',
        'google_review_enabled' => 'boolean',
    ];

    public $rules = [
        ['enabled', 'Actif', 'required|boolean'],
        ['sample_percent', 'Proportion', 'required|integer|in:20,40,60,100'],
        ['days_after_visit', 'Délai', 'required|integer|min:1|max:7'],
        ['cooldown_days', 'Pause par client', 'required|integer|min:0|max:365'],
        ['google_review_enabled', 'Avis Google', 'required|boolean'],
        ['email_subject', "Sujet de l'email", 'required|string|max:190'],
        ['email_heading', "Titre de l'email", 'required|string|max:190'],
        ['email_message', "Texte de l'email", 'required|string|max:1500'],
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::create([
            'enabled' => true,
            'sample_percent' => 40,
            'days_after_visit' => 1,
            'cooldown_days' => 60,
            'google_review_enabled' => true,
            'email_subject' => "Votre avis nous aide - La Grande Muraille de Chine",
            'email_heading' => 'Comment s’est passée votre visite ?',
            'email_message' => "Merci d’être venu chez La Grande Muraille de Chine. Votre retour nous aide à améliorer l’accueil, le repas et notre système de réservation. Le questionnaire prend moins d’une minute.",
        ]);
    }

    public function getSamplePercentOptions(): array
    {
        return [
            20 => '1 réservation sur 5 (20 %)',
            40 => '2 réservations sur 5 (40 %)',
            60 => '3 réservations sur 5 (60 %)',
            100 => 'Toutes les réservations (100 %)',
        ];
    }
}
