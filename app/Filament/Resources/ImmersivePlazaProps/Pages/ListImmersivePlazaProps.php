<?php

namespace App\Filament\Resources\ImmersivePlazaProps\Pages;

use App\Filament\Resources\ImmersivePlazaProps\ImmersivePlazaPropResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImmersivePlazaProps extends ListRecords
{
    protected static string $resource = ImmersivePlazaPropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
