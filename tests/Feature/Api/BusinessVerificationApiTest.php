<?php

namespace Tests\Feature\Api;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * `POST /api/v1/businesses/{business}/verificacion` (5.1/3.1 del TODO).
 * Multipart, reutiliza `RequestBusinessVerification` — mismo disco
 * privado que el formulario web.
 */
class BusinessVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_owner_can_request_verification_with_a_document(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Verificable'])->business;
        Sanctum::actingAs($owner);

        $response = $this->post(route('api.v1.businesses.verificacion.store', $business), [
            'legal_name' => 'Negocio Verificable SAS',
            'contact_name' => 'Ana Pérez',
            'contact_document_type' => 'CC',
            'contact_document_number' => '123456789',
            'document' => UploadedFile::fake()->create('camara-comercio.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated()->assertJsonPath('data.status', BusinessVerification::EN_REVISION);
        $this->assertDatabaseHas('business_verifications', ['business_id' => $business->id, 'status' => BusinessVerification::EN_REVISION]);
        $this->assertArrayNotHasKey('verification_document_path', $response->json('data'));
    }

    public function test_someone_outside_the_business_cannot_request_verification(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.businesses.verificacion.store', $business), [
            'legal_name' => 'X', 'contact_name' => 'X', 'contact_document_type' => 'CC', 'contact_document_number' => '1',
        ])->assertForbidden();
    }
}
