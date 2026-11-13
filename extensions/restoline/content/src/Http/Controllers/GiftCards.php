<?php

declare(strict_types=1);

namespace Restoline\Content\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Illuminate\Http\RedirectResponse;
use Restoline\Content\Models\GiftCard;
use Restoline\Content\Services\GiftCardEmailService;
use Restoline\Content\Services\GiftCardService;

class GiftCards extends AdminController
{
    public array $implement = [ListController::class, FormController::class];

    public array $listConfig = ['list' => [
        'model' => GiftCard::class,
        'title' => 'Bons cadeaux',
        'emptyMessage' => 'Aucun bon cadeau.',
        'defaultSort' => ['created_at', 'DESC'],
        'configFile' => 'giftcard',
    ]];

    public array $formConfig = [
        'name' => 'Bon cadeau',
        'model' => GiftCard::class,
        'create' => ['title' => 'Créer un bon cadeau', 'redirect' => 'restoline/content/gift_cards/edit/{gift_card_id}', 'redirectClose' => 'restoline/content/gift_cards'],
        'edit' => ['title' => 'Bon cadeau', 'redirect' => 'restoline/content/gift_cards/edit/{gift_card_id}', 'redirectClose' => 'restoline/content/gift_cards'],
        'delete' => ['redirect' => 'restoline/content/gift_cards'],
        'configFile' => 'giftcard',
    ];

    protected null|string|array $requiredPermissions = 'Restoline.Content.GiftCards';

    public function __construct()
    {
        parent::__construct();
        AdminMenu::setContext('restoline-gift-cards');
    }

    public function formAfterSave($model): void
    {
        if (!$model instanceof GiftCard || !$model->wasRecentlyCreated) {
            return;
        }

        app(GiftCardService::class)->issue($model);
        if ($model->recipient_email) {
            try {
                app(GiftCardEmailService::class)->send($model);
                flash()->success('Bon créé et email envoyé au bénéficiaire.')->now();
            } catch (\Throwable $exception) {
                flash()->warning('Bon créé, mais email non envoyé : '.$exception->getMessage())->now();
            }
        }
    }

    public function formExtendFields($form): void
    {
        if ($form->model instanceof GiftCard && $form->model->exists) {
            $form->getField('initial_amount')->disabled = true;
        }
    }

    public function onSendGiftCardEmail(): void
    {
        $giftCard = GiftCard::findOrFail((int)post('gift_card_id'));
        app(GiftCardEmailService::class)->send($giftCard);
        flash()->success('Email du bon cadeau envoyé.')->now();
    }

    public function redeem(): mixed
    {
        $giftCard = $this->findGiftCard();
        if (request()->isMethod('post')) {
            $validated = request()->validate([
                'gift_card_id' => ['required', 'integer'],
                'mode' => ['required', 'in:full,partial'],
                'amount' => ['nullable', 'numeric', 'min:0.01'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);
            $giftCard = GiftCard::findOrFail((int)$validated['gift_card_id']);
            $amount = $validated['mode'] === 'full'
                ? (float)$giftCard->balance_amount
                : (float)($validated['amount'] ?? 0);
            app(GiftCardService::class)->redeem($giftCard, $amount, $validated['note'] ?: null);

            return redirect(admin_url('restoline/content/gift_cards/redeem?code='.$giftCard->code))
                ->with('success', 'Le solde du bon cadeau a été mis à jour.');
        }

        return $this->makeView('redeem', [
            'giftCard' => $giftCard,
            'selectFullAmount' => trim((string)request()->query('token')) !== '',
        ]);
    }

    public function print(): mixed
    {
        $giftCard = GiftCard::findOrFail((int)request()->query('gift_card_id'));
        $pdf = app(GiftCardEmailService::class)->printablePdf($giftCard);
        $filename = 'cheque-cadeau-'.strtolower($giftCard->code).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => (string)strlen($pdf),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function findGiftCard(): ?GiftCard
    {
        $token = trim((string)request()->query('token'));
        $code = strtoupper(trim((string)request()->query('code')));

        if ($token !== '') {
            return GiftCard::query()->where('qr_token', $token)->first();
        }

        return $code !== '' ? GiftCard::query()->where('code', $code)->first() : null;
    }
}
