<?php

namespace App\Filament\Resources\PlazaLegendEntries\Schemas;

use App\Filament\Resources\ImmersiveObjectTemplates\Schemas\ImmersiveObjectTemplateForm;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class PlazaLegendEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(fn ($record) => "Color detectado: {$record->color_hex} ({$record->detected_pixel_count} muestras)"),
                Select::make('object_template_id')
                    ->label('Objeto del catálogo')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm(ImmersiveObjectTemplateForm::components())
                    ->helperText('Si el objeto que corresponde a este color todavía no existe, créalo aquí mismo sin salir de la leyenda.'),
            ]);
    }
}
