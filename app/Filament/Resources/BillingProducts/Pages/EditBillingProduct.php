<?php

namespace App\Filament\Resources\BillingProducts\Pages;

use App\Filament\Resources\BillingProducts\BillingProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingProduct extends EditRecord
{
    protected static string $resource = BillingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
