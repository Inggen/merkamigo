<?php

namespace App\Filament\Resources\BillingProducts\Pages;

use App\Filament\Resources\BillingProducts\BillingProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingProducts extends ListRecords
{
    protected static string $resource = BillingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
