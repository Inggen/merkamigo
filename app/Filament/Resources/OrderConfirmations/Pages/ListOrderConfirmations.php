<?php

namespace App\Filament\Resources\OrderConfirmations\Pages;

use App\Filament\Resources\OrderConfirmations\OrderConfirmationResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderConfirmations extends ListRecords
{
    protected static string $resource = OrderConfirmationResource::class;
}
