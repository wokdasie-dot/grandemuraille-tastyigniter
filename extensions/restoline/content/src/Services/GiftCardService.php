<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Restoline\Content\Models\GiftCard;
use Restoline\Content\Models\GiftCardTransaction;
use RuntimeException;

class GiftCardService
{
    public function ensureQrImage(GiftCard $giftCard): string
    {
        $directory = public_path('storage/media/uploads/gift-cards');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.$giftCard->code.'.png';

        if (!File::exists($path)) {
            $result = new Builder(
                writer: new PngWriter(),
                data: $this->redeemUrl($giftCard),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 420,
                margin: 12,
            )->build();

            $result->saveToFile($path);
        }

        return $path;
    }

    public function qrImageUrl(GiftCard $giftCard): string
    {
        $this->ensureQrImage($giftCard);

        return url('/storage/media/uploads/gift-cards/'.rawurlencode($giftCard->code).'.png');
    }

    public function redeemUrl(GiftCard $giftCard): string
    {
        return admin_url('restoline/content/gift_cards/redeem?token='.$giftCard->qr_token);
    }

    public function issue(GiftCard $giftCard): void
    {
        $this->ensureQrImage($giftCard);

        GiftCardTransaction::query()->firstOrCreate([
            'gift_card_id' => $giftCard->getKey(),
            'type' => 'issue',
        ], [
            'amount' => $giftCard->initial_amount,
            'balance_before' => 0,
            'balance_after' => $giftCard->balance_amount,
            'staff_name' => $this->staffName(),
            'note' => 'Création du bon cadeau',
        ]);
    }

    public function redeem(GiftCard $giftCard, float $amount, ?string $note = null): GiftCard
    {
        if ($amount <= 0) {
            throw new RuntimeException('Le montant doit être supérieur à zéro.');
        }

        return DB::transaction(function() use ($giftCard, $amount, $note): GiftCard {
            $locked = GiftCard::query()->lockForUpdate()->findOrFail($giftCard->getKey());

            if (!$locked->isRedeemable()) {
                throw new RuntimeException('Ce bon cadeau ne peut pas être utilisé.');
            }

            $before = round((float)$locked->balance_amount, 2);
            $amount = round($amount, 2);
            if ($amount > $before) {
                throw new RuntimeException('Le montant demandé dépasse le solde disponible.');
            }

            $after = round($before - $amount, 2);
            $locked->balance_amount = $after;
            $locked->save();

            GiftCardTransaction::query()->create([
                'gift_card_id' => $locked->getKey(),
                'type' => 'redeem',
                'amount' => -$amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'staff_name' => $this->staffName(),
                'note' => $note,
            ]);

            return $locked;
        });
    }

    private function staffName(): ?string
    {
        $user = AdminAuth::user();

        return $user ? trim((string)($user->first_name.' '.$user->last_name)) : null;
    }
}
