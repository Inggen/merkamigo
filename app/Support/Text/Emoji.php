<?php

namespace App\Support\Text;

/**
 * Limpieza de emojis compartida entre el payload de Google Merchant
 * (`GoogleMerchantProductMapper`) y el JSON-LD de la página pública
 * (`SchemaBuilder`) — Google rechaza/penaliza tanto el feed de Merchant
 * Center como los datos estructurados con emojis en título/descripción.
 * El contenido real del producto en Merkamigo (lo que ve el emprendedor
 * y el visitante en la vitrina como texto plano) no se toca.
 */
class Emoji
{
    public static function strip(string $text): string
    {
        $pattern = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{2190}-\x{21FF}]/u';

        $cleaned = (string) preg_replace($pattern, '', $text);

        return trim((string) preg_replace('/\s{2,}/', ' ', $cleaned));
    }
}
