<?php

namespace App\Filament\Resources\StandZones\Pages;

use App\Filament\Resources\StandZones\StandZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStandZone extends EditRecord
{
    protected static string $resource = StandZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
