<?php

namespace App\Filament\Resources\ImmersivePlazas\Pages;

use App\Filament\Resources\ImmersivePlazas\ImmersivePlazaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImmersivePlazas extends ListRecords
{
    protected static string $resource = ImmersivePlazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
