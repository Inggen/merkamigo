<?php

namespace App\Filament\Resources\BillingProducts\Pages;

use App\Filament\Resources\BillingProducts\BillingProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingProduct extends CreateRecord
{
    protected static string $resource = BillingProductResource::class;
}
