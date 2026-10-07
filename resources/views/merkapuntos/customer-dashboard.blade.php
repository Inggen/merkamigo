@php
    $featuredRewards = $this->featuredRewards;
    $goal = $this->primaryGoal;
    $goalReward = $goal?->reward ?? $featuredRewards->first();
    $goalPhotoUrl = $goalReward?->imageUrl() ?? $goalReward?->product?->primaryImage()?->url();
    $goalAvailable = $goal?->available ?? 0;
    $goalMissing = $goal ? $goal->missing : ($goalReward?->points_cost ?? 0);
    $goalProgress = $goal?->progress ?? 0;
@endphp

<section class="min-h-screen bg-[#f7f7f5] text-zinc-950 dark:bg-[#101315] dark:text-white">
    <div class="mx-auto w-full max-w-7xl px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
        <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.34em] text-zinc-500 dark:text-zinc-400">{{ __('Merkamigo Premia') }}</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">{{ __('Merkapuntos') }}</h1>
                <p class="mt-1 text-base text-zinc-500 dark:text-zinc-400">{{ __('Tus compras de siempre, con algo más para ti.') }}</p>
            </div>

            <flux:button variant="primary" icon="qr-code" class="w-full sm:w-auto" x-on:click="$flux.modal('mi-qr').show()">
                {{ __('Mostrar mi QR') }}
            </flux:button>
        </header>

        <div class="relative isolate overflow-hidden rounded-3xl bg-[#2b0b0d] px-6 py-8 text-white shadow-xl shadow-brand-950/15 sm:px-9 lg:min-h-64 lg:px-10 lg:py-9">
            <img
                src="{{ asset('images/backgrounds/fondo_merkapuntos_banner.webp') }}"
                class="absolute inset-0 -z-20 size-full object-cover object-center"
                alt=""
                aria-hidden="true"
            >
            <span class="absolute inset-0 -z-10 bg-gradient-to-r from-black/60 via-[#5f0b21]/35 to-black/5"></span>

            <div class="max-w-xl">
                <h2 class="text-3xl font-black leading-tight tracking-tight sm:text-4xl">{{ __('Tu próxima compra puede acercarte a una recompensa.') }}</h2>
                <p class="mt-3 max-w-md text-base leading-6 text-white/85">{{ __('Elige lo que te gustaría disfrutar y empieza a acumular comprando cerca de ti.') }}</p>
                <a href="#recompensas" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-zinc-950 shadow-sm transition hover:-translate-y-0.5 hover:bg-zinc-100">
                    {{ __('Descubrir recompensas') }}
                    <flux:icon.arrow-right class="size-4" variant="outline" />
                </a>
            </div>
        </div>

        @if ($this->activeRedemptions->isNotEmpty())
            <div class="mt-4 space-y-3">
                @foreach ($this->activeRedemptions as $redemption)
                    <div class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-brand-900 dark:bg-brand-500/10">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300"><flux:icon.clock class="size-5" variant="outline" /></span>
                            <div><p class="text-sm font-bold">{{ __('Canje pendiente: :title', ['title' => $redemption->reward_snapshot['title'] ?? __('premio')]) }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $redemption->account->business->name }} · {{ __('Expira :time', ['time' => $redemption->expires_at->diffForHumans()]) }}</p></div>
                        </div>
                        <div class="flex gap-2"><flux:button size="sm" variant="primary" wire:click="viewCode({{ $redemption->id }})">{{ __('Ver código') }}</flux:button><flux:button size="sm" variant="ghost" wire:click="cancelRedemption({{ $redemption->id }})" wire:confirm="{{ __('¿Cancelar este canje? Tus puntos quedarán disponibles de nuevo.') }}">{{ __('Cancelar') }}</flux:button></div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4 grid gap-4 lg:grid-cols-[1.25fr_.95fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-6">
                <h2 class="text-xl font-black tracking-tight">{{ $goalReward ? __('Tu próxima meta') : __('Tu primera meta') }}</h2>

                @if ($goalReward)
                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="size-24 shrink-0 overflow-hidden rounded-full bg-brand-50 ring-4 ring-brand-50 dark:bg-brand-950 dark:ring-brand-500/10">
                            @if ($goalPhotoUrl)<img src="{{ $goalPhotoUrl }}" class="size-full object-cover" alt="{{ $goalReward->title }}">@else<div class="flex size-full items-center justify-center text-brand-600"><flux:icon.gift class="size-9" variant="outline" /></div>@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-lg font-bold">{{ $goalReward->title }}</h3>
                            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $goalReward->business->name }}@if ($goalReward->business->municipality) · {{ $goalReward->business->municipality->name }}@endif</p>
                            <div class="mt-3 flex items-center justify-between gap-3 text-sm"><span class="font-semibold">{{ number_format($goalAvailable, 0, ',', '.') }} / {{ number_format($goalReward->points_cost, 0, ',', '.') }} {{ __('Merkapuntos') }}</span><span class="text-xs text-zinc-400">{{ $goalProgress }}%</span></div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"><div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-red-400 transition-all" style="width: {{ $goalProgress }}%"></div></div>
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $goalMissing > 0 ? __('Te faltan :points para disfrutarla.', ['points' => number_format($goalMissing, 0, ',', '.')]) : __('¡Ya puedes canjear esta recompensa!') }}</p>
                        </div>
                        <flux:button size="sm" :href="route('premia.show', $goalReward)" variant="primary" wire:navigate class="shrink-0">{{ $goalMissing > 0 ? __('Ver mi meta') : __('Canjear') }}</flux:button>
                    </div>
                @else
                    <div class="mt-4 flex items-center gap-4 rounded-2xl bg-zinc-50 p-4 dark:bg-white/5">
                        <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"><flux:icon.gift class="size-6" variant="outline" /></span>
                        <div class="flex-1"><p class="font-bold">{{ __('Todavía no tienes Merkapuntos') }}</p><p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Compra en un negocio participante y muestra tu QR para empezar.') }}</p></div>
                    </div>
                @endif
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-6">
                <h2 class="text-xl font-black tracking-tight">{{ __('Así de fácil') }}</h2>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    @foreach ([['shopping-bag', __('Compra cerca'), __('El negocio confirma tu compra.')], ['qr-code', __('Muestra tu QR'), __('En el momento de pagar.')], ['gift', __('Acumula y canjea'), __('Tus puntos aparecen aquí.')]] as [$icon, $title, $copy])
                        <div class="min-w-0 text-center sm:text-left"><span class="mx-auto inline-flex size-11 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm shadow-brand-900/20 sm:mx-0"><flux:icon :name="$icon" class="size-5" variant="outline" /></span><p class="mt-2 text-xs font-bold sm:text-sm">{{ $title }}</p><p class="mt-1 hidden text-xs leading-4 text-zinc-500 dark:text-zinc-400 sm:block">{{ $copy }}</p></div>
                    @endforeach
                </div>
                <a href="{{ route('premia.index') }}" wire:navigate class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-4 text-sm font-bold transition hover:text-brand-600 dark:border-white/10 dark:hover:text-brand-300"><span class="inline-flex items-center gap-2"><flux:icon.building-storefront class="size-5" variant="outline" />{{ __('Ver negocios participantes') }}</span><flux:icon.arrow-right class="size-4" /></a>
            </div>
        </div>

        @if ($this->accounts->isNotEmpty())
            <div class="mt-5 flex gap-3 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($this->accounts as $row)
                    <div class="flex min-w-64 shrink-0 items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-zinc-900"><flux:avatar :src="$row->business->logoUrl()" :name="$row->business->name" size="sm" /><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $row->business->name }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Saldo disponible') }}</p></div><p class="text-xl font-black text-brand-600 dark:text-brand-400">{{ number_format($row->available, 0, ',', '.') }}</p></div>
                @endforeach
            </div>
        @endif

        <div id="recompensas" class="mt-7 scroll-mt-24">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><h2 class="text-2xl font-black tracking-tight sm:text-3xl">{{ __('Empieza por algo que te encante') }}</h2><p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Descubre recompensas en negocios locales participantes.') }}</p></div>
                <a href="{{ route('premia.index') }}" wire:navigate class="text-sm font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">{{ __('Ver todas las recompensas →') }}</a>
            </div>

            @if ($featuredRewards->isEmpty())
                <div class="rounded-3xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center dark:border-white/15 dark:bg-white/5"><flux:icon.gift class="mx-auto size-10 text-brand-500" variant="outline" /><p class="mt-3 font-bold">{{ __('Pronto encontrarás nuevas recompensas') }}</p><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Los negocios participantes están preparando beneficios para ti.') }}</p></div>
            @else
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($featuredRewards as $reward)
                        @include('merkapuntos.partials.customer-reward-card', ['reward' => $reward, 'accounts' => $this->accounts])
                    @endforeach
                </div>
            @endif
        </div>

        @if ($this->accounts->isNotEmpty())
            <div class="mt-6 rounded-2xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" x-data="{ open: false }"><button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-controls="historial-puntos-panel" class="flex w-full items-center justify-between p-4 text-left"><span class="font-bold">{{ __('Historial de puntos') }}</span><flux:icon.chevron-down class="size-5 text-zinc-400 transition" x-bind:class="open && 'rotate-180'" /></button><div id="historial-puntos-panel" x-show="open" x-cloak x-transition class="border-t border-zinc-100 px-4 pb-2 dark:border-white/10">@forelse ($this->movements as $movement)<div class="flex items-center justify-between border-b border-zinc-100 py-3 text-sm last:border-0 dark:border-white/10"><div><p>{{ $movement->account->business->name }}</p><p class="text-xs text-zinc-400">{{ $movement->created_at->format('d/m/Y H:i') }}</p></div><p class="font-bold {{ $movement->points > 0 ? 'text-emerald-600' : 'text-zinc-500' }}">{{ $movement->points > 0 ? '+' : '' }}{{ $movement->points }}</p></div>@empty<p class="py-3 text-sm text-zinc-500">{{ __('Todavía no hay movimientos.') }}</p>@endforelse</div></div>
        @endif

        <div class="mt-5 flex flex-col items-start justify-between gap-3 border-t border-zinc-200 py-5 text-sm sm:flex-row sm:items-center dark:border-white/10"><div><p class="font-bold">{{ __('¿Olvidaste mostrar tu QR?') }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Sube tu recibo y el negocio revisa tu compra manualmente.') }}</p></div><flux:button size="sm" variant="ghost" x-on:click="$flux.modal('solicitud-excepcional').show()">{{ __('Reportar compra') }}</flux:button></div>
    </div>

    <flux:modal name="mi-qr" class="w-full !max-w-xs text-center"><flux:heading size="lg" class="mb-1">{{ __('Mi QR para acumular') }}</flux:heading><flux:text class="mb-4 text-sm text-zinc-500">{{ __('Muéstralo al pagar. El negocio confirma tu compra.') }}</flux:text><img src="{{ route('merkapuntos.qr') }}" alt="{{ __('Tu código QR de Merkapuntos') }}" class="mx-auto size-56 rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-700"></flux:modal>

    <flux:modal name="canje-listo" class="w-full !max-w-xs text-center">@if ($reservedRedemptionId) @php($activeRedemption = \App\Domain\Loyalty\Models\LoyaltyRedemption::find($reservedRedemptionId)) <flux:badge color="amber" class="mb-3">{{ __('Pendiente de entrega') }}</flux:badge><flux:heading size="lg" class="mb-1">{{ $activeRedemption?->reward_snapshot['title'] ?? __('Tu premio') }}</flux:heading><flux:text class="mb-4 text-sm text-zinc-500">{{ __('Presenta este código en el local.') }}</flux:text>@if ($reservedToken)<img src="{{ route('merkapuntos.qr.redemption', $reservedRedemptionId) }}" alt="{{ __('Código de canje') }}" class="mx-auto mb-4 size-48 rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-700">@else<x-states.empty :title="__('Código ya mostrado')" :description="__('Pídele al negocio que verifique el canje con tu nombre.')" />@endif @if ($activeRedemption)<p class="mb-4 text-xs text-zinc-400">{{ __('Expira :time', ['time' => $activeRedemption->expires_at->diffForHumans()]) }}</p><flux:button variant="ghost" class="w-full" wire:click="cancelRedemption({{ $activeRedemption->id }})" wire:confirm="{{ __('¿Cancelar este canje? Tus puntos quedarán disponibles de nuevo.') }}">{{ __('Cancelar canje') }}</flux:button>@endif @endif</flux:modal>

    <flux:modal name="solicitud-excepcional" class="w-full !max-w-md"><form wire:submit="submitReceiptClaim" class="space-y-4"><flux:heading size="lg">{{ __('Reportar una compra olvidada') }}</flux:heading><flux:text class="text-sm text-zinc-500">{{ __('El negocio revisará tu recibo y decide si acredita los puntos.') }}</flux:text><flux:select wire:model="receiptClaimBusinessId" :label="__('Negocio')" :placeholder="__('Selecciona un negocio')">@foreach ($this->enrolledBusinesses as $business)<flux:select.option value="{{ $business->id }}">{{ $business->name }}</flux:select.option>@endforeach</flux:select><flux:field><flux:label>{{ __('Foto del recibo') }}</flux:label><input type="file" wire:model="receiptFile" accept="image/*" class="block w-full text-sm">@error('receiptFile') <flux:text class="text-sm text-red-600">{{ $message }}</flux:text> @enderror</flux:field><flux:textarea wire:model="receiptDescription" :label="__('Detalles (opcional)')" rows="3" maxlength="500" /><flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="submitReceiptClaim">{{ __('Enviar para revisión') }}</flux:button></form></flux:modal>
</section>
