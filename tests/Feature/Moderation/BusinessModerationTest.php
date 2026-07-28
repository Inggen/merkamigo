<?php

namespace Tests\Feature\Moderation;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Moderation\Actions\RestoreBusiness;
use App\Domain\Moderation\Actions\SuspendBusiness;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Exceptions\BusinessSuspendedException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.9 del TODO: revisión, publicación, suspensión y restauración de
 * vitrinas. Toda suspensión requiere motivo y queda auditada; el propio
 * emprendedor no puede revertirla publicando de nuevo.
 */
class BusinessModerationTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $suffix = uniqid();
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Moderado '.$suffix,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Descripción de prueba.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business->fresh(), $owner);

        return $business->fresh();
    }

    public function test_suspending_a_business_requires_a_reason_and_is_audited(): void
    {
        $business = $this->publishedBusiness();
        $moderator = User::factory()->create();

        app(SuspendBusiness::class)->handle($business, $moderator, 'Contenido inapropiado');

        $business->refresh();
        $this->assertSame('suspendido', $business->status);
        $this->assertSame('Contenido inapropiado', $business->suspension_reason);
        $this->assertNotNull($business->suspended_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'business.suspended',
            'subject_type' => $business->getMorphClass(),
            'subject_id' => $business->id,
        ]);
    }

    public function test_a_suspended_business_disappears_from_public_pages(): void
    {
        $business = $this->publishedBusiness();
        $moderator = User::factory()->create();

        app(SuspendBusiness::class)->handle($business, $moderator, 'Spam');

        $this->get(route('vitrinas.show', $business->fresh()))->assertNotFound();
    }

    public function test_the_owner_cannot_undo_a_suspension_by_publishing_again(): void
    {
        $business = $this->publishedBusiness();
        $moderator = User::factory()->create();
        app(SuspendBusiness::class)->handle($business, $moderator, 'Información falsa');

        $owner = $business->fresh()->members()->first();

        $this->expectException(BusinessSuspendedException::class);

        app(PublishStorefront::class)->handle($business->fresh(), $owner);
    }

    public function test_restoring_a_business_makes_it_public_again_and_is_audited(): void
    {
        $business = $this->publishedBusiness();
        $moderator = User::factory()->create();
        app(SuspendBusiness::class)->handle($business, $moderator, 'Motivo temporal');

        app(RestoreBusiness::class)->handle($business->fresh(), $moderator);

        $business->refresh();
        $this->assertSame('publicado', $business->status);
        $this->assertNull($business->suspension_reason);
        $this->assertNull($business->suspended_at);

        $this->get(route('vitrinas.show', $business))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'business.restored',
            'subject_id' => $business->id,
        ]);
    }
}
