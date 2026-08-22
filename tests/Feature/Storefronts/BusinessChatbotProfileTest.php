<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\AnswerBusinessChatQuestion;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\BusinessChatbotProfile;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: negocios con el chatbot IA (plan Emprendedor o el
 * add-on) pueden darle al asistente un PDF, notas sueltas y el tono/jerga
 * con la que habla el negocio. Solo visible con esa entitlement.
 */
class BusinessChatbotProfileTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithChatbot(): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Chatbot'])->business;
        BusinessEntitlement::create(['business_id' => $business->id, 'key' => BusinessEntitlement::AI_CHATBOT]);

        $this->actingAs($owner);

        return $business->fresh();
    }

    /**
     * PDF válido mínimo (una sola página con texto) generado a mano: los
     * fixtures de `UploadedFile::fake()->create()` son bytes aleatorios,
     * no un PDF real, así que no sirven para probar la extracción de
     * texto — necesitamos que `smalot/pdfparser` pueda leerlo de verdad.
     */
    private function validPdfContent(string $text): string
    {
        $stream = "BT /F1 12 Tf 10 50 Td ({$text}) Tj ET\n";
        $objects = [
            1 => "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n",
            2 => "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n",
            3 => "3 0 obj<</Type/Page/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/MediaBox[0 0 200 100]/Contents 5 0 R>>endobj\n",
            4 => "4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n",
            5 => '5 0 obj<</Length '.strlen($stream).">>\nstream\n{$stream}endstream\nendobj\n",
        ];

        $body = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $num => $object) {
            $offsets[$num] = strlen($body);
            $body .= $object;
        }

        $xrefStart = strlen($body);
        $xref = "xref\n0 6\n0000000000 65535 f \n";

        for ($i = 1; $i <= 5; $i++) {
            $xref .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $body.$xref."trailer<</Size 6/Root 1 0 R>>\nstartxref\n{$xrefStart}\n%%EOF";
    }

    private function fakePdfUpload(string $text = 'Hola Mundo Merkamigo'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('info-negocio.pdf', $this->validPdfContent($text));
    }

    public function test_the_page_is_forbidden_for_a_business_without_the_chatbot_entitlement(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Chatbot'])->business;

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->assertForbidden();
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_page(): void
    {
        $business = $this->businessWithChatbot();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->assertForbidden();
    }

    public function test_owner_can_save_tone_and_notes(): void
    {
        $business = $this->businessWithChatbot();

        Livewire::test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->set('tone', 'Háblale de "mijito" y "sumercé", tono paisa cercano.')
            ->set('extra_notes', 'Llevamos 15 años en el barrio.')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $profile = BusinessChatbotProfile::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('Háblale de "mijito" y "sumercé", tono paisa cercano.', $profile->tone);
        $this->assertSame('Llevamos 15 años en el barrio.', $profile->extra_notes);
    }

    public function test_owner_can_upload_a_pdf_and_its_text_gets_extracted(): void
    {
        Storage::fake('private');

        $business = $this->businessWithChatbot();

        Livewire::test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->set('document', $this->fakePdfUpload('Hacemos domicilios gratis en Cajicá'))
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $profile = BusinessChatbotProfile::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('info-negocio.pdf', $profile->document_original_name);
        $this->assertStringContainsString('Hacemos domicilios gratis en Cajicá', $profile->document_text);
        Storage::disk('private')->assertExists($profile->document_path);
    }

    public function test_uploading_a_pdf_without_readable_text_shows_a_friendly_error(): void
    {
        Storage::fake('private');

        $business = $this->businessWithChatbot();

        Livewire::test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->set('document', UploadedFile::fake()->create('vacio.pdf', 10, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasErrors('document');

        $this->assertNull(BusinessChatbotProfile::where('business_id', $business->id)->first());
    }

    public function test_owner_can_remove_the_uploaded_document(): void
    {
        Storage::fake('private');

        $business = $this->businessWithChatbot();

        $component = Livewire::test('pages::emprendedores.negocios.chatbot', ['business' => $business->id])
            ->set('document', $this->fakePdfUpload())
            ->call('uploadDocument');

        $profile = BusinessChatbotProfile::where('business_id', $business->id)->firstOrFail();
        $storedPath = $profile->document_path;

        $component->call('removeDocument');

        $profile->refresh();
        $this->assertNull($profile->document_path);
        $this->assertNull($profile->document_original_name);
        $this->assertNull($profile->document_text);
        Storage::disk('private')->assertMissing($storedPath);
    }

    public function test_the_chat_answer_uses_the_tone_notes_and_document_from_the_profile(): void
    {
        $business = $this->businessWithChatbot();

        BusinessChatbotProfile::create([
            'business_id' => $business->id,
            'tone' => 'Trátalo de "mijito" y cierra con "¡a la orden!"',
            'extra_notes' => 'Solo aceptamos pedidos mínimos de $20.000.',
            'document_text' => 'Horario especial en diciembre: abrimos hasta las 9pm.',
        ]);

        $spy = new SpyGeneratesAssistedText;
        $this->app->instance(GeneratesAssistedText::class, $spy);

        app(AnswerBusinessChatQuestion::class)->handle($business, '¿Hacen domicilios?');

        $this->assertStringContainsString('Trátalo de "mijito"', $spy->prompt);
        $this->assertSame('Solo aceptamos pedidos mínimos de $20.000.', $spy->context['notas_del_negocio']);
        $this->assertSame('Horario especial en diciembre: abrimos hasta las 9pm.', $spy->context['documento_del_negocio']);
    }
}

class SpyGeneratesAssistedText implements GeneratesAssistedText
{
    public ?string $prompt = null;

    /** @var array<string, mixed> */
    public array $context = [];

    public function generate(string $prompt, array $context = []): ?string
    {
        $this->prompt = $prompt;
        $this->context = $context;

        return 'Listo mijito, a la orden.';
    }
}
