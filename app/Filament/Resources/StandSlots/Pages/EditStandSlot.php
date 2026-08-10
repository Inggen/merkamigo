<?php

namespace App\Filament\Resources\StandSlots\Pages;

use App\Filament\Resources\StandSlots\StandSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStandSlot extends EditRecord
{
    protected static string $resource = StandSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
