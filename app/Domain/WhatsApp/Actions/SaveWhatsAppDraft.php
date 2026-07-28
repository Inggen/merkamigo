<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Models\WhatsAppContent;

/**
 * Guarda un borrador del Copiloto de WhatsApp y acota el historial a los
 * últimos N por negocio (1.7 del TODO: "historial limitado").
 */
class SaveWhatsAppDraft
{
    private const HISTORY_LIMIT = 20;

    public function handle(Business $business, string $type, ?Product $product, ?string $tone, string $content): WhatsAppContent
    {
        $draft = WhatsAppContent::create([
            'business_id' => $business->id,
            'product_id' => $product?->id,
            'type' => $type,
            'tone' => $tone,
            'content' => $content,
        ]);

        $idsToKeep = WhatsAppContent::query()
            ->where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->pluck('id');

        WhatsAppContent::query()
            ->where('business_id', $business->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        return $draft;
    }
}
