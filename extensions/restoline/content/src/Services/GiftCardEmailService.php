<?php

declare(strict_types=1);

namespace Restoline\Content\Services;

use App\Services\Restoline\PrivateEnvironment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Restoline\Content\Models\GiftCard;
use RuntimeException;

class GiftCardEmailService
{
    public function send(GiftCard $giftCard): void
    {
        if (!$giftCard->recipient_email) {
            throw new RuntimeException('Ajoutez l’email du bénéficiaire avant l’envoi.');
        }

        $html = $this->html($giftCard);
        $pdf = $this->printablePdf($giftCard);
        $filename = 'cheque-cadeau-'.strtolower($giftCard->code).'.pdf';
        $subject = "Votre chèque cadeau La Grande Muraille de Chine";

        try {
            $this->sendViaResend($giftCard, $subject, $html, $pdf, $filename);
        } catch (\Throwable) {
            Mail::mailer('wokdasie')->html($html, fn($message) => $message
                ->from('jinzhu1031@gmail.com', "La Grande Muraille de Chine")
                ->to($giftCard->recipient_email, $giftCard->recipient_name ?: null)
                ->subject($subject)
                ->attachData($pdf, $filename, ['mime' => 'application/pdf']));
        }

        $giftCard->emailed_at = now();
        $giftCard->save();
    }

    private function sendViaResend(GiftCard $giftCard, string $subject, string $html, string $pdf, string $filename): void
    {
        $apiKey = (string)PrivateEnvironment::get('RESEND_API_KEY', '');
        if ($apiKey === '') {
            throw new RuntimeException('Clé API Resend absente.');
        }

        $fromEmail = (string)PrivateEnvironment::get('RESEND_FROM_EMAIL', 'jinzhu1031@gmail.com');
        $fromName = (string)PrivateEnvironment::get('RESEND_FROM_NAME', "La Grande Muraille de Chine");
        $response = Http::withToken($apiKey)->acceptJson()->timeout(20)->post('https://api.resend.com/emails', [
            'from' => $fromName.' <'.$fromEmail.'>',
            'to' => [$giftCard->recipient_name ? $giftCard->recipient_name.' <'.$giftCard->recipient_email.'>' : $giftCard->recipient_email],
            'subject' => $subject,
            'html' => $html,
            'attachments' => [[
                'filename' => $filename,
                'content' => base64_encode($pdf),
            ]],
        ]);

        if (!$response->successful() || !(string)$response->json('id')) {
            throw new RuntimeException('Resend HTTP '.$response->status());
        }
    }

    private function html(GiftCard $giftCard): string
    {
        $qrUrl = app(GiftCardService::class)->qrImageUrl($giftCard);
        $logoUrl = url('/vendor/restoline-wokdasie/images/logo-wokdasie-square.jpg');
        $name = e($giftCard->recipient_name ?: 'Bonjour');
        $amount = e(number_format((float)$giftCard->balance_amount, 2, ',', ' '));
        $code = e($giftCard->code);
        $expiry = $giftCard->expires_at ? 'Valable jusqu’au '.e($giftCard->expires_at->format('d/m/Y')).'.' : 'Sans date d’expiration renseignée.';

        return '<!doctype html><html lang="fr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;background:#f3f4f6;"><tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;"><tr><td style="padding:28px 34px 16px;text-align:center;">
            <img src="'.e($logoUrl).'" width="86" height="86" alt="La Grande Muraille de Chine" style="display:inline-block;border-radius:50%;object-fit:cover;">
            <h1 style="margin:20px 0 8px;color:#17211c;font-family:Georgia,serif;font-size:30px;font-weight:500;">Votre chèque cadeau</h1>
            </td></tr><tr><td style="padding:18px 34px 36px;color:#4b5563;line-height:1.65;">
            <p>Bonjour '. $name .',</p><p>Voici votre chèque cadeau La Grande Muraille de Chine d’une valeur de <strong>'. $amount .' EUR</strong>.</p>
            <div style="margin:24px 0;padding:20px;text-align:center;background:#f4f6f3;border-left:4px solid #215b45;"><strong style="font-size:22px;color:#17211c;letter-spacing:1px;">'. $code .'</strong><br><img src="'.e($qrUrl).'" width="180" height="180" alt="QR code du chèque cadeau" style="margin-top:16px;display:inline-block;"></div>
            <p>Votre carte cadeau A4, prête à imprimer et à offrir, est jointe à cet e-mail.</p><p><strong>Comment l’utiliser ?</strong><br>Imprimez le PDF joint ou présentez-le sur votre téléphone lors de votre commande à table. Notre équipe scannera le QR code ou saisira le code du bon, puis déduira le montant utilisé.</p>
            <p>'. $expiry .'</p><p style="margin-top:28px;">La Grande Muraille de Chine<br>Rue de Mons 171, 7301 Hornu<br>065 78 33 78 · jinzhu1031@gmail.com</p>
            </td></tr></table></td></tr></table></body></html>';
    }

