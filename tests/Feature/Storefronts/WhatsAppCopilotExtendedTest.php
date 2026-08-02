<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 4.4 del TODO: Copiloto de WhatsApp ampliado — variantes de longitud,
 * fecha sugerida para un borrador y respuestas frecuentes editables.
 */
class WhatsAppCopilotExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_short_variant_is_shorter_than_the_long_variant_for_the_same_content(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Longitud',
            'description' => 'Somos un negocio familiar con más de diez años de experiencia en el municipio.',
        ])->business;

        $this->actingAs($owner);

        $short = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'presentacion')
            ->set('tone', 'cercano')
            ->set('length', 'corto')
            ->call('generate')
            ->get('generated');

        $long = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'presentacion')
            ->set('tone', 'cercano')
            ->set('length', 'largo')
            ->call('generate')
            ->get('generated');

        $this->assertLessThan(strlen($long), strlen($short));
        $this->assertStringNotContainsString('diez años', $short);
        $this->assertStringContainsString('diez años', $long);
    }

    public function test_a_draft_can_be_saved_with_a_suggested_date_and_it_shows_in_history(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Fecha'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'estado')
            ->set('tone', 'cercano')
            ->call('generate')
            ->set('scheduledFor', now()->addDays(3)->toDateString())
            ->call('saveDraft')
            ->assertSee('Para el');

        $draft = WhatsAppContent::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(now()->addDays(3)->toDateString(), $draft->scheduled_for->toDateString());
    }

    public function test_owner_can_override_the_faq_answers_and_they_are_used_instead_of_the_automatic_text(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio FAQ'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('faqDisponibilidad', 'Tenemos arepas y empanadas todos los días.')
            ->set('faqHorario', 'Lunes a sábado de 7am a 5pm.')
            ->set('faqDomicilio', 'No hacemos domicilios por ahora.')
            ->call('saveFaqAnswers')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertSame('Tenemos arepas y empanadas todos los días.', $business->faqAnswer('disponibilidad'));

        $generated = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'respuesta')
            ->set('tone', 'cercano')
            ->call('generate')
            ->get('generated');

        $this->assertStringContainsString('Tenemos arepas y empanadas todos los días.', $generated);
        $this->assertStringContainsString('Lunes a sábado de 7am a 5pm.', $generated);
        $this->assertStringContainsString('No hacemos domicilios por ahora.', $generated);
    }

    public function test_without_faq_overrides_the_automatic_text_is_still_used(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin FAQ'])->business;
        app(CreateProduct::class)->handle($business, [
            'name' => 'Arepa', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner)->update(['status' => 'publicado', 'is_available' => true]);

        $this->actingAs($owner);

        $generated = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'respuesta')
            ->set('tone', 'cercano')
            ->call('generate')
            ->get('generated');

        $this->assertStringContainsString('Arepa', $generated);
    }

    public function test_a_product_never_viewed_gets_a_suggestion_and_a_recently_viewed_one_does_not(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sugerencias'])->business;

        $neverViewed = app(CreateProduct::class)->handle($business, [
            'name' => 'Nunca visto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $neverViewed->update(['status' => 'publicado']);

        $freshlyViewed = app(CreateProduct::class)->handle($business, [
            'name' => 'Recién visto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $freshlyViewed->update(['status' => 'publicado']);

        AnalyticsEvent::create([
            'business_id' => $business->id, 'type' => AnalyticsEvent::PRODUCTO_VIEW,
            'subject_type' => $freshlyViewed->getMorphClass(), 'subject_id' => $freshlyViewed->id, 'visitor_hash' => 'a',
        ]);

        $this->actingAs($owner);

        $suggestions = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->get('suggestions');

        $this->assertTrue(collect($suggestions)->contains(fn ($s) => str_contains($s, 'Nunca visto')));
        $this->assertFalse(collect($suggestions)->contains(fn ($s) => str_contains($s, 'Recién visto')));
    }
}
