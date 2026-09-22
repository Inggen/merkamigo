<?php

namespace App\Http\Controllers;

use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Models\Entitlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga protegida de un producto digital (Fase 9 del TODO social) —
 * exige un `Entitlement` vigente, nunca una URL pública permanente. El
 * dueño del negocio también puede descargarlo para revisarlo.
 */
class ProductDownloadController extends Controller
{
    public function download(Request $request, Product $product): StreamedResponse
    {
        $file = $product->files()->firstOrFail();
        $user = $request->user();

        $isOwner = $user->can('update', $product->business);

        if (! $isOwner) {
            $entitlement = Entitlement::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            abort_if(! $entitlement || ! $entitlement->isActive(), 403, 'No tienes acceso a este archivo.');
        }

        return Storage::disk('private')->download($file->path, $file->original_name);
    }
}
