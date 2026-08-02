<?php

namespace App\Filament\Resources\WebhookSubscriptions\Pages;

use App\Filament\Resources\WebhookSubscriptions\WebhookSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookSubscription extends CreateRecord
{
    protected static string $resource = WebhookSubscriptionResource::class;
}
