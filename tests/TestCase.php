<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cada prueba usa RefreshDatabase (transacción por prueba); el
        // caché en memoria de roles/permisos de spatie/permission debe
        // limpiarse para no arrastrar IDs de una prueba anterior.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
