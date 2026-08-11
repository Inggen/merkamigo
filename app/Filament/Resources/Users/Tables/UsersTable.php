<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Platform\Actions\StartUserImpersonation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('experience')
                    ->label('Experiencia')
                    ->badge(),
                TextColumn::make('platform_role')
                    ->label('Rol de plataforma')
                    ->state(fn (User $record) => $record->platformRoleName() ?? '—')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('experience')
                    ->options(['cliente' => 'Cliente', 'emprendedor' => 'Emprendedor']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Entrar como')
                    ->icon(Heroicon::OutlinedArrowRightCircle)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Entrar como usuario')
                    ->modalDescription('Abrirá la plataforma exactamente como este usuario. Podrás volver a tu cuenta de superadmin desde una franja visible en el panel.')
                    ->visible(fn (User $record): bool => (auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false) && auth()->id() !== $record->id)
                    ->action(function (User $record, StartUserImpersonation $startUserImpersonation) {
                        $startUserImpersonation->handle(auth()->user(), $record);

                        Notification::make()
                            ->title("Ahora estás dentro de la cuenta de {$record->name}")
                            ->success()
                            ->send();

                        return redirect()->route('dashboard');
                    }),
            ]);
    }
}
