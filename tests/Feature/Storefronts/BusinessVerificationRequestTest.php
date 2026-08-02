<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Actions\ReviewBusinessVerification;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 3.1 del TODO: solicitud de verificación por el propio negocio.
 */
class BusinessVerificationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_verification_with_a_document(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Verificable'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.verificacion', ['business' => $business->id])
            ->set('legal_name', 'Negocio Verificable SAS')
            ->set('contact_name', 'Ana Dueña')
            ->set('contact_document_type', 'CC')
            ->set('contact_document_number', '123456789')
            ->set('document', UploadedFile::fake()->create('camara-comercio.pdf', 200, 'application/pdf'))
            ->call('submit')
            ->assertHasNoErrors();

        $verification = BusinessVerification::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(BusinessVerification::EN_REVISION, $verification->status);
        $this->assertSame('Negocio Verificable SAS', $verification->legal_name);
        $this->assertNotNull($verification->verification_document_path);
        Storage::disk('private')->assertExists($verification->verification_document_path);
        Storage::disk('public')->assertMissing($verification->verification_document_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'business.verification_requested',
            'subject_id' => $verification->id,
        ]);
    }

    public function test_owner_cannot_request_again_while_already_in_review(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio En Revision'])->business;

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::EN_REVISION,
            'legal_name' => 'Ya enviado',
            'contact_name' => 'Alguien',
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.verificacion', ['business' => $business->id])
            ->assertSet('canRequest', false)
            ->assertDontSee('Enviar solicitud');
    }

    public function test_owner_can_request_again_after_being_asked_for_adjustments(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajustes'])->business;

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::EN_REVISION,
            'legal_name' => 'Primer intento',
            'contact_name' => 'Alguien',
        ]);

        $moderator = User::factory()->create();
        app(ReviewBusinessVerification::class)->handle($verification, $moderator, BusinessVerification::REQUIERE_AJUSTES, 'Falta el documento.', null, 'basica');

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.verificacion', ['business' => $business->id])
            ->assertSet('canRequest', true)
            ->set('legal_name', 'Segundo intento SAS')
            ->set('contact_name', 'Ana Dueña')
            ->set('contact_document_type', 'CC')
            ->set('contact_document_number', '123456789')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, BusinessVerification::where('business_id', $business->id)->count());
        $this->assertSame(BusinessVerification::EN_REVISION, $verification->fresh()->status);
        $this->assertSame('Segundo intento SAS', $verification->fresh()->legal_name);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_page(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::emprendedores.negocios.verificacion', ['business' => $businessA->id])
            ->assertForbidden();
    }

    public function test_the_document_download_route_requires_team_membership(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Documento'])->business;

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::EN_REVISION,
            'verification_document_path' => 'business-verifications/'.$business->id.'/doc.pdf',
        ]);
        Storage::disk('private')->put($verification->verification_document_path, 'contenido');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->get(route('emprendedores.negocios.verificacion.documento', $business))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('emprendedores.negocios.verificacion.documento', $business))
            ->assertRedirect();
    }
}
