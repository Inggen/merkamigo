<?php

namespace App\Filament\Resources\ImmersivePlazas\Pages;

use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Resources\ImmersivePlazas\ImmersivePlazaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditImmersivePlazaSpatialEditor extends Page
{
    use InteractsWithRecord {
        getRecord as getBaseRecord;
    }

    protected static string $resource = ImmersivePlazaResource::class;

    protected string $view = 'filament.immersive.plaza-spatial-editor-page';

    protected Width|string|null $maxContentWidth = 'w-full max-w-none';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(ImmersivePlazaResource::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string
    {
        return 'Editor espacial (3D): '.$this->getRecord()->name;
    }

    public function getRecord(): ImmersivePlaza
    {
        /** @var ImmersivePlaza */
        return $this->getBaseRecord();
    }

    protected function getHeaderActions(): array
    {
        return [
            ImmersivePlazaResource::enterExperienceAction(),
            Action::make('editPlaza')
                ->label('Editar')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('danger')
                ->url(ImmersivePlazaResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
