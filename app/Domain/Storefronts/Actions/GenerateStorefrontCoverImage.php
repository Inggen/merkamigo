<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\AiImagePrompt;
use App\Support\Ai\Contracts\GeneratesImages;
use Illuminate\Http\UploadedFile;

/**
 * Genera una foto de portada con IA a partir de los datos reales del
 * negocio (pedido del usuario) — beneficio del plan Emprendedor, el
 * componente que llama esta acción es responsable de esa validación.
 * Devuelve un `UploadedFile` "de prueba" (no viene de una petición HTTP
 * real) para poder reutilizar exactamente el mismo camino que una
 * portada subida a mano (`UpdateStorefront`/`MediaUploader`): mismo
 * límite de tamaño, mismo recorte/optimización, mismo borrado de la
 * portada anterior.
 */
class GenerateStorefrontCoverImage
{
    public function __construct(
        private readonly GeneratesImages $imageGenerator,
    ) {}

    public function handle(Business $business, string $style = AiImagePrompt::ULTRAREALISTA): ?UploadedFile
    {
        $imageContents = $this->imageGenerator->generate($this->prompt($business, $style));

        if ($imageContents === null) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ai-cover-');
        file_put_contents($tempPath, $imageContents);

        return new UploadedFile($tempPath, 'portada-ia.png', 'image/png', null, true);
    }

    private function prompt(Business $business, string $style): string
    {
        $details = array_filter([
            $business->category?->name,
            $business->municipality?->name,
        ]);

        return AiImagePrompt::build(
            subject: "Fotografía de portada para la vitrina en línea del negocio \"{$business->name}\"",
            details: array_values($details),
            embeddedText: $business->storefront?->headline ?: $business->name,
            style: $style,
        );
    }
}
