<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

/**
 * Bug real reportado por el usuario: `PayloadTooLargeException` en el
 * editor espacial de plaza — `PlazaSpatialEditor` mantiene toda la escena
 * en una propiedad pública de Livewire, así que una plaza con bastante
 * contenido supera fácilmente el límite de 1MB por defecto del paquete.
 * Guarda contra que alguien borre `config/livewire.php` pensando que no
 * se usa (es un archivo de configuración, no algo que se referencie desde
 * código PHP).
 */
class LivewirePayloadConfigTest extends TestCase
{
    public function test_the_livewire_payload_limit_is_raised_above_the_package_default(): void
    {
        $this->assertSame(8 * 1024 * 1024, config('livewire.payload.max_size'));

        // Las demás sub-claves de "payload" no deben perderse — Laravel
        // hace `mergeConfigFrom()` a nivel de la clave raíz, no un merge
        // profundo, así que sobreescribir solo `max_size` sin repetir las
        // otras las dejaría en `null` (sin límite) en vez de su default.
        $this->assertSame(10, config('livewire.payload.max_nesting_depth'));
        $this->assertSame(50, config('livewire.payload.max_calls'));
        $this->assertSame(200, config('livewire.payload.max_components'));
    }
}
