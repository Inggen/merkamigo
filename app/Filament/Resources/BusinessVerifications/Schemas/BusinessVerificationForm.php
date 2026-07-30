<?php

namespace App\Filament\Resources\BusinessVerifications\Schemas;

use App\Domain\Trust\Models\BusinessVerification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BusinessVerificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('business_id')->relationship('business', 'name')->required()->disabled(),
            Select::make('level')
                ->options([
                    'basica' => 'Básica',
                    'avanzada' => 'Avanzada',
                ])
                ->required(),
            Select::make('status')
                ->options([
                    BusinessVerification::SIN_INICIAR => 'Sin iniciar',
                    BusinessVerification::EN_REVISION => 'En revisión',
                    BusinessVerification::REQUIERE_AJUSTES => 'Requiere ajustes',
                    BusinessVerification::VERIFICADA => 'Verificada',
                    BusinessVerification::VENCIDA => 'Vencida',
                    BusinessVerification::REVOCADA => 'Revocada',
                ])
                ->required(),
            TextInput::make('legal_name'),
            TextInput::make('contact_name'),
            TextInput::make('contact_document_type'),
            TextInput::make('contact_document_number'),
            TextInput::make('verification_document_path')
                ->label('Documento privado')
                ->disabled(),
            Textarea::make('request_note')->label('Nota de solicitud')->columnSpanFull(),
            Textarea::make('review_note')->label('Nota de revisión')->columnSpanFull(),
            DateTimePicker::make('expires_at')->label('Vence en'),
        ]);
    }
}
