<?php

namespace App\Filament\Resources\WebhookSubscriptions\Schemas;

use App\Domain\Platform\Actions\DispatchOutboundWebhook;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WebhookSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('business_id')
                ->label('Negocio (vacío = todos los negocios)')
                ->relationship('business', 'name')
                ->searchable()
                ->nullable(),
            TextInput::make('url')
                ->label('URL de destino')
                ->url()
                ->required(),
            TextInput::make('secret')
                ->label('Secreto (firma HMAC-SHA256)')
                ->default(fn () => Str::random(40))
                ->required(),
            CheckboxList::make('subscribed_events')
                ->label('Eventos suscritos')
                ->options(array_combine(DispatchOutboundWebhook::SUBSCRIBABLE_EVENTS, DispatchOutboundWebhook::SUBSCRIBABLE_EVENTS))
                ->columns(2)
                ->required(),
            Toggle::make('is_active')
                ->default(true)
                ->required(),
        ]);
    }
}
