<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Support\Ai\AiImagePrompt;
use App\Support\Ai\Contracts\GeneratesImages;
use Illuminate\Http\UploadedFile;

/**
 * Genera una foto de producto con IA a partir de sus datos reales
 * (pedido del usuario) — mismo criterio que `GenerateStorefrontCoverImage`:
 * beneficio del plan Emprendedor (el componente que llama esta acción es
 * responsable de esa validación), y el resultado se guarda por el mismo
 * camino que una foto subida a mano (`UpdateProduct`/`MediaUploader`).
 */
class GenerateProductImage
{
    public function __construct(
        private readonly GeneratesImages $imageGenerator,
    ) {}

    public function handle(Product $product, string $style = AiImagePrompt::ULTRAREALISTA): ?UploadedFile
    {
        $imageContents = $this->imageGenerator->generate($this->prompt($product, $style));

        if ($imageContents === null) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ai-product-photo-');
        file_put_contents($tempPath, $imageContents);

        return new UploadedFile($tempPath, 'foto-producto-ia.png', 'image/png', null, true);
    }

    private function prompt(Product $product, string $style): string
    {
        $details = array_filter([
            $product->type === 'servicio' ? 'Es un servicio, no un producto físico' : null,
            $product->business?->category?->name,
            $product->unit ? "Se ofrece por {$product->unit}" : null,
        ]);

        return AiImagePrompt::build(
            subject: "Foto de catálogo en línea para \"{$product->name}\", de la vitrina \"{$product->business?->name}\"",
            details: array_values($details),
            embeddedText: $product->name,
            style: $style,
        );
    }
}
