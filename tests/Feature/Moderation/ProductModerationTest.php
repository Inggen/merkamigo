<?php

namespace Tests\Feature\Moderation;

use App\Domain\Moderation\Actions\RestoreProduct;
use App\Domain\Moderation\Actions\SuspendProduct;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.9 del TODO: moderación de productos.
 */
class ProductModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspending_a_product_hides_it_and_is_audited(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Producto'])->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto Reportado', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        $moderator = User::factory()->create();
        app(SuspendProduct::class)->handle($product, $moderator, 'Contenido inapropiado');

        $product->refresh();
        $this->assertSame('archivado', $product->status);
        $this->assertTrue($product->isSuspended());
        $this->assertSame('Contenido inapropiado', $product->suspension_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.suspended',
            'subject_id' => $product->id,
        ]);
    }

    public function test_restoring_a_product_publishes_it_again(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Producto'])->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);

        $moderator = User::factory()->create();
        app(SuspendProduct::class)->handle($product, $moderator, 'Motivo');
        app(RestoreProduct::class)->handle($product->fresh(), $moderator);

        $product->refresh();
        $this->assertSame('publicado', $product->status);
        $this->assertFalse($product->isSuspended());
        $this->assertNull($product->suspension_reason);
    }
}
