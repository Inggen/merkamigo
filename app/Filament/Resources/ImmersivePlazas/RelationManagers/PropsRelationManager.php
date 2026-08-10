<?php

namespace App\Filament\Resources\ImmersivePlazas\RelationManagers;

use App\Domain\Immersive\Models\ImmersivePlazaProp;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Collection;

/**
 * IMM-013 del TODO inmersivo: puente que faltaba entre "editar una plaza" y
 * "colocar un objeto del catálogo en ella" — antes el único camino era el
 * recurso separado "Elementos de plaza", sin ningún contexto visual ni el
 * `immersive_plaza_id` precargado (había que saber que ese recurso existía
 * y elegir la plaza a ciegas de un select). Solo objetos con
 * `category != 'stand'` — los stands tienen su propio flujo comercial vía
 * `StandZone`/`StandSlot`/`StandAssignment`, no se colocan aquí.
 */
class PropsRelationManager extends RelationManager
{
    protected static string $relationship = 'props';

    protected static ?string $title = 'Elementos de la plaza';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('object_template_id')
                ->label('Objeto del catálogo')
                ->relationship('template', 'name', fn ($query) => $query->where('category', '!=', 'stand'))
                ->searchable()
                ->preload()
                ->required(),
            Fieldset::make('Posición de mundo')
                ->columns(3)
                ->schema([
                    TextInput::make('world_position.x')->label('X')->numeric()->default(0)->required(),
                    TextInput::make('world_position.y')->label('Y')->numeric()->default(0)->required(),
                    TextInput::make('world_position.z')->label('Z')->numeric()->default(0)->required(),
                ]),
            Fieldset::make('Rotación')
                ->columns(3)
                ->schema([
                    TextInput::make('rotation.x')->label('X')->numeric()->default(0),
                    TextInput::make('rotation.y')->label('Y')->numeric()->default(0),
                    TextInput::make('rotation.z')->label('Z')->numeric()->default(0),
                ]),
            TextInput::make('scale')
                ->label('Escala')
                ->numeric()
                ->default(1)
                ->required(),
            Toggle::make('collision_enabled')
                ->label('Validar colisiones')
                ->helperText('Solo aplica a objetos con modelo 3D (.glb).')
                ->default(false),
            Select::make('status')
                ->label('Estado')
                ->options(['borrador' => 'Borrador', 'confirmado' => 'Confirmado'])
                ->default('borrador')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('template.name')
            ->columns([
                TextColumn::make('template.name')
                    ->label('Objeto')
                    ->searchable(),
                TextColumn::make('template.category')
                    ->label('Categoría')
                    ->badge(),
                TextColumn::make('world_position')
                    ->label('Posición')
                    // `formatStateUsing` no sirve aquí: Filament trata un
                    // estado array como una lista y llama el formatter una
                    // vez POR ELEMENTO (x, y, z por separado) en vez de una
                    // vez con el array completo. `getStateUsing` evita esa
                    // resolución automática y arma el texto directo desde
                    // el registro.
                    ->getStateUsing(fn (ImmersivePlazaProp $record): string => sprintf(
                        'x:%s y:%s z:%s',
                        $record->world_position['x'],
                        $record->world_position['y'],
                        $record->world_position['z'],
                    )),
                TextColumn::make('source')
                    ->label('Origen')
                    ->formatStateUsing(fn (string $state): string => $state === 'auto_detected' ? 'Detección automática' : 'Manual')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirmSelected')
                        ->label('Marcar como confirmado')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (ImmersivePlazaProp $record) => $record->update([
                                'status' => 'confirmado',
                            ]));
                        }),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(static::$title)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        $this->getTabsContentComponent(),
                        RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
                        EmbeddedTable::make(),
                        RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER),
                    ]),
            ]);
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseRelationshipTable()
            ->recordTitleAttribute('template.name')
            ->heading('');
    }
}
