<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Actions\RemovePlatformKnowledgeDocument;
use App\Domain\Platform\Actions\SavePlatformKnowledgeDocument;
use App\Domain\Platform\Models\PlatformKnowledgeDocument;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use UnitEnum;

/**
 * Documento PDF de contexto general del asistente de la plataforma
 * (personaje flotante fuera de una vitrina) — pedido del usuario: un
 * resumen de todas las funcionalidades de Merkamigo, para que el
 * asistente lo consulte junto con categorías/municipios/preguntas
 * frecuentes (ver `AnswerPlatformChatQuestion`).
 *
 * @property-read Schema $form
 */
class PlatformAssistantKnowledge extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Base de conocimiento (asistente)';

    protected static ?string $title = 'Base de conocimiento del asistente general';

    protected string $view = 'filament.pages.platform-assistant-knowledge';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documento de contexto')
                    ->description('Un PDF con un resumen de las funcionalidades de Merkamigo. El asistente general lo usa como conocimiento real en cada respuesta, además de categorías, municipios y preguntas frecuentes — nunca reemplaza esos datos, solo los complementa.')
                    ->components([
                        FileUpload::make('document')
                            ->label('Subir PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->storeFiles(false)
                            ->helperText('Máximo 10 MB. Debe tener texto seleccionable (no una imagen escaneada).'),
                    ]),
            ])
            ->statePath('data');
    }

    public function knowledge(): PlatformKnowledgeDocument
    {
        return PlatformKnowledgeDocument::current();
    }

    public function save(): void
    {
        $document = $this->form->getState()['document'] ?? null;

        if (! $document instanceof UploadedFile) {
            Notification::make()
                ->title('Selecciona un PDF primero')
                ->warning()
                ->send();

            return;
        }

        try {
            app(SavePlatformKnowledgeDocument::class)->handle($document);
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->form->fill();

        Notification::make()
            ->title('Documento guardado')
            ->success()
            ->send();
    }

    public function remove(): void
    {
        app(RemovePlatformKnowledgeDocument::class)->handle($this->knowledge());

        $this->form->fill();

        Notification::make()
            ->title('Documento eliminado')
            ->success()
            ->send();
    }
}
