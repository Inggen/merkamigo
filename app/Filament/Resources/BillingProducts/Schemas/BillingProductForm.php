<?php

namespace App\Filament\Resources\BillingProducts\Schemas;

use App\Domain\Billing\Models\BillingProduct;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

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
                    ->label('Precio (COP)')
                    ->numeric()
                    ->required()
                    ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
                    ->stripCharacters('.')
                    // El campo se escribe/muestra en pesos (ej. 29.900);
                    // `price_cents` sigue guardando centavos porque Wompi
                    // exige `amount_in_cents` en su API.
                    ->formatStateUsing(fn (?int $state): ?int => filled($state) ? intdiv($state, 100) : null)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? ((int) $state) * 100 : null)
                    ->helperText('Ej: 29.900 = $29.900 COP.'),
                Select::make('kind')
                    ->label('Tipo')
                    ->options([
                        BillingProduct::DESTACADO => 'Destacado',
                        BillingProduct::VITRINA_ASISTIDA => 'Vitrina asistida',
                        BillingProduct::KIT_ARRANCA_BONITO => 'Kit Arranca Bonito',
                        BillingProduct::ENTITLEMENT => 'Desbloqueo de capacidad (add-on)',
                    ])
                    ->required(),
                KeyValue::make('payload')
                    ->label('Datos adicionales (ej. days: 7 para destacados, entitlement_key/expires_in_days para add-ons)')
                    ->keyLabel('Clave')
                    ->valueLabel('Valor'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
