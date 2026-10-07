<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Models\LoyaltyReceiptClaim;
use Illuminate\Http\RedirectResponse;

/**
 * Enlace protegido al recibo de una solicitud excepcional
 * (TODO_Merkapuntos.md, F2.7). Mismo patrón que
 * `BusinessVerificationDocumentController`, con un cuidado extra: el
 * middleware `business.team` fija el team de spatie/permission a partir
 * del `{business}` de LA RUTA, pero la solicitud a autorizar es
 * `$claim->business` — si no coincidieran, autorizar contra `$claim->business`
 * evaluaría los roles bajo el team equivocado (el de la ruta, no el dueño
 * real de la solicitud), lo que dejaría colar a cualquiera que tenga un
 * negocio propio y conozca el ID de una solicitud ajena. Por eso se
 * verifica primero que ambos coincidan.
 */
class LoyaltyReceiptClaimDocumentController extends Controller
{
    public function show(Business $business, LoyaltyReceiptClaim $claim): RedirectResponse
    {
        abort_unless($claim->business_id === $business->id, 404);

        $this->authorize('view', $business);

        $url = $claim->receiptUrl();

        abort_if(blank($url), 404);

        return redirect()->away($url);
    }
}
