<?php

use App\Domain\Billing\Actions\CheckUsageLimit;
use App\Domain\Billing\Actions\RemoveBusinessPaymentSource;
use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Plan y límites del negocio (4.1 del TODO): plan actual, consumo vs
 * límites, y cambio de plan. Los planes gratuitos se activan de
 * inmediato; los de pago pasan por el checkout de Wompi (4.2).
 */
new #[Title('Tu plan')] class extends Component {
    #[Locked]
    public int $businessId;

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
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function currentPlan(): Plan
    {
        return $this->business->activePlan();
    }

    #[Computed]
    public function usage(): array
    {
        return [
            'max_products' => app(CheckUsageLimit::class)->handle($this->business, 'max_products'),
            'max_members' => app(CheckUsageLimit::class)->handle($this->business, 'max_members'),
        ];
    }

    #[Computed]
    public function plans()
    {
        return Plan::where('is_active', true)->orderBy('position')->get();
    }

    public function switchToFreePlan(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        if (! $plan->isFree()) {
            return;
        }

        app(SubscribeToPlan::class)->handle($this->business, $plan, Auth::user());

        unset($this->currentPlan, $this->usage);
        Flux::toast(variant: 'success', text: __('Cambiaste al plan :plan.', ['plan' => $plan->name]));
    }

    /**
     * Llamado desde JS tras guardar/validar la tarjeta (o al quitarla) —
     * recalcula `business` desde la BD y cierra el modal, ya que todo el
     * flujo de tokenización/fuente de pago corre fuera de Livewire
     * (`PaymentSourceController`, ver 4.2 del TODO).
     */
    public function refreshBusinessState(): void
    {
        unset($this->business);
        Flux::modal('add-card-modal')->close();
    }

    public function removeAutoRenewCard(): void
    {
        app(RemoveBusinessPaymentSource::class)->handle($this->business, Auth::user());

        unset($this->business);
        Flux::toast(variant: 'success', text: __('Quitamos la tarjeta guardada. Tu plan seguirá activo hasta el final del periodo pagado.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-10">
    <div>
        <flux:heading size="xl">{{ __('Tu plan') }}</flux:heading>
        <flux:subheading>{{ __('Consulta tu plan actual, revisa su uso y elige la opción que mejor se adapte a tu negocio.') }}</flux:subheading>
    </div>

    <div class="relative overflow-hidden rounded-2xl border-2 border-brand-200 bg-stone-50 p-6 dark:border-brand-900 dark:bg-zinc-900">
        <div class="pointer-events-none absolute -top-4 right-6 hidden size-24 items-center justify-center rounded-full bg-brand-100/60 sm:flex dark:bg-brand-900/30">
            <flux:icon.building-storefront variant="outline" class="size-11 text-brand-300 dark:text-brand-700" />
        </div>

        <div class="relative max-w-md">
            <flux:badge color="green">{{ __('Plan actual') }}</flux:badge>

            <flux:heading size="xl" class="mt-2">{{ $this->currentPlan->name }}</flux:heading>

            <div class="mt-1 flex items-center gap-2">
                <flux:text class="text-lg font-semibold text-zinc-950 dark:text-white">
                    {{ $this->currentPlan->isFree() ? __('$0 COP') : '$'.number_format($this->currentPlan->price_cents / 100, 0, ',', '.').' COP' }}
                </flux:text>
                <flux:badge color="green">{{ __('Activo') }}</flux:badge>
            </div>

            <flux:text class="mt-3 text-zinc-600 dark:text-zinc-300">{{ $this->currentPlan->description }}</flux:text>
        </div>

        @if (! empty($this->currentPlan->features))
            <div class="relative mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                <ul class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
                    @foreach ($this->currentPlan->features as $feature)
                        <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle variant="outline" class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @unless ($this->currentPlan->isFree())
        <div
            x-data="{
                urls: {
                    tokens: @js(route('emprendedores.negocios.plan.tarjeta.tokens-aceptacion', $this->business)),
                    store: @js(route('emprendedores.negocios.plan.tarjeta.store', $this->business)),
                    status: @js(route('emprendedores.negocios.plan.tarjeta.estado', $this->business)),
                },
                customerEmail: @js(Auth::user()->email),
                form: { cardHolder: '', number: '', expMonth: '', expYear: '', cvc: '' },
                acceptance: {},
                acceptedTerms: false,
                saving: false,
                status: 'idle',
                error: null,
                reset() {
                    this.form = { cardHolder: '', number: '', expMonth: '', expYear: '', cvc: '' };
                    this.acceptedTerms = false;
                    this.saving = false;
                    this.status = 'idle';
                    this.error = null;
                    fetch(this.urls.tokens, { headers: { Accept: 'application/json' } })
                        .then((r) => r.ok ? r.json() : Promise.reject())
                        .then((data) => { this.acceptance = data; })
                        .catch(() => { this.acceptance = {}; });
                },
                async save() {
                    if (this.saving) return;

                    if (! this.acceptedTerms) {
                        this.error = @js(__('Debes aceptar los términos de Wompi para continuar.'));
                        return;
                    }

                    this.saving = true;
                    this.error = null;

                    try {
                        const tokenResponse = await fetch(`${this.acceptance.api_url}/tokens/cards`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${this.acceptance.public_key}` },
                            body: JSON.stringify({
                                number: this.form.number.replace(/\s+/g, ''),
                                exp_month: this.form.expMonth.padStart(2, '0'),
                                exp_year: this.form.expYear.padStart(2, '0'),
                                cvc: this.form.cvc,
                                card_holder: this.form.cardHolder,
                            }),
                        });

                        const tokenPayload = await tokenResponse.json();

                        if (! tokenResponse.ok || tokenPayload.status !== 'CREATED') {
                            this.error = tokenPayload.error?.messages ? Object.values(tokenPayload.error.messages).flat().join(' ') : @js(__('Wompi rechazó los datos de la tarjeta. Revísalos e intenta de nuevo.'));
                            this.saving = false;

                            return;
                        }

                        const card = tokenPayload.data;

                        const saveResponse = await fetch(this.urls.store, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({
                                card_token: card.id,
                                card_brand: card.brand,
                                card_last_four: card.last_four,
                                customer_email: this.customerEmail,
                                acceptance_token: this.acceptance.acceptance_token,
                                accept_personal_auth_token: this.acceptance.accept_personal_auth_token,
                            }),
                        });

                        const savePayload = await saveResponse.json();

                        if (! saveResponse.ok) {
                            this.error = savePayload.message ?? @js(__('No pudimos guardar la tarjeta. Intenta de nuevo.'));
                            this.saving = false;

                            return;
                        }

                        await this.pollStatus(savePayload.data?.status);
                    } catch (e) {
                        this.error = @js(__('No pudimos conectar con Wompi. Intenta de nuevo.'));
                        this.saving = false;
                    }
                },
                async pollStatus(initialStatus) {
                    let currentStatus = initialStatus;
                    let attempts = 0;

                    while (currentStatus === 'PENDING' && attempts < 30) {
                        this.status = 'challenge';
                        await new Promise((resolve) => setTimeout(resolve, 2000));

                        const response = await fetch(this.urls.status, { headers: { Accept: 'application/json' } });
                        const payload = await response.json();

                        currentStatus = payload.data?.status;
                        attempts++;
                    }

                    this.saving = false;

                    if (currentStatus === 'AVAILABLE') {
                        this.status = 'idle';
                        this.$wire.refreshBusinessState();
                        this.$flux.toast({ text: @js(__('Listo, tu tarjeta quedó guardada para la renovación automática.')) });
                    } else {
                        this.status = 'idle';
                        this.error = @js(__('Wompi no pudo validar la tarjeta con tu banco. Intenta con otra tarjeta.'));
                    }
                },
            }"
            x-init="reset()"
            class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Renovación automática') }}</flux:heading>
                    <flux:subheading>{{ __('Guarda una tarjeta para que tu plan se renueve solo cada mes, sin que tengas que volver a pagar a mano.') }}</flux:subheading>
                </div>

                @if ($this->business->hasAutoRenewCard())
                    <flux:badge color="green" icon="check-circle">{{ __('Activa') }}</flux:badge>
                @endif
            </div>

            @if ($this->business->hasAutoRenewCard())
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:icon.credit-card variant="outline" class="size-6 text-zinc-500 dark:text-zinc-400" />
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $this->business->card_brand }} •••• {{ $this->business->card_last_four }}
                        </flux:text>
                    </div>

                    <flux:button size="sm" variant="ghost" wire:click="removeAutoRenewCard" wire:confirm="{{ __('¿Quitar esta tarjeta? Tu plan no se renovará solo el próximo mes.') }}">
                        {{ __('Quitar tarjeta') }}
                    </flux:button>
                </div>
            @else
                <div class="mt-4">
                    <flux:modal.trigger name="add-card-modal">
                        <flux:button size="sm" variant="primary" x-on:click="reset()">
                            {{ __('Guardar tarjeta') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="add-card-modal" class="max-w-md">
                    <div class="space-y-5">
                        <div>
                            <flux:heading size="lg">{{ __('Guardar tarjeta') }}</flux:heading>
                            <flux:subheading>{{ __('Merkamigo nunca ve ni guarda los datos de tu tarjeta — se envían directo a Wompi.') }}</flux:subheading>
                        </div>

                        <form x-on:submit.prevent="save" class="space-y-4">
                            <flux:input label="{{ __('Nombre en la tarjeta') }}" x-model="form.cardHolder" required autocomplete="cc-name" />
                            <flux:input label="{{ __('Número de tarjeta') }}" x-model="form.number" inputmode="numeric" maxlength="19" required autocomplete="cc-number" placeholder="4242 4242 4242 4242" />

                            <div class="grid grid-cols-3 gap-3">
                                <flux:input label="{{ __('Mes (MM)') }}" x-model="form.expMonth" inputmode="numeric" maxlength="2" required autocomplete="cc-exp-month" placeholder="06" />
                                <flux:input label="{{ __('Año (AA)') }}" x-model="form.expYear" inputmode="numeric" maxlength="2" required autocomplete="cc-exp-year" placeholder="29" />
                                <flux:input label="{{ __('CVC') }}" x-model="form.cvc" inputmode="numeric" maxlength="4" required autocomplete="cc-csc" placeholder="123" />
                            </div>

                            <template x-if="acceptance.acceptance_permalink">
                                <label class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <input type="checkbox" x-model="acceptedTerms" class="mt-0.5 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-600 dark:bg-zinc-800" required>
                                    <span>
                                        {{ __('Acepto los') }}
                                        <a :href="acceptance.acceptance_permalink" target="_blank" class="underline">{{ __('términos de uso de datos') }}</a>
                                        {{ __('y la') }}
                                        <a :href="acceptance.personal_auth_permalink" target="_blank" class="underline">{{ __('autorización de datos personales') }}</a>
                                        {{ __('de Wompi.') }}
                                    </span>
                                </label>
                            </template>

                            <p x-show="status === 'challenge'" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                {{ __('Tu banco está verificando la tarjeta, esto puede tardar un momento…') }}
                            </p>

                            <p x-show="error" x-cloak class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" x-text="error"></p>

                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" x-bind:disabled="saving">
                                    <span x-show="! saving">{{ __('Guardar tarjeta') }}</span>
                                    <span x-show="saving" x-cloak>{{ __('Guardando…') }}</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </flux:modal>
            @endif
        </div>
    @endunless

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('Uso de tu plan') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2">
            @php
                $products = $this->usage['max_products'];
                $productsAvailable = $products['limit'] !== null ? max($products['limit'] - $products['used'], 0) : null;
                $productsPercent = $products['limit'] ? min(100, (int) round(($products['used'] / max($products['limit'], 1)) * 100)) : 0;

                $members = $this->usage['max_members'];
                $membersAvailable = $members['limit'] !== null ? max($members['limit'] - $members['used'], 0) : null;
                $membersPercent = $members['limit'] ? min(100, (int) round(($members['used'] / max($members['limit'], 1)) * 100)) : 0;
            @endphp

            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-950">
                    <flux:icon.cube variant="outline" class="size-6 text-brand-600 dark:text-brand-400" />
                </span>

                <flux:text class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Productos y servicios') }}</flux:text>

                <div class="mt-1 text-2xl font-bold text-zinc-950 dark:text-white">
                    {{ $products['used'] }}
                    @if ($products['limit'] !== null)
                        <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">{{ __('de :limit', ['limit' => $products['limit']]) }}</span>
                    @endif
                </div>

                @if ($products['limit'] === null)
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sin límite') }}</flux:text>
                @else
                    <div class="mt-2 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-2 rounded-full bg-brand-600" style="width: {{ $productsPercent }}%"></div>
                    </div>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ trans_choice(':count cupo disponible|:count cupos disponibles', $productsAvailable, ['count' => $productsAvailable]) }}
                    </flux:text>
                @endif
            </div>

            <div class="flex flex-col justify-between gap-4 rounded-2xl border border-zinc-200 p-5 sm:flex-row sm:items-center dark:border-zinc-700">
                <div class="min-w-0">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-950">
                        <flux:icon.users variant="outline" class="size-6 text-brand-600 dark:text-brand-400" />
                    </span>

                    <flux:text class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Miembros del equipo') }}</flux:text>

                    <div class="mt-1 text-2xl font-bold text-zinc-950 dark:text-white">
                        {{ $members['used'] }}
                        @if ($members['limit'] !== null)
                            <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">{{ __('de :limit', ['limit' => $members['limit']]) }}</span>
                        @endif
                    </div>

                    @if ($members['limit'] === null)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sin límite') }}</flux:text>
                    @else
                        <div class="mt-2 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-2 rounded-full bg-brand-600" style="width: {{ $membersPercent }}%"></div>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice(':count cupo disponible|:count cupos disponibles', $membersAvailable, ['count' => $membersAvailable]) }}
                        </flux:text>
                    @endif
                </div>

                <flux:button
                    size="sm"
                    class="shrink-0 !border-brand-300 !text-brand-700 hover:!bg-brand-50 dark:!border-brand-800 dark:!text-brand-300 dark:hover:!bg-brand-950"
                    variant="outline"
                    :href="route('emprendedores.negocios.colaboradores', $this->business)"
                >
                    {{ __('Gestionar equipo') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div>
        <flux:heading size="lg">{{ __('Comparar planes') }}</flux:heading>
        <flux:subheading class="mb-3">{{ __('Puedes cambiar de plan cuando lo necesites.') }}</flux:subheading>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($this->plans as $plan)
                @php $isCurrent = $plan->id === $this->currentPlan->id; @endphp
                <div @class([
                    'flex flex-col rounded-2xl border p-5',
                    'border-2 border-brand-600' => $isCurrent,
                    'border-zinc-200 dark:border-zinc-700' => ! $isCurrent,
                ])>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <flux:text class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $plan->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $plan->isFree() ? __('$0 COP') : '$'.number_format($plan->price_cents / 100, 0, ',', '.').' COP' }}
                            </flux:text>
                        </div>

                        @if ($isCurrent)
                            <flux:badge color="green">{{ __('Tu plan actual') }}</flux:badge>
                        @elseif ($plan->isFree())
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                {{ __('Plan básico') }}
                            </span>
                        @endif
                    </div>

                    @if (! empty($plan->features))
                        <ul class="mt-4 space-y-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:icon.check-circle variant="outline" class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-5">
                        @if ($isCurrent)
                            <flux:button size="sm" class="w-full" variant="filled" disabled>
                                {{ __('Plan actual') }}
                            </flux:button>
                        @elseif ($plan->isFree())
                            <flux:button
                                size="sm"
                                class="w-full !border-brand-300 !text-brand-700 hover:!bg-brand-50 dark:!border-brand-800 dark:!text-brand-300 dark:hover:!bg-brand-950"
                                variant="outline"
                                wire:click="switchToFreePlan({{ $plan->id }})"
                                wire:confirm="{{ __('¿Cambiar a este plan?') }}"
                            >
                                {{ __('Cambiar a :plan', ['plan' => $plan->name]) }}
                            </flux:button>
                        @else
                            <flux:button size="sm" class="w-full" variant="primary" :href="route('emprendedores.negocios.plan.checkout', ['business' => $this->business, 'plan' => $plan])">
                                {{ __('Suscribirme') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
        <flux:icon.chat-bubble-left-right variant="outline" class="size-4 shrink-0" />
        {{ __('¿Tienes dudas sobre tu plan?') }}
        <flux:link :href="route('soporte')">{{ __('Contáctanos') }}</flux:link>
    </div>
</section>
