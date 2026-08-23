<?php

namespace App\Support\Ai;

/**
 * Construye el prompt compartido para toda imagen generada con IA en el
 * panel de emprendedor (portada de vitrina, foto de producto) — pedido
 * del usuario: fotografía profesional ultrarrealista O estilo voxel, a
 * elección, con el texto relevante incorporado directamente en la
 * imagen de forma limpia y elegante (no como una marca de agua).
 */
class AiImagePrompt
{
    public const ULTRAREALISTA = 'ultrarealista';

    public const VOXEL = 'voxel';

    /**
     * @return array<string, string>
     */
    public static function styles(): array
    {
        return [
            self::ULTRAREALISTA => 'Ultrarrealista',
            self::VOXEL => 'Voxel',
        ];
    }

    public static function isValidStyle(string $style): bool
    {
        return array_key_exists($style, self::styles());
    }

    /**
     * @param  array<int, string>  $details  Datos reales del negocio/producto — nunca se debe inventar información fuera de esta lista.
     */
    public static function build(string $subject, array $details, string $embeddedText, string $style): string
    {
        $detailsText = $details !== [] ? ' Datos reales: '.implode(', ', $details).'.' : '';

        $styleInstruction = $style === self::VOXEL
            ? 'Estilo voxel: bloques 3D isométricos tipo diorama, colores vivos, iluminación suave, composición limpia y moderna (como una escena hecha de cubos, sin fotorrealismo).'
            : 'Fotografía profesional ultrarrealista: luz natural cálida, alta resolución, composición editorial de revista.';

        return trim(
            "{$subject}.{$detailsText} {$styleInstruction} Incorpora el texto \"{$embeddedText}\" directamente en la imagen, con tipografía limpia, elegante y bien integrada al diseño (no como una marca de agua superpuesta). Sin errores ortográficos ni tipográficos, sin logotipos ajenos, sin marcas de agua adicionales, sin personas reconocibles."
        );
    }
}
