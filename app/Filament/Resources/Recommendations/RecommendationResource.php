<?php

namespace App\Filament\Resources\Recommendations;

use App\Domain\Trust\Models\Recommendation;
use App\Filament\Resources\Recommendations\Pages\EditRecommendation;
use App\Filament\Resources\Recommendations\Pages\ListRecommendations;
use App\Filament\Resources\Recommendations\Schemas\RecommendationForm;
use App\Filament\Resources\Recommendations\Tables\RecommendationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static UnitEnum|string|null $navigationGroup = 'Confianza';

    public static function form(Schema $schema): Schema
    {
        return RecommendationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecommendationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecommendations::route('/'),
            'edit' => EditRecommendation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return self::canViewAny();
    }
}
