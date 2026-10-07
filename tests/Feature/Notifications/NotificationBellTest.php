<?php

namespace Tests\Feature\Notifications;

use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Messaging\Actions\SendBusinessMessage;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * Campana de notificaciones del header (pedido del usuario, 2026-10-06):
 * "agrega un sistema de notificaciones con un desplegable en el header y
 * quita el botón de mensajes, deja en las notificaciones que algunos
 * sean de tipo mensajes y que te lleven directamente al mensaje que
 * envían." No hay lógica nueva de generación de notificaciones — estas
 * pruebas confirman que el desplegable LEE correctamente las que el
 * resto del proyecto ya genera, y que un mensaje nuevo navega a la
 * conversación exacta, no a la bandeja general.
 */
class NotificationBellTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    /**
     * `RegisterLoyaltyPurchase::handle()` ya envía `PointsAccrued` por su
     * cuenta como parte de la acción real — no hace falta (ni hay que)
     * notificar manualmente encima, o quedarían dos notificaciones por
     * cada compra.
     */
    private function accrueLoyaltyNotification(User $customer, string $idempotencyKey = 'accrue'): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 20_000_00, $idempotencyKey);
    }

    public function test_it_lists_the_users_notifications_with_an_unread_count(): void
    {
        $customer = User::factory()->create();
        $this->accrueLoyaltyNotification($customer);

        Livewire::actingAs($customer)
            ->test('notification-bell')
            ->assertSet('unreadCount', 1)
            ->assertSee('Ganaste')
            ->assertSee('Merkapuntos');
    }

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_url(): void
    {
        $customer = User::factory()->create();
        $this->accrueLoyaltyNotification($customer);

        $notification = $customer->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        Livewire::actingAs($customer)
            ->test('notification-bell')
            ->call('open', $notification->id)
            ->assertRedirect(route('clientes.actividad'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_new_message_notification_links_directly_to_that_conversation_not_the_inbox(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Mensajería', 'whatsapp_number' => '+573001112233',
        ])->business;
        $business->update(['status' => 'publicado', 'contact_channel' => 'merkamigo']);

        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('vitrinas.contact', $business));
        $conversation = BusinessConversation::where('business_id', $business->id)->firstOrFail();

        app(SendBusinessMessage::class)->handle($conversation, $customer, 'Quiero conocer la disponibilidad.');

        $notification = $owner->notifications()->firstOrFail();
        $this->assertSame('business_message', $notification->data['type']);
        $this->assertSame(route('messages.show', $conversation), $notification->data['url']);

        Livewire::actingAs($owner)
            ->test('notification-bell')
            ->assertSee('Quiero conocer la disponibilidad')
            ->call('open', $notification->id)
            ->assertRedirect(route('messages.show', $conversation));
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $customer = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->accrueLoyaltyNotification($customer, "mark-all-{$i}");
        }

        $this->assertSame(3, $customer->unreadNotifications()->count());

        Livewire::actingAs($customer)
            ->test('notification-bell')
            ->assertSet('unreadCount', 3)
            ->call('markAllRead')
            ->assertSet('unreadCount', 0);

        $this->assertSame(0, $customer->unreadNotifications()->count());
    }

    public function test_a_user_without_notifications_sees_an_empty_state(): void
    {
        $customer = User::factory()->create();

        Livewire::actingAs($customer)
            ->test('notification-bell')
            ->assertSet('unreadCount', 0)
            ->assertSee('Todavía no tienes novedades');
    }
}
