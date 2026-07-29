<?php

use App\Domain\Identity\Actions\UpdateAvatar;
use App\Domain\Identity\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Configuración de perfil')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public ?string $email = '';
    public ?string $phone = '';

    public $avatar;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = Auth::user()->phone;
    }

    /**
     * Foto de perfil (0.6/E06 del TODO): se guarda aparte del resto del
     * formulario para que subir la foto no dependa de completar los demás
     * campos, y viceversa.
     */
    public function updateAvatar(): void
    {
        $this->validate(['avatar' => ['required', 'image', 'max:2048']]);

        app(UpdateAvatar::class)->handle(Auth::user(), $this->avatar);

        $this->reset('avatar');

        Flux::toast(variant: 'success', text: __('Foto de perfil actualizada.'));
    }

    public function removeAvatar(): void
    {
        app(UpdateAvatar::class)->remove(Auth::user());

        Flux::toast(text: __('Foto de perfil eliminada.'));
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Configuración de perfil') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Perfil')" :subheading="__('Actualiza tu nombre, correo electrónico y foto de perfil')">
        <div class="my-6 flex items-center gap-4">
            <flux:avatar :src="Auth::user()->avatarUrl()" :name="Auth::user()->name" circle size="xl" />

            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <label class="inline-flex cursor-pointer items-center rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        {{ __('Cambiar foto') }}
                        <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                    </label>

                    @if (Auth::user()->avatar_path)
                        <flux:button size="sm" variant="ghost" wire:click="removeAvatar" wire:confirm="{{ __('¿Quitar tu foto de perfil?') }}">
                            {{ __('Quitar') }}
                        </flux:button>
                    @endif
                </div>

                @error('avatar') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror

                @if ($avatar)
                    <flux:button size="sm" variant="primary" wire:click="updateAvatar" wire:loading.attr="disabled" class="self-start">
                        {{ __('Guardar foto') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Nombre')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Correo electrónico')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Tu correo electrónico no está verificado.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('Se envió un nuevo enlace de verificación a tu correo electrónico.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input wire:model="phone" :label="__('Teléfono')" type="tel" autocomplete="tel" placeholder="+57 300 000 0000" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Guardar') }}
                    </flux:button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
