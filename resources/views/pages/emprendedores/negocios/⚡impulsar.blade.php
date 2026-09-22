<?php

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\CreateContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\Story;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
new #[Title('Impulsa tu negocio')] class extends Component
{
    #[Locked]
    public int $businessId;

    public string $promotionTarget = '';

    public ?int $promotionMunicipalityId = null;

    public ?int $promotionCategoryId = null;

    public ?int $promotionRadiusKm = null;

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
        $this->promotionMunicipalityId = $business->municipality_id;
        $this->promotionCategoryId = $business->category_id;
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

    #[Computed]
    public function promotionTargets(): array
    {
        $products = $this->business->products()->where('status', 'publicado')->latest()->get()
            ->map(fn (Product $product) => ['value' => 'product:'.$product->id, 'label' => __('Producto: :name', ['name' => $product->name])]);
        $posts = $this->business->posts()->where('status', 'publicado')->latest('published_at')->get()
            ->map(fn (Post $post) => ['value' => 'post:'.$post->id, 'label' => __('Publicación: :name', ['name' => str($post->body ?: __('Sin texto'))->limit(55)])]);
        $stories = $this->business->stories()->where('expires_at', '>', now())->latest()->get()
            ->map(fn (Story $story) => ['value' => 'story:'.$story->id, 'label' => __('Estado: :name', ['name' => str($story->caption ?: __('Sin texto'))->limit(55)])]);
        $lives = $this->business->liveStreams()->where('status', '!=', LiveStream::BORRADOR)->get()
            ->map(fn (LiveStream $live) => ['value' => 'live:'.$live->id, 'label' => __('Live: :name', ['name' => $live->title])]);

        return $products->concat($posts)->concat($stories)->concat($lives)->values()->all();
    }

    #[Computed]
    public function municipalities()
    {
        return Municipality::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function promoteContent(int $billingProductId): void
    {
        $data = $this->validate([
            'promotionTarget' => ['required', 'regex:/^(product|post|story|live):[0-9]+$/'],
            'promotionMunicipalityId' => ['nullable', 'integer', 'exists:municipalities,id'],
            'promotionCategoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'promotionRadiusKm' => ['nullable', 'integer', Rule::in([1, 3, 5, 10, 20])],
        ]);

        [$type, $id] = explode(':', $data['promotionTarget'], 2);
        $content = match ($type) {
            'product' => $this->business->products()->findOrFail($id),
            'post' => $this->business->posts()->findOrFail($id),
            'story' => $this->business->stories()->findOrFail($id),
            'live' => $this->business->liveStreams()->findOrFail($id),
        };
        $billingProduct = BillingProduct::query()
            ->where('is_active', true)
            ->where('kind', BillingProduct::DESTACADO)
            ->findOrFail($billingProductId);

        $promotion = app(CreateContentPromotion::class)->handle($this->business, $content, [
            'municipality_id' => $data['promotionMunicipalityId'],
            'category_id' => $data['promotionCategoryId'],
            'radius_km' => $data['promotionRadiusKm'],
        ], Auth::user());

        $this->redirectRoute('emprendedores.negocios.impulsar.checkout', [
            'business' => $this->business,
            'billingProduct' => $billingProduct,
            'promotion' => $promotion->id,
        ]);
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

    @if (! empty($this->destacados) && ! empty($this->promotionTargets))
        <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Destaca contenido') }}</flux:heading>
            <flux:subheading>{{ __('Promociona un producto, publicación, estado o Live con los paquetes existentes.') }}</flux:subheading>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="promotionTarget" :label="__('Contenido')" placeholder="{{ __('Selecciona contenido') }}">
                    @foreach ($this->promotionTargets as $target)
                        <flux:select.option :value="$target['value']">{{ $target['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="promotionMunicipalityId" :label="__('Municipio')">
                    @foreach ($this->municipalities as $municipality)
                        <flux:select.option :value="$municipality->id">{{ $municipality->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="promotionCategoryId" :label="__('Categoría')">
                    @foreach ($this->categories as $category)
                        <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="promotionRadiusKm" :label="__('Distancia')">
                    <flux:select.option value="">{{ __('Todo el municipio') }}</flux:select.option>
                    @foreach ([1, 3, 5, 10, 20] as $radius)
                        <flux:select.option :value="$radius">{{ __(':radius km', ['radius' => $radius]) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($this->destacados as $item)
                    <flux:button variant="primary" wire:click="promoteContent({{ $item['product']->id }})">
                        {{ __('Promocionar :days días · :price', [
                            'days' => $item['days'],
                            'price' => '$'.number_format($item['product']->price_cents / 100, 0, ',', '.'),
                        ]) }}
                    </flux:button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->otherProducts->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('También podemos ayudarte') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($this->otherProducts as $product)
                    @php
                        $icon = match ($product->kind) {
                            \App\Domain\Billing\Models\BillingProduct::KIT_ARRANCA_BONITO => 'gift',
                            \App\Domain\Billing\Models\BillingProduct::ENTITLEMENT => 'sparkles',
                            default => 'photo',
                        };
                        $buttonLabel = match ($product->kind) {
                            \App\Domain\Billing\Models\BillingProduct::KIT_ARRANCA_BONITO => __('Comprar kit'),
                            \App\Domain\Billing\Models\BillingProduct::ENTITLEMENT => __('Activar'),
                            default => __('Solicitar ayuda'),
                        };
                    @endphp
                    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950">
                                <flux:icon
                                    :icon="$icon"
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
                                {{ $buttonLabel }}
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
