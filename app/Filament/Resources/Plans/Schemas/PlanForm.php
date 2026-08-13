<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Domain\Billing\Models\Plan;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PlanForm
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
                    ->label('Precio (COP, vacío = gratuito)')
                    ->numeric()
                    ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
                    ->stripCharacters('.')
                    // El campo se escribe/muestra en pesos (ej. 19.900);
                    // `price_cents` sigue guardando centavos porque Wompi
                    // exige `amount_in_cents` en su API.
                    ->formatStateUsing(fn (?int $state): ?int => filled($state) ? intdiv($state, 100) : null)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? ((int) $state) * 100 : null)
                    ->helperText('Ej: 19.900 = $19.900 COP. Déjalo vacío para un plan gratuito.'),
                Select::make('billing_period')
                    ->label('Periodicidad')
                    ->options([
                        Plan::MENSUAL => 'Mensual',
                        Plan::ANUAL => 'Anual',
                        Plan::PAGO_UNICO => 'Pago único',
                    ])
                    ->required(),
                TextInput::make('trial_days')
                    ->label('Días de prueba')
                    ->numeric()
                    ->default(0)
                    ->required(),
                KeyValue::make('limits')
                    ->label('Límites (clave: max_products, max_members, max_featured_days, max_storefronts — vacío = sin límite)')
                    ->keyLabel('Clave')
                    ->valueLabel('Valor'),
                TagsInput::make('features')
                    ->label('Características del plan')
                    ->helperText('Lo que ve el emprendedor al comparar planes, ej: "Vitrina destacada 7 días". Escribe una y presiona Enter para agregar la siguiente.')
                    ->placeholder('Agregar característica')
                    ->columnSpanFull(),
                TextInput::make('position')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
