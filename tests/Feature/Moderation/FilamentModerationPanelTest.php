<?php

namespace Tests\Feature\Moderation;

use App\Domain\Businesses\Models\Business;
use App\Domain\Moderation\Models\Report;
use App\Domain\Moderation\Models\SupportTicket;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 1.9 del TODO: prueba de extremo a extremo del panel de Filament (no solo
 * de las acciones de dominio por separado), a través del ciclo de vida
 * real de Livewire que usa Filament — el mismo tipo de escenario donde
 * apareció el bug del 403 en los paneles del emprendedor.
 */
class FilamentModerationPanelTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    private function publishedBusiness(): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Panel Filament', 'whatsapp_number' => '+573001112233',
        ])->business;
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $business->update(['logo_path' => 'businesses/1/logo.jpg', 'status' => 'publicado']);

        return $business->fresh();
    }

    public function test_the_business_list_renders_and_the_suspend_table_action_works(): void
    {
        $business = $this->publishedBusiness();

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(ListBusinesses::class)
            ->assertSuccessful()
            ->callTableAction('suspend', $business, data: ['reason' => 'contenido_inapropiado'])
            ->assertHasNoTableActionErrors();

        $business->refresh();
        $this->assertSame('suspendido', $business->status);
        $this->assertSame('Contenido inapropiado', $business->suspension_reason);
    }

    public function test_the_report_list_renders_and_the_resolve_table_action_works(): void
    {
        $business = $this->publishedBusiness();
        $report = Report::create([
            'reportable_type' => $business->getMorphClass(),
            'reportable_id' => $business->id,
            'reason' => 'spam',
            'status' => Report::PENDIENTE,
        ]);

        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');
        $this->actingAs($moderator);

        Livewire::test(ListReports::class)
            ->assertSuccessful()
            ->callTableAction('resolve', $report, data: ['note' => 'Revisado.'])
            ->assertHasNoTableActionErrors();

        $report->refresh();
        $this->assertSame(Report::RESUELTO, $report->status);
        $this->assertSame($moderator->id, $report->resolved_by);
    }

    public function test_the_support_ticket_list_renders_and_the_resolve_table_action_works(): void
    {
        $ticket = SupportTicket::create([
            'subject' => 'No puedo publicar', 'message' => 'El botón no responde.',
            'contact_email' => 'a@example.com', 'status' => SupportTicket::PENDIENTE,
        ]);

        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');
        $this->actingAs($moderator);

        Livewire::test(ListSupportTickets::class)
            ->assertSuccessful()
            ->callTableAction('resolve', $ticket, data: ['note' => 'Ya se corrigió.'])
            ->assertHasNoTableActionErrors();

        $ticket->refresh();
        $this->assertSame(SupportTicket::RESUELTO, $ticket->status);
        $this->assertSame($moderator->id, $ticket->resolved_by);
    }
}
