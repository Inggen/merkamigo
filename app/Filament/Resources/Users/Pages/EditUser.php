<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;

        $data['platform_role'] = $user->platformRoleName() ?? '';

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $role = $this->data['platform_role'] ?? '';

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');

        $user->syncRoles($role !== '' ? [Role::findOrCreate($role, 'web')] : []);

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }
}
