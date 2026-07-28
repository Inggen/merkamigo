<?php

namespace Tests\Feature\Moderation;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.3/1.4/1.9 del TODO: reportar contenido y resolverlo desde moderación.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Reportable',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Descripción.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business->fresh(), $owner);

        return $business->fresh();
    }

    public function test_a_guest_can_report_a_business_without_an_account(): void
    {
        $business = $this->publishedBusiness();

        $response = $this->post(route('reportes.guardar.negocio', $business), [
            'reason' => 'contenido_inapropiado',
            'details' => 'Detalles del reporte.',
            'reporter_email' => 'reportante@example.com',
        ]);

        $response->assertRedirect(route('vitrinas.show', $business));

        $this->assertDatabaseHas('reports', [
            'reportable_type' => $business->getMorphClass(),
            'reportable_id' => $business->id,
            'reason' => 'contenido_inapropiado',
            'reporter_email' => 'reportante@example.com',
            'status' => Report::PENDIENTE,
        ]);
    }

    public function test_a_guest_can_report_a_product(): void
    {
        $business = $this->publishedBusiness();
        $product = $business->products()->first();
        $product->update(['status' => 'publicado']);

        $response = $this->post(route('reportes.guardar.producto', [$business, $product]), [
            'reason' => 'spam',
        ]);

        $response->assertRedirect(route('vitrinas.product', [$business, $product]));

        $this->assertDatabaseHas('reports', [
            'reportable_type' => $product->getMorphClass(),
            'reportable_id' => $product->id,
            'reason' => 'spam',
        ]);
    }

    public function test_reporting_requires_a_valid_reason(): void
    {
        $business = $this->publishedBusiness();

        $this->post(route('reportes.guardar.negocio', $business), ['reason' => 'motivo_inventado'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_a_moderator_can_resolve_a_report(): void
    {
        $business = $this->publishedBusiness();
        $report = Report::create([
            'reportable_type' => $business->getMorphClass(),
            'reportable_id' => $business->id,
            'reason' => 'contenido_inapropiado',
            'status' => Report::PENDIENTE,
        ]);

        $moderator = User::factory()->create();
        app(ResolveReport::class)->handle($report, $moderator, Report::RESUELTO, 'Se corrigió el contenido.');

        $report->refresh();
        $this->assertSame(Report::RESUELTO, $report->status);
        $this->assertSame($moderator->id, $report->resolved_by);
        $this->assertNotNull($report->resolved_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report.resolved',
            'subject_id' => $report->id,
        ]);
    }

    public function test_reportable_label_describes_the_content(): void
    {
        $business = $this->publishedBusiness();
        $report = Report::create([
            'reportable_type' => $business->getMorphClass(),
            'reportable_id' => $business->id,
            'reason' => 'spam',
            'status' => Report::PENDIENTE,
        ]);

        $this->assertSame('Negocio: '.$business->name, $report->reportableLabel());
    }
}
