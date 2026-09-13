<?php

namespace Tests\Unit;

use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use ReflectionClass;
use Tests\TestCase;

/**
 * Google rechaza (o bloquea editar) productos con emojis en title/
 * description — se limpian solo en el payload hacia Google, sin tocar
 * el contenido real del producto en Merkamigo.
 */
class GoogleMerchantMapperEmojiTest extends TestCase
{
    private function stripEmoji(string $text): string
    {
        $mapper = new GoogleMerchantProductMapper;
        $method = (new ReflectionClass($mapper))->getMethod('stripEmoji');
        $method->setAccessible(true);

        return $method->invoke($mapper, $text);
    }

    public function test_strips_common_emoji_from_title_and_description(): void
    {
        $this->assertSame(
            'Jabón Artesanal Cookies & Cream',
            $this->stripEmoji('Jabón Artesanal 🧼✨ Cookies & Cream 😍'),
        );

        $this->assertSame(
            'Torta de chocolate ¡Deliciosa!',
            $this->stripEmoji('Torta de chocolate 🎂🍫 ¡Deliciosa! 🔥🔥🔥'),
        );
    }

    public function test_leaves_plain_text_and_accents_untouched(): void
    {
        $this->assertSame(
            'Café recién tostado — 100% arábica',
            $this->stripEmoji('Café ☕ recién tostado — 100% arábica'),
        );

        $this->assertSame(
            'Sin ningún emoji, texto normal.',
            $this->stripEmoji('Sin ningún emoji, texto normal.'),
        );
    }
}
