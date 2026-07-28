<?php

use App\Domain\Businesses\Actions\InviteCollaborator;
use App\Domain\Businesses\Actions\RemoveCollaborator;
use App\Domain\Businesses\Exceptions\CollaboratorInviteException;
use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessMembership;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Gestión básica de colaboradores (1.6 del TODO): agregar por correo a un
 * usuario ya registrado con rol admin/collaborator, y quitarlo. Sin sistema
 * de invitación por correo todavía — ver InviteCollaborator.
 */
new #[Title('Colaboradores')] class extends Component {
    #[Locked]
    public int $businessId;

    public string $email = '';

    public string $role = 'collaborator';

    /**
     * El middleware `business.team` solo corre en la carga inicial de la
     * página: las peticiones AJAX de Livewire (invite, remove...) van al
     * endpoint genérico `/livewire/update`, que no pasa por esa ruta ni por
     * ese middleware. `boot()` sí se ejecuta en cada petición (inicial y
     * subsecuentes), así que es el único lugar donde fijar el team de forma
     * confiable en todo el ciclo de vida del componente — sin esto,
     * cualquier acción después del primer render pierde el contexto de
     * equipo y falla con 403.
     */
    public function boot(): void
    {
        if (isset($this->businessId)) {
            setPermissionsTeamId($this->businessId);
            Auth::user()?->unsetRelation('roles');
        }
    }

    public function mount(Business $business): void
    {
        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');

        $this->authorize('manageMembers', $business);

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function members()
    {
        return $this->business->members()->withPivot('id')->get()->map(function ($user) {
            setPermissionsTeamId($this->businessId);
            $user->unsetRelation('roles');

            return [
                'membershipId' => $user->pivot->id,
                'user' => $user,
                'role' => $user->getRoleNames()->first(),
            ];
        });
    }

    public function invite(): void
    {
        $this->authorize('manageMembers', $this->business);

        $data = $this->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,collaborator'],
        ]);

        try {
            app(InviteCollaborator::class)->handle($this->business, Auth::user(), $data['email'], $data['role']);
            $this->reset(['email']);
            unset($this->members);
            Flux::toast(variant: 'success', text: __('Colaborador agregado.'));
        } catch (CollaboratorInviteException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function remove(int $membershipId): void
    {
        $this->authorize('manageMembers', $this->business);

        $membership = BusinessMembership::findOrFail($membershipId);

        try {
            app(RemoveCollaborator::class)->handle($this->business, Auth::user(), $membership);
            unset($this->members);
            Flux::toast(text: __('Colaborador eliminado.'));
        } catch (CollaboratorInviteException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <flux:heading size="xl">{{ __('Colaboradores') }}</flux:heading>
    <flux:subheading>{{ __('Agrega a alguien de tu equipo para que te ayude a administrar este negocio.') }}</flux:subheading>

    <form wire:submit="invite" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 p-6 sm:flex-row sm:items-end dark:border-zinc-700">
        <div class="flex-1">
            <flux:input wire:model="email" type="email" :label="__('Correo de la persona')" placeholder="correo@ejemplo.com" />
        </div>

        <flux:select wire:model="role" :label="__('Rol')">
            <flux:select.option value="collaborator">{{ __('Colaborador') }}</flux:select.option>
            <flux:select.option value="admin">{{ __('Administrador') }}</flux:select.option>
        </flux:select>

        <flux:button type="submit" variant="primary">{{ __('Agregar') }}</flux:button>
    </form>

    <div class="space-y-3">
        @forelse ($this->members as $member)
            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <div class="font-medium">{{ $member['user']->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $member['user']->email }} · {{ ucfirst($member['role'] ?? '') }}</div>
                </div>

                @if ($member['role'] !== 'owner')
                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="remove({{ $member['membershipId'] }})"
                        wire:confirm="{{ __('¿Quitar a :name de este negocio?', ['name' => $member['user']->name]) }}"
                    >
                        {{ __('Quitar') }}
                    </flux:button>
                @endif
            </div>
        @empty
            <x-states.empty title="{{ __('Todavía no hay colaboradores') }}" />
        @endforelse
    </div>
</section>
