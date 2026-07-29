<?php

namespace App\Filament\Resources\BusinessAttributes\Pages;

use App\Filament\Resources\BusinessAttributes\BusinessAttributeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessAttributes extends ListRecords
{
    protected static string $resource = BusinessAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