    public function printablePdf(GiftCard $giftCard): string
    {
        $giftCardService = app(GiftCardService::class);
        $qrPath = $giftCardService->ensureQrImage($giftCard);
        $logoPath = public_path('vendor/restoline-wokdasie/images/logo-wokdasie-square.jpg');
        $logo = $this->dataUri($logoPath, 'image/jpeg');
        $qrCode = $this->dataUri($qrPath, 'image/png');
        $recipient = e($giftCard->recipient_name ?: 'Votre proche');
        $amount = e(number_format((float)$giftCard->balance_amount, 2, ',', ' '));
        $code = e($giftCard->code);
        $expiry = $giftCard->expires_at
            ? 'Valable jusqu’au '.e($giftCard->expires_at->format('d/m/Y'))
            : 'Valable sans date d’expiration renseignée';

        $html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
            @page { margin: 10mm; size: A4 portrait; }
            body { color: #242424; font-family: DejaVu Serif, serif; font-size: 10pt; }
            .sheet { border: .8pt solid #d2d2d2; padding: 9mm 12mm 8mm; }
            .header { width: 100%; border-collapse: collapse; }
            .logo { width: 20mm; height: 20mm; object-fit: cover; }
            .restaurant { padding-left: 5mm; vertical-align: top; line-height: 1.4; }
            .restaurant strong { display: block; font-size: 19pt; font-weight: normal; }
            .restaurant small { font-size: 8.5pt; }
            .title { margin: 8mm 0 7mm; padding: 0 0 3mm; border-bottom: 2pt solid #202020; font-size: 30pt; font-style: italic; font-weight: normal; line-height: 1; }
            .intro { margin: 0 0 6mm; font-size: 10.5pt; line-height: 1.55; }
            .gift { width: 100%; border-collapse: collapse; background: #fbfaf7; border-top: 1pt solid #b8924c; border-bottom: 1pt solid #b8924c; }
            .gift td { padding: 6mm; vertical-align: middle; }
            .recipient-label { color: #6a5a3b; font-size: 9pt; }
            .recipient { margin: 2mm 0 4mm; color: #7f1720; font-size: 19pt; }
            .amount { margin: 0; color: #7f1720; font-size: 25pt; font-weight: bold; }
            .qr-cell { width: 56mm; text-align: center; border-left: .7pt solid #d8cfbd; }
            .qr { width: 43mm; height: 43mm; }
            .code-label { margin: 3mm 0 1mm; color: #6a5a3b; font-family: DejaVu Sans, sans-serif; font-size: 7.5pt; letter-spacing: .7pt; text-transform: uppercase; }
            .code { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 12pt; font-weight: bold; letter-spacing: .8pt; }
            .instructions { margin: 7mm 0 0; font-size: 9.5pt; line-height: 1.55; }
            .signature { margin-top: 9mm; text-align: right; font-size: 9.5pt; }
            .footer { margin-top: 8mm; padding-top: 3mm; border-top: .7pt solid #c8c0b3; color: #585858; font-family: DejaVu Sans, sans-serif; font-size: 8pt; line-height: 1.45; text-align: center; }
        </style></head><body><main class="sheet">
            <table class="header"><tr><td style="width:20mm;vertical-align:top"><img class="logo" src="'.$logo.'" alt="La Grande Muraille de Chine"></td><td class="restaurant"><strong>La Grande Muraille de Chine</strong><small>Restaurant Chinois &amp; Japonais<br>Buffet wok<br>Rue de Mons 171, 7301 Hornu<br>065 78 33 78 · jinzhu1031@gmail.com</small></td></tr></table>
            <h1 class="title">Chèque cadeau</h1>
            <p class="intro">Un moment gourmand à offrir.<br>Présentez cette carte lors de l’addition à table : son montant sera déduit de votre consommation.</p>
            <table class="gift"><tr><td><p class="recipient-label">Cette carte est offerte à</p><p class="recipient">'.$recipient.'</p><p class="amount">'.$amount.' EUR</p></td><td class="qr-cell"><img class="qr" src="'.$qrCode.'" alt="QR code"><p class="code-label">Code sécurisé</p><p class="code">'.$code.'</p></td></tr></table>
            <p class="instructions"><strong>Comment l’utiliser ?</strong><br>Imprimez ce chèque cadeau ou présentez-le sur votre téléphone lors de votre commande à table. Notre équipe scannera le QR code ou saisira le code sécurisé. Le bon peut être utilisé en une fois ou partiellement, jusqu’à épuisement de son solde.</p>
            <p class="signature">Avec nos salutations gourmandes,<br><strong>L’équipe La Grande Muraille de Chine</strong></p>
            <footer class="footer">'.$expiry.'<br>Conservez cette carte jusqu’à l’utilisation complète de son solde.</footer>
        </main></body></html>';

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function dataUri(string $path, string $mime): string
    {
        if (!is_file($path)) {
            throw new RuntimeException('Image nécessaire au chèque cadeau introuvable.');
        }

        return 'data:'.$mime.';base64,'.base64_encode((string)file_get_contents($path));
    }
}
