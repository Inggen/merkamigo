<?php

namespace App\Http\Controllers;

use App\Domain\Loyalty\Actions\IssueLoyaltyIdentityToken;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * TODO_Merkapuntos.md, F1.8/F2.4/F2.5: los QR del cliente (identificación
 * y canje) se sirven como imagen por un endpoint propio (mismo patrón que
 * `VitrinaController::qr`), nunca se embebe el token crudo en el HTML de
 * la página.
 */
class MerkapuntosQrController extends Controller
{
    public function identity(Request $request): Response
    {
        $token = app(IssueLoyaltyIdentityToken::class)->handle($request->user());

        return $this->render($token);
    }

    public function redemption(Request $request, LoyaltyRedemption $redemption): Response
    {
        abort_unless($redemption->account->user_id === $request->user()->id, 403);
        abort_unless($redemption->status === LoyaltyRedemption::RESERVADO, 404);

        return $this->render($redemption->token_encrypted);
    }

    private function render(string $value): Response
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'imageTransparent' => false,
            'scale' => 8,
        ]);

        $png = (new QRCode($options))->render($value);

        return response($png, 200, ['Content-Type' => 'image/png']);
    }
}
