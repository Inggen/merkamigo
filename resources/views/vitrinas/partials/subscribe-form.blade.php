@php
    $plan = $product->subscriptionPlan;
    $frequencyLabel = match ($plan?->frequency) {
        'semanal' => __('semana'),
        'trimestral' => __('trimestre'),
        'anual' => __('año'),
        default => __('mes'),
    };
    $existingSubscription = auth()->check()
        ? \App\Domain\Subscriptions\Models\CustomerSubscription::where('product_id', $product->id)
            ->where('buyer_user_id', auth()->id())
            ->whereIn('status', [\App\Domain\Subscriptions\Models\CustomerSubscription::PRUEBA, \App\Domain\Subscriptions\Models\CustomerSubscription::ACTIVA])
            ->first()
        : null;
@endphp

@unless ($business->hasWompiConnected() && $plan && $plan->is_active)
    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Este producto no está disponible para suscripción en este momento.') }}
    </flux:text>
@else
    @if ($existingSubscription)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
            <flux:text class="font-semibold text-emerald-900 dark:text-emerald-100">{{ __('Ya estás suscrito') }}</flux:text>
            <flux:text class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                {{ __('Administra tu suscripción desde Mis compras.') }}
            </flux:text>
            <flux:button size="sm" class="mt-3" :href="route('clientes.compras')" wire:navigate>
                {{ __('Ir a Mis compras') }}
            </flux:button>
        </div>
    @elseif (! auth()->check())
        <flux:button :href="route('login')" wire:navigate variant="primary" class="w-full">
            {{ __('Inicia sesión para suscribirte') }}
        </flux:button>
    @else
        <div
            x-data="{
                urls: {
                    tokens: @js(route('subscriptions.tokens-aceptacion', $business)),
                    saveCard: @js(route('subscriptions.tarjeta.store', $business)),
                    subscribe: @js(route('subscriptions.subscribe', $product)),
                },
                statusUrlBase: @js(route('subscriptions.tarjeta.estado', [$business, '__ID__'])),
                customerEmail: @js(auth()->user()->email),
                form: { cardHolder: '', number: '', expMonth: '', expYear: '', cvc: '' },
                acceptance: {},
                acceptedTerms: false,
                saving: false,
                error: null,
                init() {
                    fetch(this.urls.tokens, { headers: { Accept: 'application/json' } })
                        .then((r) => r.ok ? r.json() : Promise.reject())
                        .then((data) => { this.acceptance = data; })
                        .catch(() => { this.acceptance = {}; });
                },
                async subscribe() {
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

                        const saveResponse = await fetch(this.urls.saveCard, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({
                                card_token: card.id,
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

                        const sourceId = savePayload.data.id;
                        const finalStatus = await this.pollUntilResolved(sourceId, savePayload.data.status);

                        if (finalStatus !== 'AVAILABLE') {
                            this.error = @js(__('Wompi no pudo validar la tarjeta con tu banco. Intenta con otra tarjeta.'));
                            this.saving = false;
                            return;
                        }

                        const subscribeResponse = await fetch(this.urls.subscribe, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({
                                wompi_payment_source_id: String(sourceId),
                                card_brand: card.brand,
                                card_last_four: card.last_four,
                            }),
                        });

                        const subscribePayload = await subscribeResponse.json();

                        if (! subscribeResponse.ok) {
                            this.error = subscribePayload.message ?? @js(__('No pudimos completar la suscripción. Intenta de nuevo.'));
                            this.saving = false;
                            return;
                        }

                        window.location.href = @js(route('clientes.compras'));
                    } catch (e) {
                        this.error = @js(__('No pudimos conectar con Wompi. Intenta de nuevo.'));
                        this.saving = false;
                    }
                },
                async pollUntilResolved(sourceId, initialStatus) {
                    let currentStatus = initialStatus;
                    let attempts = 0;

                    while (currentStatus === 'PENDING' && attempts < 30) {
                        await new Promise((resolve) => setTimeout(resolve, 2000));

                        const url = this.statusUrlBase.replace('__ID__', sourceId);
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        const payload = await response.json();

                        currentStatus = payload.data?.status;
                        attempts++;
                    }

                    return currentStatus;
                },
            }"
            class="space-y-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800"
        >
            <div>
                <flux:heading size="base">
                    {{ __('Suscribirme: $:amount / :frequency', ['amount' => number_format((float) $product->price, 0, ',', '.'), 'frequency' => $frequencyLabel]) }}
                </flux:heading>
                @if ($plan->trial_days)
                    <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">
                        {{ trans_choice('Prueba gratis de :count día|Prueba gratis de :count días', $plan->trial_days, ['count' => $plan->trial_days]) }}
                    </flux:text>
                @endif
                @if ($plan->benefits)
                    <flux:text class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $plan->benefits }}</flux:text>
                @endif
            </div>

            <flux:input x-model="form.cardHolder" :label="__('Nombre en la tarjeta')" autocomplete="cc-name" />
            <flux:input x-model="form.number" :label="__('Número de tarjeta')" inputmode="numeric" maxlength="19" autocomplete="cc-number" placeholder="4242 4242 4242 4242" />
            <div class="grid grid-cols-3 gap-2">
                <flux:input x-model="form.expMonth" :label="__('Mes')" placeholder="MM" maxlength="2" />
                <flux:input x-model="form.expYear" :label="__('Año')" placeholder="AA" maxlength="2" />
                <flux:input x-model="form.cvc" :label="__('CVC')" maxlength="4" />
            </div>

            <template x-if="acceptance.acceptance_permalink">
                <label class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                    <input type="checkbox" x-model="acceptedTerms" class="mt-1">
                    <span>
                        {{ __('Acepto los') }}
                        <a :href="acceptance.acceptance_permalink" target="_blank" class="underline">{{ __('términos de uso de datos') }}</a>
                        {{ __('y la') }}
                        <a :href="acceptance.personal_auth_permalink" target="_blank" class="underline">{{ __('autorización de datos personales') }}</a>
                        {{ __('de Wompi.') }}
                    </span>
                </label>
            </template>

            <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>

            <flux:button type="button" x-on:click="subscribe" variant="primary" class="w-full" x-bind:disabled="saving">
                <span x-show="! saving">{{ __('Suscribirme ahora') }}</span>
                <span x-show="saving">{{ __('Procesando…') }}</span>
            </flux:button>

            <flux:text class="text-center text-xs text-zinc-400">
                {{ __('Cobro periódico directo a :business, procesado por Wompi.', ['business' => $business->name]) }}
            </flux:text>
        </div>
    @endif
@endunless
