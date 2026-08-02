<?php

namespace Tests\Feature\Moderation;

use App\Domain\Moderation\Actions\ResolveSupportTicket;
use App\Domain\Moderation\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.9 del TODO: solicitud de soporte por escrito y su gestión desde
 * moderación.
 */
class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_submit_a_support_ticket_with_a_contact_email(): void
    {
        $response = $this->post(route('soporte.solicitud.guardar'), [
            'subject' => 'No puedo publicar mi vitrina',
            'message' => 'El botón de publicar no responde.',
            'contact_email' => 'ayuda@example.com',
        ]);

        $response->assertRedirect(route('soporte'));

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'No puedo publicar mi vitrina',
            'contact_email' => 'ayuda@example.com',
            'user_id' => null,
            'status' => SupportTicket::PENDIENTE,
        ]);
    }

    public function test_a_guest_must_provide_a_contact_email(): void
    {
        $this->post(route('soporte.solicitud.guardar'), [
            'subject' => 'Asunto',
            'message' => 'Mensaje.',
        ])->assertSessionHasErrors('contact_email');

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_an_authenticated_user_does_not_need_to_provide_a_contact_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('soporte.solicitud.guardar'), [
            'subject' => 'Asunto',
            'message' => 'Mensaje.',
        ])->assertRedirect(route('soporte'));

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'Asunto',
            'user_id' => $user->id,
        ]);
    }

    public function test_a_moderator_can_resolve_a_support_ticket(): void
    {
        $ticket = SupportTicket::create([
            'subject' => 'Asunto', 'message' => 'Mensaje.', 'contact_email' => 'a@example.com', 'status' => SupportTicket::PENDIENTE,
        ]);

        $moderator = User::factory()->create();
        app(ResolveSupportTicket::class)->handle($ticket, $moderator, SupportTicket::RESUELTO, 'Ya se corrigió.');

        $ticket->refresh();
        $this->assertSame(SupportTicket::RESUELTO, $ticket->status);
        $this->assertSame($moderator->id, $ticket->resolved_by);
        $this->assertNotNull($ticket->resolved_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'support_ticket.resolved',
            'subject_id' => $ticket->id,
        ]);
    }

    public function test_resolving_with_an_invalid_status_is_rejected(): void
    {
        $ticket = SupportTicket::create([
            'subject' => 'Asunto', 'message' => 'Mensaje.', 'contact_email' => 'a@example.com', 'status' => SupportTicket::PENDIENTE,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(ResolveSupportTicket::class)->handle($ticket, User::factory()->create(), 'estado_invalido', null);
    }

    public function test_contact_label_falls_back_to_the_registered_users_email(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $ticket = SupportTicket::create([
            'user_id' => $user->id, 'subject' => 'Asunto', 'message' => 'Mensaje.', 'status' => SupportTicket::PENDIENTE,
        ]);

        $this->assertSame('cliente@example.com', $ticket->contactLabel());
    }
}
