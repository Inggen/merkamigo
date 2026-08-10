<?php

namespace App\Filament\Resources\ImmersivePlazaProps\Pages;

use App\Filament\Resources\ImmersivePlazaProps\ImmersivePlazaPropResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImmersivePlazaProp extends EditRecord
{
    protected static string $resource = ImmersivePlazaPropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
