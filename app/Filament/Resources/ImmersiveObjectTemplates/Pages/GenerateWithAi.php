<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Pages;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * IMM-020b del TODO inmersivo: shell de la página de recurso — resuelve y
 * autoriza el registro, y delega todo el flujo interactivo (subir fotos,
 * generar/refinar, previsualizar en vivo, guardar) al componente Livewire
 * `App\Livewire\ImmersiveObjectTemplateAiGenerator`, embebido en
 * `filament.immersive.object-template-ai-generator`.
 */
class GenerateWithAi extends Page
{
    use InteractsWithRecord {
        getRecord as getBaseRecord;
    }

    protected static string $resource = ImmersiveObjectTemplateResource::class;

    protected string $view = 'filament.immersive.object-template-ai-generator';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(ImmersiveObjectTemplateResource::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string
    {
        return 'Generar con IA: '.$this->getRecord()->name;
    }

    public function getRecord(): ImmersiveObjectTemplate
    {
        /** @var ImmersiveObjectTemplate */
        return $this->getBaseRecord();
    }
}
