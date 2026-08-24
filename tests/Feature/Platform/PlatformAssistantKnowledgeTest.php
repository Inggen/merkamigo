<?php

namespace Tests\Feature\Platform;

use App\Domain\Discovery\Actions\AnswerPlatformChatQuestion;
use App\Domain\Platform\Models\PlatformKnowledgeDocument;
use App\Filament\Pages\PlatformAssistantKnowledge;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Documento PDF de contexto general del asistente de la plataforma
 * (pedido del usuario) — el admin lo sube, se extrae el texto una sola
 * vez, y `AnswerPlatformChatQuestion` lo manda como contexto real en
 * cada pregunta.
 */
class PlatformAssistantKnowledgeTest extends TestCase
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

        $body .= $xref."trailer<</Size 6/Root 1 0 R>>\nstartxref\n{$xrefStart}\n%%EOF";

        return $body;
    }

    private function fakePdfUpload(string $text = 'Merkamigo tiene chatbot IA y generador de imágenes.'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('funcionalidades.pdf', $this->validPdfContent($text));
    }

    public function test_an_admin_can_upload_the_knowledge_document(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(PlatformAssistantKnowledge::class)
            ->set('data.document', $this->fakePdfUpload())
            ->call('save');

        $knowledge = PlatformKnowledgeDocument::current();

        $this->assertSame('funcionalidades.pdf', $knowledge->document_original_name);
        $this->assertStringContainsString('Merkamigo tiene chatbot IA', $knowledge->document_text);
    }

    public function test_an_admin_can_remove_the_knowledge_document(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $component = Livewire::test(PlatformAssistantKnowledge::class)
            ->set('data.document', $this->fakePdfUpload())
            ->call('save');

        $this->assertTrue(PlatformKnowledgeDocument::current()->hasDocument());

        $component->call('remove');

        $this->assertFalse(PlatformKnowledgeDocument::current()->hasDocument());
    }

    public function test_a_regular_user_cannot_access_the_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(PlatformAssistantKnowledge::class)->assertForbidden();
    }

    public function test_the_platform_assistant_receives_the_document_text_as_context(): void
    {
        PlatformKnowledgeDocument::create([
            'document_original_name' => 'funcionalidades.pdf',
            'document_text' => 'Merkamigo tiene chatbot IA y generador de imágenes de portada.',
        ]);

        $spy = new SpyGeneratesAssistedText;
        $this->app->instance(GeneratesAssistedText::class, $spy);

        app(AnswerPlatformChatQuestion::class)->handle('¿Qué es Merkamigo?');

        $this->assertSame(
            'Merkamigo tiene chatbot IA y generador de imágenes de portada.',
            $spy->context['documento_de_referencia'] ?? null,
        );
    }

    public function test_the_platform_assistant_gets_a_null_document_when_none_is_configured(): void
    {
        $spy = new SpyGeneratesAssistedText;
        $this->app->instance(GeneratesAssistedText::class, $spy);

        app(AnswerPlatformChatQuestion::class)->handle('¿Qué es Merkamigo?');

        $this->assertArrayHasKey('documento_de_referencia', $spy->context);
        $this->assertNull($spy->context['documento_de_referencia']);
    }
}

class SpyGeneratesAssistedText implements GeneratesAssistedText
{
    /** @var array<string, mixed> */
    public array $context = [];

    public function generate(string $prompt, array $context = []): ?string
    {
        $this->context = $context;

        return json_encode(['respuesta' => 'Ok.', 'accion' => null]);
    }
}
