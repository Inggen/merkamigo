<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 0.3 del TODO: health checks de aplicación, base de datos, colas y
 * almacenamiento.
 */
class HealthTest extends TestCase
{
    public function test_the_health_endpoint_reports_all_checks(): void
    {
        $response = $this->getJson(route('api.v1.health'));

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.storage', true);
    }
}
