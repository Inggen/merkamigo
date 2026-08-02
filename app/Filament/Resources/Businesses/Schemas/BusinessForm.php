<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Domain\Businesses\Models\BusinessAttribute;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Select::make('municipality_id')
                    ->label('Municipio principal')
                    ->relationship('municipality', 'name'),
                Select::make('municipalities')
                    ->label('Municipios adicionales')
                    ->relationship('municipalities', 'name')
                    ->multiple()
                    ->helperText('Otros municipios donde también atiende este negocio, además del principal.'),
                TextInput::make('zone'),
                TextInput::make('address'),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('whatsapp_number'),
                TextInput::make('logo_path'),
                TextInput::make('hours'),
                TextInput::make('social_links'),
                Textarea::make('payment_info')
                    ->columnSpanFull(),
                CheckboxList::make('attributes')
                    ->options(fn () => BusinessAttribute::where('is_active', true)->orderBy('name')->pluck('name', 'slug')),
                Select::make('status')
                    ->options([
                        'borrador' => 'Borrador',
                        'pendiente_revision' => 'Pendiente revision',
                        'publicado' => 'Publicado',
                        'suspendido' => 'Suspendido',
                        'archivado' => 'Archivado',
                    ])
                    ->default('borrador')
                    ->required(),
                Textarea::make('suspension_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('suspended_at'),
                DateTimePicker::make('featured_until'),
            ]);
    }
}
