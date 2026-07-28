<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('business_id')
                    ->relationship('business', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('type')
                    ->options(['producto' => 'Producto', 'servicio' => 'Servicio'])
                    ->default('producto')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                Select::make('price_type')
                    ->options([
                        'exacto' => 'Exacto',
                        'desde' => 'Desde',
                        'consultar' => 'Consultar',
                        'sin_precio' => 'Sin precio',
                    ])
                    ->default('exacto')
                    ->required(),
                TextInput::make('unit'),
                TextInput::make('promo_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('promo_label'),
                Toggle::make('is_available')
                    ->required(),
                Select::make('status')
                    ->options([
                        'borrador' => 'Borrador',
                        'publicado' => 'Publicado',
                        'agotado' => 'Agotado',
                        'archivado' => 'Archivado',
                    ])
                    ->default('borrador')
                    ->required(),
                Textarea::make('suspension_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('suspended_at'),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
