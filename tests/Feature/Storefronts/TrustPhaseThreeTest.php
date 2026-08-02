<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Actions\ModerateRecommendation;
use App\Domain\Trust\Actions\ReviewBusinessVerification;
use App\Domain\Trust\Models\BusinessVerification;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrustPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_verification_document_url_is_a_temporary_signed_link_never_on_the_public_disk(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [$business] = $this->publishedBusiness($municipality, $category);

        $path = 'business-verifications/'.$business->id.'/documento.pdf';
        Storage::disk('private')->put($path, 'contenido');

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::EN_REVISION,
            'verification_document_path' => $path,
        ]);

        $url = $verification->documentUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('expiration=', $url);
        Storage::disk('public')->assertMissing($path);
    }

    private function publishedBusiness(Municipality $municipality, Category $category): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Confiable',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panadería con reputación verificable.',
        ])->business;

        $business->update(['logo_path' => 'businesses/'.$business->id.'/logo.jpg']);

        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan campesino',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 8000,
        ], [], $owner)->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return [$business->fresh(), $owner];
    }

    public function test_the_public_storefront_shows_the_trust_badge_confirmed_orders_and_published_recommendations(): void
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [$business, $owner] = $this->publishedBusiness($municipality, $category);

        $customer = User::factory()->create();

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'requested_by' => $owner->id,
            'status' => BusinessVerification::EN_REVISION,
            'legal_name' => 'Panadería Confiable SAS',
            'contact_name' => 'Ana Dueña',
        ]);

        app(ReviewBusinessVerification::class)->handle(
            $verification,
            $owner,
            BusinessVerification::VERIFICADA,
            'Documentos básicos revisados.',
            Carbon::now()->addDays(30),
            'basica',
        );

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido de panes para evento familiar.',
        ]);

        app(ConfirmOrder::class)->confirmAsCustomer($order, $customer);
        app(ConfirmOrder::class)->confirmAsBusiness($order->fresh(), $owner);
        app(ConfirmOrder::class)->complete($order->fresh(), $owner);

        $recommendation = Recommendation::create([
            'business_id' => $business->id,
            'order_confirmation_id' => $order->id,
            'author_user_id' => $customer->id,
            'body' => 'Llegó puntual y el producto estaba fresco.',
            'tags' => ['Puntual', 'Buena atención'],
        ]);

        app(ModerateRecommendation::class)->handle(
            $recommendation,
            $owner,
            Recommendation::PUBLICADA,
            'Gracias por confiar en nosotros.',
        );

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Verificación básica')
            ->assertSee('1 pedido confirmado')
            ->assertSee('Llegó puntual y el producto estaba fresco.')
            ->assertSee('Gracias por confiar en nosotros.')
            ->assertSee('Esta insignia confirma una revisión básica de identidad o documentos del negocio. No implica garantía de calidad, pago ni entrega por parte de Merkamigo.');
    }

    public function test_the_public_storefront_hides_the_badge_when_verification_is_revoked(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [$business, $owner] = $this->publishedBusiness($municipality, $category);

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'requested_by' => $owner->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addDays(10),
        ]);

        app(ReviewBusinessVerification::class)->handle(
            $verification,
            $owner,
            BusinessVerification::REVOCADA,
            'Se revoca por inconsistencia documental.',
        );

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertDontSee('Verificación básica');
    }

    public function test_an_order_cannot_be_completed_unilaterally_and_leaves_audit_trail_when_both_sides_confirm(): void
    {
        $municipality = Municipality::create(['name' => 'Tabio', 'slug' => 'tabio', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [$business, $owner] = $this->publishedBusiness($municipality, $category);

        $customer = User::factory()->create();
        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido con confirmación bilateral.',
        ]);

        $service = app(ConfirmOrder::class);

        $service->confirmAsCustomer($order, $customer);
        $service->complete($order->fresh(), $customer);

        $order->refresh();

        $this->assertSame(OrderConfirmation::PENDIENTE, $order->status);
        $this->assertNull($order->completed_at);
        $this->assertFalse($order->is_reputation_eligible);

        $service->confirmAsBusiness($order, $owner);
        $service->complete($order->fresh(), $owner);

        $order->refresh();

        $this->assertSame(OrderConfirmation::COMPLETADO, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertTrue($order->is_reputation_eligible);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.confirmed_by_customer',
            'subject_type' => $order->getMorphClass(),
            'subject_id' => $order->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.confirmed_by_business',
            'subject_type' => $order->getMorphClass(),
            'subject_id' => $order->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.completed',
            'subject_type' => $order->getMorphClass(),
            'subject_id' => $order->id,
        ]);

        $this->assertSame(3, AuditLog::query()->where('subject_type', $order->getMorphClass())->where('subject_id', $order->id)->count());
    }
}
