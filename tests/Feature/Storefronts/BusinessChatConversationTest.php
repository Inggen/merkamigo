<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Jobs\NotifyBusinessOfChatConversation;
use App\Domain\Storefronts\Models\BusinessChatConversation;
use App\Domain\Storefronts\Notifications\NewChatConversation;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: seguimiento de quién le escribe al chatbot y qué se
 * dijo, con un aviso por correo (resumen por IA) cuando la conversación
 * termina — sin mandar un correo por cada mensaje.
 */
class BusinessChatConversationTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithChatbot(): Business
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Chat',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner)->update(['status' => 'publicado']);

        BusinessEntitlement::create(['business_id' => $business->id, 'key' => BusinessEntitlement::AI_CHATBOT]);
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

    public function test_chatting_records_a_conversation_with_its_messages(): void
    {
        $business = $this->businessWithChatbot();
        $this->fakeAssistedText('Sí, hacemos domicilios en Cajicá.');

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => '¿Tienen domicilios?'])
            ->assertOk();

        $conversation = BusinessChatConversation::where('business_id', $business->id)->firstOrFail();
        $this->assertCount(2, $conversation->messages);
        $this->assertSame('user', $conversation->messages[0]->role);
        $this->assertSame('¿Tienen domicilios?', $conversation->messages[0]->content);
        $this->assertSame('assistant', $conversation->messages[1]->role);
        $this->assertSame('Sí, hacemos domicilios en Cajicá.', $conversation->messages[1]->content);
        $this->assertNotEmpty($conversation->visitor_hash);
    }

    public function test_consecutive_messages_from_the_same_visitor_join_the_same_conversation(): void
    {
        $business = $this->businessWithChatbot();
        $this->fakeAssistedText('Respuesta.');

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => 'Primera pregunta'])->assertOk();
        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => 'Segunda pregunta'])->assertOk();

        $this->assertSame(1, BusinessChatConversation::where('business_id', $business->id)->count());

        $conversation = BusinessChatConversation::where('business_id', $business->id)->firstOrFail();
        $this->assertCount(4, $conversation->messages);
    }

    public function test_a_message_long_after_the_session_window_starts_a_new_conversation(): void
    {
        $business = $this->businessWithChatbot();
        $this->fakeAssistedText('Respuesta.');

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => 'Pregunta vieja'])->assertOk();

        BusinessChatConversation::where('business_id', $business->id)->update([
            'last_message_at' => now()->subHours(7),
        ]);

        $this->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => 'Pregunta nueva'])->assertOk();

        $this->assertSame(2, BusinessChatConversation::where('business_id', $business->id)->count());
    }

    public function test_a_logged_in_visitor_is_attributed_by_name(): void
    {
        $business = $this->businessWithChatbot();
        $this->fakeAssistedText('Respuesta.');
        $visitor = User::factory()->create(['name' => 'Camila Torres']);

        $this->actingAs($visitor)
            ->postJson(route('api.v1.plaza.negocios.chat', $business->slug), ['question' => 'Hola'])
            ->assertOk();

        $conversation = BusinessChatConversation::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('Camila Torres', $conversation->displayLabel());
        $this->assertSame($visitor->id, $conversation->visitor_user_id);
    }

    public function test_the_notify_job_summarizes_and_notifies_the_business_owner(): void
    {
        Notification::fake();

        $business = $this->businessWithChatbot();

        $conversation = BusinessChatConversation::create([
            'business_id' => $business->id,
            'visitor_hash' => 'hash-1',
            'last_message_at' => now(),
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => '¿Hacen domicilios?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Sí, en Cajicá.']);

        $this->app->instance(GeneratesAssistedText::class, new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Un visitante preguntó por domicilios y se le confirmó cobertura en Cajicá.';
            }
        });

        (new NotifyBusinessOfChatConversation($conversation->id, 2))->handle();

        $conversation->refresh();
        $this->assertNotNull($conversation->notified_at);
        $this->assertSame('Un visitante preguntó por domicilios y se le confirmó cobertura en Cajicá.', $conversation->summary);

        Notification::assertSentTo($business->members->first(), NewChatConversation::class);
    }

    public function test_the_notify_job_skips_when_newer_messages_arrived_since_it_was_dispatched(): void
    {
        Notification::fake();

        $business = $this->businessWithChatbot();

        $conversation = BusinessChatConversation::create([
            'business_id' => $business->id,
            'visitor_hash' => 'hash-2',
            'last_message_at' => now(),
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Primera pregunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Primera respuesta']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Segunda pregunta, llegó después']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Segunda respuesta']);

        // El job se programó cuando la conversación solo tenía 2 mensajes.
        (new NotifyBusinessOfChatConversation($conversation->id, 2))->handle();

        $conversation->refresh();
        $this->assertNull($conversation->notified_at);
        Notification::assertNothingSent();
    }

    public function test_the_notify_job_does_not_notify_twice(): void
    {
        Notification::fake();

        $business = $this->businessWithChatbot();

        $conversation = BusinessChatConversation::create([
            'business_id' => $business->id,
            'visitor_hash' => 'hash-3',
            'last_message_at' => now(),
            'notified_at' => now(),
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Hola']);

        (new NotifyBusinessOfChatConversation($conversation->id, 1))->handle();

        Notification::assertNothingSent();
    }

    public function test_owner_sees_recent_conversations_on_the_chatbot_page(): void
    {
        $business = $this->businessWithChatbot();
        $owner = $business->members->first();

        $conversation = BusinessChatConversation::create([
            'business_id' => $business->id,
            'visitor_hash' => 'hash-4',
            'visitor_label' => 'Camila Torres',
            'last_message_at' => now(),
            'summary' => 'Preguntó por horarios de atención.',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => '¿A qué hora abren?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Abrimos a las 8am.']);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->assertSee('Camila Torres')
            ->assertSee('Preguntó por horarios de atención.')
            ->assertSee('¿A qué hora abren?');
    }
}
