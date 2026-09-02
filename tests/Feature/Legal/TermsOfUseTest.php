<?php

namespace Tests\Feature\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsOfUseTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_include_the_third_party_sales_and_returns_policy(): void
    {
        $this->get(route('terminos'))
            ->assertOk()
            ->assertSee('Ventas, garantías y devoluciones de terceros')
            ->assertSee('El negocio identificado en cada vitrina es el vendedor o prestador del servicio')
            ->assertSee('la garantía, el derecho de retracto, la reversión del pago')
            ->assertSee('Estatuto del Consumidor colombiano');

        $this->assertSame('1.2', config('legal.terms_version'));
    }
}
