<?php

namespace App\Filament\Resources\Recommendations\Schemas;

use App\Domain\Trust\Models\Recommendation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RecommendationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('business_id')->relationship('business', 'name')->required()->disabled(),
            Select::make('status')
                ->options([
                    Recommendation::PENDIENTE => 'Pendiente',
                    Recommendation::PUBLICADA => 'Publicada',
                    Recommendation::OCULTA => 'Oculta',
                ])
                ->required(),
            Select::make('rating')
                ->label('Calificación')
                ->options(array_combine(
                    range(Recommendation::MIN_RATING, Recommendation::MAX_RATING),
                    range(Recommendation::MIN_RATING, Recommendation::MAX_RATING),
                ))
                ->placeholder('Sin calificar'),
            Textarea::make('body')->label('Recomendación')->required()->columnSpanFull(),
            TagsInput::make('tags')->label('Etiquetas'),
            Textarea::make('business_response')->label('Respuesta del negocio')->columnSpanFull(),
        ]);
    }
}
