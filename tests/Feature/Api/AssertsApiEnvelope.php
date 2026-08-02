<?php

namespace Tests\Feature\Api;

use Illuminate\Testing\TestResponse;

/**
 * Aserciones compartidas para el envelope estándar de /api/v1 (5.1 del
 * TODO), reusadas por los tests de los endpoints nuevos en vez de repetir
 * la misma estructura `data/meta/links` en cada archivo.
 */
trait AssertsApiEnvelope
{
    protected function assertPaginatedEnvelope(TestResponse $response): TestResponse
    {
        return $response
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }
}
