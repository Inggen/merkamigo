<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Pages;

use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImmersiveObjectTemplates extends ListRecords
{
    protected static string $resource = ImmersiveObjectTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
