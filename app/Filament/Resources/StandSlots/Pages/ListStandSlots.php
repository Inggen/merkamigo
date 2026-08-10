<?php

namespace App\Filament\Resources\StandSlots\Pages;

use App\Filament\Resources\StandSlots\StandSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStandSlots extends ListRecords
{
    protected static string $resource = StandSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
