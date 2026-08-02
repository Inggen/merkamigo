<?php

namespace App\Filament\Resources\BillingProducts\Schemas;

use App\Domain\Billing\Models\BillingProduct;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BillingProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price_cents')
                    ->label('Precio (en centavos COP)')
                    ->numeric()
                    ->required()
                    ->helperText('Ej: 990000 = $9.900 COP.'),
                Select::make('kind')
                    ->label('Tipo')
                    ->options([
                        BillingProduct::DESTACADO => 'Destacado',
                        BillingProduct::VITRINA_ASISTIDA => 'Vitrina asistida',
                        BillingProduct::KIT_ARRANCA_BONITO => 'Kit Arranca Bonito',
                    ])
                    ->required(),
                KeyValue::make('payload')
                    ->label('Datos adicionales (ej. days: 7 para destacados)')
                    ->keyLabel('Clave')
                    ->valueLabel('Valor'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
