<?php

namespace App\Filament\Resources\BusinessAttributes\Pages;

use App\Filament\Resources\BusinessAttributes\BusinessAttributeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessAttribute extends EditRecord
{
    protected static string $resource = BusinessAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
