<?php

namespace App\Filament\Resources\Needs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('municipality_id')
                    ->relationship('municipality', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->required(),
                TextInput::make('zone'),
                TextInput::make('budget')
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->options([
                        'borrador' => 'Borrador',
                        'publicada' => 'Publicada',
                        'recibiendo_ofertas' => 'Recibiendo ofertas',
                        'seleccionada' => 'Seleccionada',
                        'cerrada' => 'Cerrada',
                        'vencida' => 'Vencida',
                        'cancelada' => 'Cancelada',
                    ])
                    ->required(),
                Textarea::make('suspension_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('suspended_at'),
            ]);
    }
}
