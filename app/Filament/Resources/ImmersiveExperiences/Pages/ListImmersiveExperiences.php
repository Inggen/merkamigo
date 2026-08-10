<?php

namespace App\Filament\Resources\ImmersiveExperiences\Pages;

use App\Filament\Resources\ImmersiveExperiences\ImmersiveExperienceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImmersiveExperiences extends ListRecords
{
    protected static string $resource = ImmersiveExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
