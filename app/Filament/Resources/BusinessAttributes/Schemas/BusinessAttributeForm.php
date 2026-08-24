<?php

namespace App\Filament\Resources\BusinessAttributes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;

class BusinessAttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('icon')
                    ->label('Ícono (opcional)')
                    ->placeholder('Ej. credit-card, users, truck, map-pin, leaf, sparkles')
                    ->helperText('Nombre de un ícono Heroicons (outline), sin el prefijo. Ej. "credit-card" para pagos, "truck" para domicilios. Si lo dejas vacío, la tarjeta se muestra solo con el nombre, como antes.')
                    ->maxLength(60)
                    ->rule(
                        fn () => fn (string $attribute, $value, \Closure $fail) => filled($value) && ! View::exists('flux::icon.'.$value)
                            ? $fail('No reconocemos ese nombre de ícono. Revisa que esté escrito igual que en la lista de Heroicons (ej. "credit-card", no "CreditCard" ni "credit_card").')
                            : null
                    ),
                TextInput::make('description')
                    ->label('Descripción corta (opcional)')
                    ->placeholder('Ej. Paga fácil y seguro con tus medios digitales favoritos.')
                    ->helperText('Una o dos frases que se muestran debajo del nombre en la vitrina pública.')
                    ->maxLength(160),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
