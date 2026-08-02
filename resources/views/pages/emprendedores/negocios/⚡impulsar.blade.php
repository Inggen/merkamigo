<?php

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Businesses\Models\Business;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Catálogo de productos de ingreso complementario (4.3 del TODO):
 * destacados temporales, vitrina asistida y kit "Arranca Bonito". Cada
 * compra pasa por el checkout de Wompi (4.2) — nada se activa sin un pago
 * aprobado.
 */
new #[Title('Impulsa tu negocio')] class extends Component {
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
    public function products()
    {
        return BillingProduct::where('is_active', true)->orderBy('price_cents')->get();
    }

    /**
     * Tarjetas de "Destaca tu vitrina" (kind `destacado`), ordenadas por
     * duración, con el precio por día y el ahorro frente a comprar por
     * días sueltos calculados a partir de los precios reales del catálogo
     * — nunca codificados, para que sigan siendo correctos si un admin
     * cambia los precios desde Filament.
     *
     * @return array<int, array{product: BillingProduct, days: int, pricePerDay: int, savings: int, isFirst: bool, isRecommended: bool, isBestPrice: bool}>
     */
    #[Computed]
    public function destacados(): array
    {
        $products = $this->products
            ->where('kind', BillingProduct::DESTACADO)
            ->sortBy(fn (BillingProduct $product) => $product->payload['days'] ?? 0)
            ->values();

        if ($products->isEmpty()) {
            return [];
        }

        $referenceDays = max($products->first()->payload['days'] ?? 1, 1);
        $referenceDailyRate = $products->first()->price_cents / $referenceDays;

        $bestPricePerDayProductId = $products
            ->sortBy(fn (BillingProduct $product) => $product->price_cents / max($product->payload['days'] ?? 1, 1))
            ->first()
            ->id;

        $recommendedIndex = $products->count() >= 3 ? intdiv($products->count() - 1, 2) : null;

        return $products->map(function (BillingProduct $product, int $index) use ($referenceDailyRate, $bestPricePerDayProductId, $recommendedIndex) {
            $days = max($product->payload['days'] ?? 1, 1);
            $pricePerDay = (int) round($product->price_cents / $days);
            $savings = (int) round($referenceDailyRate * $days) - $product->price_cents;

            return [
                'product' => $product,
                'days' => $days,
                'pricePerDay' => $pricePerDay,
                'savings' => $savings,
                'isFirst' => $index === 0,
                'isRecommended' => $recommendedIndex !== null && $index === $recommendedIndex,
                'isBestPrice' => $index !== 0 && $product->id === $bestPricePerDayProductId,
            ];
        })->all();
    }

    #[Computed]
    public function otherProducts()
    {
        return $this->products->where('kind', '!=', BillingProduct::DESTACADO)->values();
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-10">
    <div>
        <flux:heading size="xl">{{ __('Impulsa tu negocio') }}</flux:heading>
        <flux:subheading>
            {{ __('Elige cómo quieres hacer crecer tu vitrina. Solo pagas una vez y se activa cuando Wompi confirme el pago.') }}
        </flux:subheading>
    </div>

    @if ($this->business->isFeatured())
        <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                    <flux:icon.star variant="solid" class="size-5 text-amber-500" />
                </span>
                <flux:text class="text-sm font-medium">
                    {{ __('Tu vitrina está destacada hasta el :date', ['date' => $this->business->featured_until->translatedFormat('d \\d\\e F')]) }}
                </flux:text>
            </div>
            <flux:badge color="amber">{{ __('Activo') }}</flux:badge>
        </div>
    @endif

    @if (! empty($this->destacados))
        <div>
            <flux:heading size="lg">{{ __('Destaca tu vitrina') }}</flux:heading>
            <flux:subheading>{{ __('Aparece primero en la Plaza de tu municipio') }}</flux:subheading>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ($this->destacados as $item)
                    @php $product = $item['product']; @endphp
                    <div @class([
                        'relative flex flex-col rounded-2xl border p-5 pt-6',
                        'border-2 border-brand-600' => $item['isRecommended'],
                        'border-zinc-200 dark:border-zinc-700' => ! $item['isRecommended'],
                    ])>
                        @if ($item['isRecommended'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold whitespace-nowrap text-white">
                                {{ __('Más elegido') }}
                            </span>
                        @elseif ($item['isBestPrice'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-amber-200 px-3 py-1 text-xs font-semibold whitespace-nowrap text-amber-900 dark:bg-amber-900 dark:text-amber-200">
                                {{ __('Mejor precio') }}
                            </span>
                        @endif

                        <div class="flex-1 text-center">
                            <div class="text-2xl font-bold text-zinc-950 dark:text-white">{{ __(':days días', ['days' => $item['days']]) }}</div>
                            <div class="mt-1 text-xl font-bold text-brand-600 dark:text-brand-400">
                                ${{ number_format($product->price_cents / 100, 0, ',', '.') }}
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">COP</span>
                            </div>

                            <div class="my-3 border-t border-zinc-100 dark:border-zinc-800"></div>

                            @if ($item['isFirst'])
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __(':amount por día', ['amount' => '$'.number_format($item['pricePerDay'] / 100, 0, ',', '.')]) }}
                                </flux:text>
                            @else
                                <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                                    {{ __('Ahorras :amount', ['amount' => '$'.number_format($item['savings'] / 100, 0, ',', '.')]) }}
                                </flux:text>
                            @endif
                        </div>

                        <flux:button
                            class="mt-5 {{ $item['isRecommended'] ? '' : '!border-brand-300 !text-brand-700 hover:!bg-brand-50 dark:!border-brand-800 dark:!text-brand-300 dark:hover:!bg-brand-950' }}"
                            :variant="$item['isRecommended'] ? 'primary' : 'outline'"
                            :href="route('emprendedores.negocios.impulsar.checkout', ['business' => $this->business, 'billingProduct' => $product])"
                        >
                            {{ __('Elegir :days días', ['days' => $item['days']]) }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->otherProducts->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('También podemos ayudarte') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($this->otherProducts as $product)
                    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950">
                                <flux:icon
                                    :icon="$product->kind === \App\Domain\Billing\Models\BillingProduct::KIT_ARRANCA_BONITO ? 'gift' : 'photo'"
                                    variant="outline"
                                    class="size-6 text-brand-600 dark:text-brand-400"
                                />
                            </span>
                            <div class="min-w-0">
                                <flux:text class="font-semibold text-zinc-950 dark:text-white">{{ $product->name }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $product->description }}</flux:text>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                            <flux:text class="text-lg font-bold text-brand-600 dark:text-brand-400">
                                ${{ number_format($product->price_cents / 100, 0, ',', '.') }}
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">COP</span>
                            </flux:text>

                            @if ($product->kind === \App\Domain\Billing\Models\BillingProduct::KIT_ARRANCA_BONITO)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                    {{ __('Todo incluido') }}
                                </span>
                            @endif

                            <flux:button
                                size="sm"
                                variant="primary"
                                :href="route('emprendedores.negocios.impulsar.checkout', ['business' => $this->business, 'billingProduct' => $product])"
                            >
                                {{ $product->kind === \App\Domain\Billing\Models\BillingProduct::KIT_ARRANCA_BONITO ? __('Comprar kit') : __('Solicitar ayuda') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex items-center justify-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
        <flux:icon.shield-check variant="outline" class="size-4 shrink-0" />
        {{ __('Pago seguro con Wompi · Activación automática después de la confirmación.') }}
    </div>
</section>
