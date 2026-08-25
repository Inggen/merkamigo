<?php

namespace App\Filament\Resources\ImmersivePlazaProps\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Anuncios (imágenes) que rotan en carrusel sobre la pantalla de esta
 * colocación de billboard (`ImmersivePlazaProp`). Solo tiene efecto visible
 * en la escena si la plantilla del objeto trae `screen_material_name`
 * (campo "Material de la pantalla (anuncios)" en el catálogo). Activar/
 * desactivar un anuncio no lo borra: solo decide si entra en el carrusel
 * que arma `billboard-ad-utils.js` en la experiencia inmersiva.
 */
class AdsRelationManager extends RelationManager
{
    protected static string $relationship = 'ads';

    protected static ?string $title = 'Anuncios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image_path')
                ->label('Imagen')
                ->image()
                ->required()
                ->disk('public')
                ->directory('immersive-billboard-ads'),
            Toggle::make('is_active')
                ->label('Activo')
                ->helperText('Solo los anuncios activos entran al carrusel que se ve en la plaza.')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image_path')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Imagen')
                    ->disk('public'),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
