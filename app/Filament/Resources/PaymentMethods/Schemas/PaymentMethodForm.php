<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $set('slug', filled($state) ? Str::slug($state) : null);
                    }),
                TextInput::make('slug')
                    ->required(),
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('payment-methods')
                    ->maxSize(config('media.payment_method_logo.max_kb'))
                    ->helperText('Se muestra en la vitrina, junto a las demás formas de pago que acepte el negocio.'),
                TextInput::make('position')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->helperText('Las formas de pago se listan de menor a mayor.'),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Solo las formas de pago activas puede escogerlas un negocio.')
                    ->required(),
            ]);
    }
}
