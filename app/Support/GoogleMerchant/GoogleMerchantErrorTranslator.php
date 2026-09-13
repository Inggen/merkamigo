<?php

namespace App\Support\GoogleMerchant;

/**
 * Traduce el motivo crudo que guarda `GoogleMerchantService` (mensajes de
 * validación propios, ya en español, o el texto técnico que devuelve la
 * API de Google) a un mensaje entendible para el emprendedor. El panel de
 * administrador sigue mostrando el motivo crudo tal cual (útil para
 * soporte/depuración) — esta traducción es solo para la vista del
 * vendedor.
 */
class GoogleMerchantErrorTranslator
{
    /**
     * @var array<string, string>
     */
    private const PATTERNS = [
        'price' => 'Revisa el precio del producto antes de publicarlo en Google.',
        'precio' => 'Revisa el precio del producto antes de publicarlo en Google.',
        'image' => 'La imagen del producto no cumple los requisitos de Google. Prueba con otra foto.',
        'imagen' => 'La imagen del producto no cumple los requisitos de Google. Prueba con otra foto.',
        'title' => 'El nombre del producto no cumple los requisitos de Google.',
        'nombre' => 'El nombre del producto no cumple los requisitos de Google.',
        'description' => 'La descripción del producto no cumple los requisitos de Google.',
        'descripción' => 'La descripción del producto no cumple los requisitos de Google.',
        'availability' => 'Revisa la disponibilidad del producto antes de publicarlo en Google.',
        'gtin' => 'El código GTIN ingresado no es válido para Google.',
        'mpn' => 'El código de fabricante (MPN) ingresado no es válido para Google.',
        'brand' => 'La marca del producto no cumple los requisitos de Google.',
        'marca' => 'La marca del producto no cumple los requisitos de Google.',
        'link' => 'La URL del producto no es accesible para Google.',
        'url' => 'La URL del producto no es accesible para Google.',
        'seller' => 'Hay un problema con los datos del vendedor ante Google.',
        'vendedor' => 'El vendedor no está habilitado para Google Shopping.',
        'negocio' => 'El negocio no está habilitado para Google Shopping.',
    ];

    public static function translate(?string $rawError): ?string
    {
        if (blank($rawError)) {
            return null;
        }

        $normalized = mb_strtolower($rawError);

        foreach (self::PATTERNS as $needle => $message) {
            if (str_contains($normalized, $needle)) {
                return $message;
            }
        }

        return 'Google encontró un problema con este producto. Revisa sus datos e inténtalo de nuevo.';
    }
}
