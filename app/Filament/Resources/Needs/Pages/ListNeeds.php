<?php

namespace App\Filament\Resources\Needs\Pages;

use App\Filament\Resources\Needs\NeedResource;
use Filament\Resources\Pages\ListRecords;

class ListNeeds extends ListRecords
{
    protected static string $resource = NeedResource::class;
}
