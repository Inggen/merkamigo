<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Cobros en línea": el negocio conecta SU PROPIA cuenta Wompi para
 * recibir pagos de productos directo en su cuenta — decisión del
 * usuario (sesión 15 sep 2026): Merkamigo nunca recauda dinero de
 * terceros, cada negocio maneja su propio dinero y su propia
 * responsabilidad fiscal sobre esa venta.
 */
new #[Title('Cobros en línea')] class extends Component {
    #[Locked]
    public int $businessId;

    public string $public_key = '';

    public string $private_key = '';

    public string $integrity_secret = '';

    public string $events_secret = '';

    public string $environment = 'sandbox';

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

        $this->authorize('update', $business);

        $this->businessId = $business->id;

        if ($credential = $business->wompiCredential) {
            $this->public_key = $credential->public_key;
            $this->environment = $credential->environment;
        }
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function webhookUrl(): string
    {
        return route('webhooks.wompi.negocio', $this->business);
    }

    public function connect(): void
    {
        $this->authorize('update', $this->business);

        try {
            app(ConnectBusinessWompi::class)->handle($this->business, [
                'public_key' => $this->public_key,
                'private_key' => $this->private_key,
                'integrity_secret' => $this->integrity_secret,
                'events_secret' => $this->events_secret,
                'environment' => $this->environment,
            ], Auth::user());
        } catch (ValidationException $e) {
            $this->addError('public_key', $e->validator->errors()->first());

            return;
        }

        $this->reset(['private_key', 'integrity_secret', 'events_secret']);
        unset($this->business);

        Flux::toast(variant: 'success', text: __('¡Wompi conectado! Ya puedes recibir pagos en línea de tus productos.'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Cobros en línea') }}</flux:heading>
        <flux:subheading>{{ __('Conecta tu propia cuenta de Wompi para que tus clientes te paguen directo — el dinero llega a tu cuenta, no a la de Merkamigo.') }}</flux:subheading>
    </div>

    @if ($this->business->hasWompiConnected())
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
            <flux:icon.check-circle class="size-6 text-emerald-600 dark:text-emerald-400" variant="outline" />
            <div>
                <flux:text class="font-semibold text-emerald-900 dark:text-emerald-100">{{ __('Wompi conectado') }}</flux:text>
                <flux:text class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('Llave pública: :key', ['key' => $public_key]) }} ({{ $environment === 'production' ? __('producción') : __('sandbox') }})</flux:text>
            </div>
        </div>
    @endif

    @if (! $this->business->hasVerifiedBadge())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950">
            <flux:text class="font-semibold text-amber-900 dark:text-amber-100">{{ __('Primero verifica tu negocio') }}</flux:text>
            <flux:text class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                {{ __('Antes de conectar pagos en línea necesitas completar la verificación de identidad — es la misma que ya usamos para el sello de confianza, protege tanto a tus clientes como a tu negocio.') }}
            </flux:text>
            <flux:button size="sm" class="mt-3" :href="route('emprendedores.negocios.verificacion', $this->business)" wire:navigate>
                {{ __('Ir a verificación') }}
            </flux:button>
        </div>
    @else
        <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
            <flux:text class="mb-1 font-semibold">{{ __('1. Copia esta URL en tu panel de Wompi') }}</flux:text>
            <flux:text class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('En tu cuenta Wompi: Configuración → Eventos (webhooks) → pega esta URL.') }}
            </flux:text>
            <div class="flex items-center gap-2 rounded-xl bg-zinc-100 px-3 py-2 font-mono text-sm dark:bg-zinc-800">
                <span class="min-w-0 flex-1 truncate">{{ $this->webhookUrl }}</span>
            </div>
        </div>

        <form wire:submit="connect" class="space-y-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
            <flux:text class="font-semibold">{{ __('2. Pega tus llaves de Wompi') }}</flux:text>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Las encuentras en tu cuenta Wompi: Configuración → Desarrolladores → Llaves API. Nunca las compartas fuera de aquí.') }}
            </flux:text>

            <flux:select wire:model="environment" :label="__('Ambiente')">
                <flux:select.option value="sandbox">{{ __('Pruebas (sandbox)') }}</flux:select.option>
                <flux:select.option value="production">{{ __('Producción (cobros reales)') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="public_key" :label="__('Llave pública')" placeholder="pub_..." />
            @error('public_key')
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            <flux:input wire:model="private_key" type="password" viewable :label="__('Llave privada')" placeholder="prv_..." />
            <flux:input wire:model="integrity_secret" type="password" viewable :label="__('Secreto de integridad')" />
            <flux:input wire:model="events_secret" type="password" viewable :label="__('Secreto de eventos')" />

            <flux:button type="submit" variant="primary">{{ __('Conectar Wompi') }}</flux:button>
        </form>
    @endif
</section>
