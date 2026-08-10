<?php

namespace App\Filament\Resources\StandZones\Pages;

use App\Filament\Resources\StandZones\StandZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStandZones extends ListRecords
{
    protected static string $resource = StandZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
