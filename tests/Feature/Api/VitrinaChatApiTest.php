<?php

namespace Tests\Feature\Api;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chat con IA de la vitrina (`/api/v1/plaza/negocios/{slug}/chat`): solo
 * disponible para negocios con `Business::canUseAiChatbot()` (plan
 * Emprendedor o el add-on comprado en "Impulsa tu negocio").
 */
class VitrinaChatApiTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): \App\Domain\Businesses\Models\Business
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería de Cajicá',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner)->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    private function fakeAssistedText(?string $answer): void
    {
        $this->app->bind(GeneratesAssistedText::class, fn () => new class($answer) implements GeneratesAssistedText
        {
            public function __construct(private readonly ?string $answer) {}

            public function generate(string $prompt, array $context = []): ?string
            {
                return $this->answer;
            }
        });
    }

    public function test_chat_is_forbidden_for_a_business_without_the_plan_or_addon(): void
    {
        $business = $this->publishedBusiness();
        $this->fakeAssistedText('No debería llamarse.');

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => '¿Tienen domicilios?'])
            ->assertForbidden();
    }

    public function test_chat_answers_when_the_business_has_the_addon_entitlement(): void
    {
        $business = $this->publishedBusiness();
        BusinessEntitlement::create(['business_id' => $business->id, 'key' => BusinessEntitlement::AI_CHATBOT]);
        $this->fakeAssistedText('Sí, hacemos domicilios en Cajicá.');

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => '¿Tienen domicilios?'])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Sí, hacemos domicilios en Cajicá.');
    }

    public function test_chat_returns_503_when_the_ai_generator_cannot_answer(): void
    {
        $business = $this->publishedBusiness();
        BusinessEntitlement::create(['business_id' => $business->id, 'key' => BusinessEntitlement::AI_CHATBOT]);
        $this->fakeAssistedText(null);

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => '¿Tienen domicilios?'])
            ->assertStatus(503)
            ->assertJsonPath('data.answer', null);
    }

    public function test_chat_requires_a_question(): void
    {
        $business = $this->publishedBusiness();
        BusinessEntitlement::create(['business_id' => $business->id, 'key' => BusinessEntitlement::AI_CHATBOT]);

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.question.0', 'The question field is required.');
    }

    public function test_chat_is_not_found_for_an_unpublished_business(): void
    {
        $draft = app(CreateStorefront::class)->handle(User::factory()->create(), ['name' => 'Borrador'])->business;
        BusinessEntitlement::create(['business_id' => $draft->id, 'key' => BusinessEntitlement::AI_CHATBOT]);

        $this->postJson(route('api.v1.plaza.negocios.chat', $draft->slug), ['question' => 'Hola'])
            ->assertNotFound();
    }
}
