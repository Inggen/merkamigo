<?php

namespace Tests\Feature\Messaging;

use App\Domain\Businesses\Models\Business;
use App\Domain\Messaging\Actions\SendBusinessMessage;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Messaging\Notifications\BusinessMessageReceived;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\Post;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InternalMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_and_returns_to_internal_contact_flow(): void
    {
        [, $business] = $this->publishedBusiness();

        $contactUrl = route('vitrinas.contact', $business);

        $this->get($contactUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $contactUrl);
    }

    public function test_customer_starts_a_product_conversation_and_notifies_the_business(): void
    {
        Notification::fake();

        [$owner, $business] = $this->publishedBusiness();
        $customer = User::factory()->create();
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Instalación de cortinas',
            'type' => 'servicio',
            'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        $response = $this->actingAs($customer)->get(route('vitrinas.contact', [
            'business' => $business,
            'context_type' => 'product',
            'context_id' => $product->id,
        ]));

        $conversation = BusinessConversation::firstOrFail();

        $response->assertRedirect(route('messages.show', $conversation));
        $this->assertSame('service', $conversation->context_type);
        $this->assertSame($product->name, $conversation->context_label);

        app(SendBusinessMessage::class)->handle($conversation, $customer, 'Quiero conocer la disponibilidad.');

        $this->assertDatabaseHas('business_messages', [
            'business_conversation_id' => $conversation->id,
            'sender_user_id' => $customer->id,
            'body' => 'Quiero conocer la disponibilidad.',
        ]);
        Notification::assertSentTo($owner, BusinessMessageReceived::class);
        $this->assertSame(1, $owner->unreadBusinessMessagesCount());
    }

    public function test_business_member_can_reply_and_customer_is_notified(): void
    {
        Notification::fake();

        [$owner, $business] = $this->publishedBusiness();
        $customer = User::factory()->create();
        $conversation = BusinessConversation::create([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'context_key' => 'business',
        ]);

        app(SendBusinessMessage::class)->handle($conversation, $owner, 'Sí, podemos ayudarte.');

        Notification::assertSentTo($customer, BusinessMessageReceived::class);
        $this->assertSame(1, $customer->unreadBusinessMessagesCount());
    }

    public function test_unrelated_user_cannot_open_a_conversation(): void
    {
        [, $business] = $this->publishedBusiness();
        $customer = User::factory()->create();
        $intruder = User::factory()->create();
        $conversation = BusinessConversation::create([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'context_key' => 'business',
        ]);

        $this->actingAs($intruder)
            ->get(route('messages.show', $conversation))
            ->assertForbidden();
    }

    public function test_configured_whatsapp_channel_keeps_the_product_context(): void
    {
        [$owner, $business] = $this->publishedBusiness('whatsapp');
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto de prueba',
            'type' => 'producto',
            'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        $this->get(route('vitrinas.contact', [
            'business' => $business,
            'context_type' => 'product',
            'context_id' => $product->id,
        ]))->assertRedirect(route('vitrinas.whatsapp.product', [$business, $product]));
    }

    public function test_storefront_internal_message_button_ignores_the_external_contact_channel(): void
    {
        [, $business] = $this->publishedBusiness('whatsapp');
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)
            ->get(route('vitrinas.contact.internal', $business));

        $conversation = BusinessConversation::firstOrFail();

        $response->assertRedirect(route('messages.show', $conversation));
        $this->assertSame($business->id, $conversation->business_id);
        $this->assertSame($customer->id, $conversation->customer_user_id);
    }

    public function test_phone_and_external_contact_channels_are_resolved_without_changing_the_cta(): void
    {
        [, $business] = $this->publishedBusiness('phone');

        $this->get(route('vitrinas.contact', $business))
            ->assertRedirect('tel:+573001112233');

        $business->update([
            'contact_channel' => 'external_link',
            'social_links' => ['website' => 'https://example.com/contacto'],
        ]);

        $this->get(route('vitrinas.contact', $business))
            ->assertRedirect('https://example.com/contacto');
    }

    public function test_publication_and_promotion_contexts_are_kept_in_separate_threads(): void
    {
        [$owner, $business] = $this->publishedBusiness();
        $customer = User::factory()->create();
        $post = Post::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'type' => 'texto',
            'body' => 'Nueva colección disponible',
            'status' => 'publicado',
            'published_at' => now(),
        ]);
        $promotion = ContentPromotion::create([
            'business_id' => $business->id,
            'created_by_user_id' => $owner->id,
            'promotable_type' => Post::class,
            'promotable_id' => $post->id,
            'status' => ContentPromotion::ACTIVA,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($customer)->get(route('vitrinas.contact', [
            'business' => $business,
            'context_type' => 'post',
            'context_id' => $post->id,
        ]))->assertRedirect();

        $this->get(route('vitrinas.contact', [
            'business' => $business,
            'context_type' => 'promotion',
            'context_id' => $promotion->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('business_conversations', [
            'customer_user_id' => $customer->id,
            'context_key' => 'post:'.$post->id,
        ]);
        $this->assertDatabaseHas('business_conversations', [
            'customer_user_id' => $customer->id,
            'context_key' => 'promotion:'.$promotion->id,
        ]);
    }

    public function test_messaging_page_sends_and_marks_incoming_messages_as_read(): void
    {
        Notification::fake();

        [$owner, $business] = $this->publishedBusiness();
        $customer = User::factory()->create();
        $conversation = BusinessConversation::create([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'context_key' => 'business',
        ]);

        app(SendBusinessMessage::class)->handle($conversation, $owner, '¿En qué podemos ayudarte?');

        $this->actingAs($customer);

        $this->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('Enviar')
            ->assertDontSee('Pregúntale a Merkamigo');

        Livewire::test('pages::messages.index', ['conversation' => $conversation])
            ->assertSet('conversationId', $conversation->id)
            ->set('body', 'Necesito una cotización.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertNotNull($conversation->messages()->where('sender_user_id', $owner->id)->firstOrFail()->read_at);
        $this->assertDatabaseHas('business_messages', [
            'sender_user_id' => $customer->id,
            'body' => 'Necesito una cotización.',
        ]);
    }

    public function test_owner_can_select_the_business_contact_channel(): void
    {
        [$owner, $business] = $this->publishedBusiness();

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('contact_channel', 'phone')
            ->assertHasNoErrors();

        $this->assertSame('phone', $business->fresh()->contact_channel);
    }

    public function test_internal_contact_does_not_require_a_whatsapp_number_to_publish(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio con chat interno',
            'contact_channel' => 'merkamigo',
        ])->business;

        $missing = app(PublishStorefront::class)->missingFieldsFor($business);

        $this->assertNotContains('WhatsApp', $missing);
        $this->assertSame('merkamigo', $business->contact_channel);
    }

    public function test_unread_counter_is_safe_before_messaging_migration_runs(): void
    {
        $user = User::factory()->create();

        Schema::shouldReceive('hasTable')
            ->once()
            ->with('business_messages')
            ->andReturnFalse();

        $this->assertSame(0, $user->unreadBusinessMessagesCount());
    }

    /**
     * @return array{User, Business}
     */
    private function publishedBusiness(string $contactChannel = 'merkamigo'): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Mensajería',
            'whatsapp_number' => '+573001112233',
        ])->business;
        $business->update([
            'status' => 'publicado',
            'contact_channel' => $contactChannel,
        ]);

        return [$owner, $business];
    }
}
