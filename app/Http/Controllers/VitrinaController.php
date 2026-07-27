<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Vitrina pública (1.3 del TODO): `/m/{slug}`, detalle de producto y QR.
 * Contenido público, sin autenticación.
 */
class VitrinaController extends Controller
{
    public function show(Business $business): View
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['storefront', 'municipality', 'category']);

        return view('vitrinas.show', [
            'business' => $business,
            'products' => $business->products()->where('status', 'publicado')->get(),
        ]);
    }

    public function product(Business $business, string $product): View
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        return view('vitrinas.product', [
            'business' => $business,
            'product' => $product->load('media'),
        ]);
    }

    public function qr(Business $business): Response
    {
        abort_unless($business->isPublished(), 404);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'imageTransparent' => false,
            'scale' => 8,
        ]);

        $png = (new QRCode($options))->render(route('vitrinas.show', $business));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }
}
