<?php

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\CalculateLoyaltyRewardSuggestion;
use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\DeliverLoyaltyRedemption;
use App\Domain\Loyalty\Actions\EnrollBusinessInLoyalty;
use App\Domain\Loyalty\Actions\IssueLoyaltyIdentityToken;
use App\Domain\Loyalty\Actions\PublishLoyaltyPolicy;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Actions\ReviewLoyaltyReceiptClaim;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyPolicy;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyReceiptClaim;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Support\MerkapuntosRewardCalculator;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel de Merkamigo Premia del negocio (TODO_Merkapuntos.md, Fase 3):
 * adhesión, política, premios, escáner único y resultados — todo en un
 * solo panel con pestañas internas, "sin nuevo sistema de menús profundo"
 * (F3.6). El escáner (F3.2/F3.3/F3.4) identifica el tipo de código
 * automáticamente: `idn_` es un cliente a registrarle una compra, `rdm_`
 * es un canje a entregar. La cámara y la galería leen el QR en el
 * navegador y envían el código detectado directamente a este flujo.
 */
new #[Title('Merkapuntos')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $activeTab = 'resumen';

    // Adhesión
    public bool $consentAccepted = false;

    public ?int $budgetCop = null;

    // Política
    public int $pointsPerUnit = 1;

    public int $unitCop = 1000;

    // Nuevo premio
    public string $rewardTitle = '';

    public string $rewardDescription = '';

    public ?int $editingRewardId = null;

    public ?int $rewardProductId = null;

    public $rewardImage = null;

    public ?string $rewardExistingImageUrl = null;

    public ?int $rewardPointsCost = null;

    public ?int $rewardFullCostCop = null;

    public ?int $rewardStockTotal = null;

    public ?string $rewardValidUntil = null;

    // Asistente de recompensa (TODO_Correccion_Logica_Merkapuntos.md): el
    // rango de puntos se calcula desde el ticket promedio del negocio,
    // nunca desde el costo del premio directamente. Es un dato del
    // asistente, no del premio en sí — no se guarda en `LoyaltyReward`.
    // El ticket promedio se calcula SOLO desde el historial de compras
    // (`averageTicketCents()`) — "así es muy difícil para el
    // emprendedor" (pedido del usuario). `rewardAverageTicketCop` es
    // únicamente el ajuste manual opcional, nunca el valor por defecto.
    public ?int $rewardAverageTicketCop = null;

    public bool $overrideAverageTicket = false;

    // Escáner
    public string $scanInput = '';

    public ?array $scanResult = null;

    public ?int $purchaseAmountCop = null;

    public string $purchaseIdempotencyKey = '';

    public string $deliveryIdempotencyKey = '';

    // Revisión de solicitud excepcional
    public ?int $reviewingClaimId = null;

    public ?int $reviewAmountCop = null;

    public string $rejectReason = '';

    /**
     * Las peticiones AJAX de Livewire van a `/livewire/update`, que no
     * pasa por el middleware `business.team` de la carga inicial — `boot()`
     * corre en cada petición y es el único lugar confiable para mantener
     * el team de spatie/permission fijo durante todo el ciclo de vida.
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

        $this->authorize('view', $business);

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function enrollment()
    {
        return $this->business->loyaltyEnrollment;
    }

    #[Computed]
    public function activePolicy(): ?LoyaltyPolicy
    {
        return $this->business->loyaltyPolicies()->where('status', LoyaltyPolicy::ACTIVA)->first();
    }

    #[Computed]
    public function rewards()
    {
        return $this->business->loyaltyRewards()->with('product.media')->latest()->get();
    }

    #[Computed]
    public function products()
    {
        return $this->business->products()->with('media')->orderBy('name')->get();
    }

    #[Computed]
    public function pendingClaims()
    {
        return $this->business->loyaltyReceiptClaims()
            ->where('status', LoyaltyReceiptClaim::PENDIENTE)
            ->with('customer')
            ->latest()
            ->get();
    }

    #[Computed]
    public function reviewingClaim(): ?LoyaltyReceiptClaim
    {
        return $this->reviewingClaimId
            ? LoyaltyReceiptClaim::with('customer')->find($this->reviewingClaimId)
            : null;
    }

    #[Computed]
    public function scanPreviewPoints(): int
    {
        if (! $this->activePolicy || ! $this->purchaseAmountCop) {
            return 0;
        }

        return $this->activePolicy->pointsFor($this->purchaseAmountCop * 100);
    }

    /**
     * Ticket promedio calculado AUTOMÁTICAMENTE desde el historial real
     * de compras del negocio — "Dejalo automático porque así es muy
     * difícil para el emprendedor" (pedido del usuario). Con menos de
     * `min_purchases_for_average_ticket` compras registradas el
     * promedio no es confiable todavía (negocio muy nuevo) y se
     * devuelve null — ahí, y solo ahí, se le pide un estimado manual.
     */
    #[Computed]
    public function averageTicketCents(): ?int
    {
        $minPurchases = (int) config('loyalty.reward_assistant.min_purchases_for_average_ticket');

        $purchases = $this->business->loyaltyPurchases()->where('status', LoyaltyPurchase::REGISTRADA);

        if ((clone $purchases)->count() < $minPurchases) {
            return null;
        }

        $average = (clone $purchases)->avg('eligible_amount_cents');

        return $average ? (int) round($average) : null;
    }

    /**
     * El ticket promedio que realmente se usa en los cálculos: el
     * automático del historial, salvo que el comerciante haya pedido
     * ajustarlo manualmente.
     */
    #[Computed]
    public function resolvedAverageTicketCents(): ?int
    {
        if ($this->overrideAverageTicket && $this->rewardAverageTicketCop) {
            return $this->rewardAverageTicketCop * 100;
        }

        return $this->averageTicketCents ?? ($this->rewardAverageTicketCop ? $this->rewardAverageTicketCop * 100 : null);
    }

    /**
     * Las tres opciones automáticas del asistente de recompensa
     * (TODO_Correccion_Logica_Merkapuntos.md) — se recalculan solas en
     * cuanto el comerciante llena el costo del premio, sin que tenga
     * que pulsar ningún botón ("Merkamigo debe encargarse
     * automáticamente del resto del cálculo").
     *
     * @return array{recommendations: list<array<string, mixed>>}|array{error: string}|null
     */
    #[Computed]
    public function rewardSuggestion(): ?array
    {
        if (! $this->rewardFullCostCop || ! $this->resolvedAverageTicketCents) {
            return null;
        }

        try {
            return app(CalculateLoyaltyRewardSuggestion::class)->handle(
                $this->business, $this->rewardFullCostCop * 100, $this->resolvedAverageTicketCents,
            );
        } catch (LoyaltyActionException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * "Valor manual" (TODO_Correccion_Logica_Merkapuntos.md): cuando el
     * comerciante escribe los puntos a mano (en vez de elegir una de las
     * tres opciones), recalcular en tiempo real el gasto aproximado
     * requerido, el número aproximado de compras y el porcentaje del
     * incentivo — sin tocar nada del premio todavía.
     *
     * @return array{required_spend_cents: int, estimated_purchases: ?float, percentage: ?float, warnings: list<string>}|null
     */
    #[Computed]
    public function manualPointsEstimate(): ?array
    {
        if (! $this->rewardPointsCost || ! $this->activePolicy) {
            return null;
        }

        $pointsPerUnit = (int) ($this->activePolicy->rule['points_per_unit'] ?? 0);
        $unitCents = (int) ($this->activePolicy->rule['unit_cents'] ?? 0);

        if ($pointsPerUnit <= 0 || $unitCents <= 0) {
            return null;
        }

        $calculator = app(MerkapuntosRewardCalculator::class);
        $requiredSpendCents = $calculator->calculateRequiredSpend($this->rewardPointsCost, $pointsPerUnit, $unitCents);

        $estimatedPurchases = $this->resolvedAverageTicketCents
            ? $calculator->calculateEstimatedPurchases($requiredSpendCents, $this->resolvedAverageTicketCents)
            : null;

        $percentage = $this->rewardFullCostCop
            ? $calculator->calculateRewardPercentage($this->rewardFullCostCop * 100, $requiredSpendCents)
            : null;

        return [
            'required_spend_cents' => $requiredSpendCents,
            'estimated_purchases' => $estimatedPurchases,
            'percentage' => $percentage,
            'warnings' => $calculator->validateRewardConfiguration($estimatedPurchases ?? 0.0, $percentage ?? 0.0),
        ];
    }

    /**
     * @return array{issued: int, redeemed: int, pending: int, budgetSpentCents: int, budgetReservedCents: int, customers: int}
     */
    #[Computed]
    public function metrics(): array
    {
        $accountIds = $this->business->loyaltyAccounts()->pluck('id');

        return [
            'issued' => (int) LoyaltyMovement::whereIn('account_id', $accountIds)->where('type', LoyaltyMovement::ACUMULACION)->sum('points'),
            'redeemed' => LoyaltyRedemption::whereIn('account_id', $accountIds)->where('status', LoyaltyRedemption::ENTREGADO)->count(),
            'pending' => LoyaltyRedemption::whereIn('account_id', $accountIds)->where('status', LoyaltyRedemption::RESERVADO)->count(),
            'budgetSpentCents' => (int) $this->business->loyaltyRewards()->sum('budget_spent_cents'),
            'budgetReservedCents' => (int) $this->business->loyaltyRewards()->sum('budget_reserved_cents'),
            'customers' => $this->business->loyaltyAccounts()->count(),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function enroll(): void
    {
        $this->authorize('update', $this->business);

        if (! $this->consentAccepted) {
            Flux::toast(variant: 'danger', text: __('Debes aceptar las condiciones del programa.'));

            return;
        }

        try {
            app(EnrollBusinessInLoyalty::class)->handle(
                $this->business, Auth::user(), 'v1', $this->budgetCop !== null ? $this->budgetCop * 100 : null,
            );
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->enrollment);
        Flux::toast(text: __('Adhesión guardada. Publica tu política de acumulación para activarla.'));
    }

    public function publishPolicy(): void
    {
        $this->authorize('update', $this->business);

        try {
            app(PublishLoyaltyPolicy::class)->handle($this->business, Auth::user(), [
                'type' => 'simple',
                'points_per_unit' => $this->pointsPerUnit,
                'unit_cents' => $this->unitCop * 100,
            ]);
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->activePolicy);
        Flux::toast(text: __('Política de acumulación publicada.'));
    }

    public function activateProgram(): void
    {
        $this->authorize('update', $this->business);

        try {
            app(EnrollBusinessInLoyalty::class)->activate($this->enrollment, Auth::user());
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->enrollment);
        Flux::toast(text: __('Merkamigo Premia está activo en tu negocio.'));
    }

    public function createReward(): void
    {
        $this->editingRewardId = null;
        $this->saveReward();
    }

    public function startNewReward(): void
    {
        $this->resetRewardForm();
        Flux::modal('premio')->show();
    }

    public function editReward(int $rewardId): void
    {
        $reward = $this->business->loyaltyRewards()->findOrFail($rewardId);

        $this->editingRewardId = $reward->id;
        $this->rewardProductId = $reward->product_id;
        $this->rewardTitle = $reward->title;
        $this->rewardDescription = $reward->description ?? '';
        $this->rewardPointsCost = $reward->points_cost;
        $this->rewardFullCostCop = (int) ($reward->full_cost_cents / 100);
        $this->rewardStockTotal = $reward->stock_total;
        $this->rewardValidUntil = $reward->valid_until?->format('Y-m-d');
        $this->rewardExistingImageUrl = $reward->imageUrl();
        $this->rewardImage = null;
        $this->rewardAverageTicketCop = null;
        $this->overrideAverageTicket = false;
        unset($this->rewardSuggestion, $this->manualPointsEstimate, $this->averageTicketCents, $this->resolvedAverageTicketCents);
        $this->resetValidation();

        Flux::modal('premio')->show();
    }

    public function saveReward(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'rewardProductId' => ['required', 'integer'],
            'rewardTitle' => ['required', 'string', 'max:120'],
            'rewardPointsCost' => ['required', 'integer', 'min:1'],
            'rewardFullCostCop' => ['required', 'integer', 'min:1'],
            'rewardStockTotal' => ['nullable', 'integer', 'min:1'],
            'rewardValidUntil' => ['nullable', 'date'],
            'rewardImage' => ['nullable', 'image', 'max:'.config('media.loyalty_reward.max_kb')],
        ]);

        $data = [
            'product_id' => $this->rewardProductId,
            'image' => $this->rewardImage,
            'title' => $this->rewardTitle,
            'description' => $this->rewardDescription ?: null,
            'points_cost' => $this->rewardPointsCost,
            'full_cost_cents' => $this->rewardFullCostCop * 100,
            'stock_total' => $this->rewardStockTotal,
            'valid_until' => $this->rewardValidUntil,
        ];

        try {
            if ($this->editingRewardId) {
                $reward = $this->business->loyaltyRewards()->findOrFail($this->editingRewardId);
                app(CreateLoyaltyReward::class)->update($reward, Auth::user(), $data);
            } else {
                app(CreateLoyaltyReward::class)->handle($this->business, Auth::user(), $data);
            }
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $wasEditing = $this->editingRewardId !== null;
        $this->resetRewardForm();
        unset($this->rewards);
        Flux::modal('premio')->close();
        Flux::toast(text: $wasEditing ? __('Premio actualizado.') : __('Premio guardado como borrador.'));
    }

    private function resetRewardForm(): void
    {
        $this->reset([
            'editingRewardId',
            'rewardProductId',
            'rewardTitle',
            'rewardDescription',
            'rewardPointsCost',
            'rewardFullCostCop',
            'rewardStockTotal',
            'rewardValidUntil',
            'rewardImage',
            'rewardExistingImageUrl',
            'rewardAverageTicketCop',
            'overrideAverageTicket',
        ]);
        unset($this->rewardSuggestion, $this->manualPointsEstimate, $this->averageTicketCents, $this->resolvedAverageTicketCents);
        $this->resetValidation();
    }

    public function toggleAverageTicketOverride(): void
    {
        $this->overrideAverageTicket = ! $this->overrideAverageTicket;

        if (! $this->overrideAverageTicket) {
            $this->rewardAverageTicketCop = null;
        }

        unset($this->resolvedAverageTicketCents);
    }

    /**
     * TODO_Correccion_Logica_Merkapuntos.md: "el negocio debe poder
     * seleccionar cualquiera de las tres opciones" — esto solo copia los
     * puntos de la opción elegida al campo editable, nunca guarda nada
     * por sí sola (`saveReward()` sigue siendo el único paso que
     * persiste el premio).
     */
    public function useSuggestion(int $points): void
    {
        $this->rewardPointsCost = $points;
    }

    public function publishReward(int $rewardId): void
    {
        $this->rewardAction($rewardId, fn ($reward) => app(CreateLoyaltyReward::class)->publish($reward, Auth::user()), __('Premio publicado.'));
    }

    public function pauseReward(int $rewardId): void
    {
        $this->rewardAction($rewardId, fn ($reward) => app(CreateLoyaltyReward::class)->pause($reward, Auth::user()), __('Premio pausado.'));
    }

    public function resumeReward(int $rewardId): void
    {
        $this->rewardAction($rewardId, fn ($reward) => app(CreateLoyaltyReward::class)->resume($reward, Auth::user()), __('Premio reactivado.'));
    }

    private function rewardAction(int $rewardId, Closure $action, string $successMessage): void
    {
        $reward = $this->business->loyaltyRewards()->findOrFail($rewardId);

        try {
            $action($reward);
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->rewards);
        Flux::toast(text: $successMessage);
    }

    public function scan(): void
    {
        $this->authorize('view', $this->business);

        $raw = trim($this->scanInput);

        if ($raw === '') {
            return;
        }

        if (str_starts_with($raw, 'idn_')) {
            $customer = app(IssueLoyaltyIdentityToken::class)->resolve($raw);

            if (! $customer) {
                Flux::toast(variant: 'danger', text: __('Código de identificación inválido.'));

                return;
            }

            $this->scanResult = ['type' => 'purchase', 'customer_id' => $customer->id, 'customer_name' => $customer->name];
            $this->purchaseIdempotencyKey = (string) Str::uuid();

            return;
        }

        if (str_starts_with($raw, 'rdm_')) {
            try {
                $redemption = app(DeliverLoyaltyRedemption::class)->findByToken($this->business, $raw);
            } catch (LoyaltyActionException $e) {
                Flux::toast(variant: 'danger', text: $e->getMessage());

                return;
            }

            $this->scanResult = ['type' => 'delivery', 'redemption_id' => $redemption->id];
            $this->deliveryIdempotencyKey = (string) Str::uuid();

            return;
        }

        Flux::toast(variant: 'danger', text: __('No reconocemos este código.'));
    }

    public function confirmPurchase(): void
    {
        if (! $this->scanResult || $this->scanResult['type'] !== 'purchase') {
            return;
        }

        if (! $this->purchaseAmountCop || $this->purchaseAmountCop <= 0) {
            Flux::toast(variant: 'danger', text: __('Ingresa el valor pagado.'));

            return;
        }

        try {
            $customer = User::findOrFail($this->scanResult['customer_id']);
            $purchase = app(RegisterLoyaltyPurchase::class)->handle(
                $this->business, Auth::user(), $customer, $this->purchaseAmountCop * 100, $this->purchaseIdempotencyKey,
            );
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        // F5.4: "medir primer canje y recompra además de registros" —
        // recompra = ya existía una compra anterior de este mismo
        // cliente en este negocio antes de la que se acaba de crear.
        $isRepurchase = LoyaltyPurchase::where('business_id', $this->business->id)
            ->where('customer_user_id', $customer->id)
            ->where('id', '!=', $purchase->id)
            ->exists();

        app(RegisterAnalyticsEvent::class)->handle($this->business, AnalyticsEvent::LOYALTY_PURCHASE_CONFIRMED, $purchase, request());

        if ($isRepurchase) {
            app(RegisterAnalyticsEvent::class)->handle($this->business, AnalyticsEvent::LOYALTY_REPURCHASE, $purchase, request());
        }

        Flux::toast(text: __('Compra registrada y puntos acreditados.'));
        $this->resetScanner();
    }

    public function confirmDelivery(): void
    {
        if (! $this->scanResult || $this->scanResult['type'] !== 'delivery') {
            return;
        }

        $redemption = LoyaltyRedemption::findOrFail($this->scanResult['redemption_id']);

        try {
            $redemption = app(DeliverLoyaltyRedemption::class)->handle($this->business, Auth::user(), $redemption);
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        app(RegisterAnalyticsEvent::class)->handle($this->business, AnalyticsEvent::LOYALTY_REDEMPTION_DELIVERED, $redemption, request());

        Flux::toast(text: __('Premio entregado.'));
        $this->resetScanner();
    }

    public function cancelScan(): void
    {
        $this->resetScanner();
    }

    private function resetScanner(): void
    {
        $this->reset(['scanInput', 'scanResult', 'purchaseAmountCop', 'purchaseIdempotencyKey', 'deliveryIdempotencyKey']);
    }

    public function startReviewingClaim(int $claimId): void
    {
        $this->reviewingClaimId = $claimId;
        $this->reviewAmountCop = null;
        $this->rejectReason = '';
        Flux::modal('revisar-solicitud')->show();
    }

    public function approveClaim(): void
    {
        if (! $this->reviewingClaimId) {
            return;
        }

        if (! $this->reviewAmountCop || $this->reviewAmountCop <= 0) {
            Flux::toast(variant: 'danger', text: __('Ingresa el valor pagado.'));

            return;
        }

        $claim = LoyaltyReceiptClaim::findOrFail($this->reviewingClaimId);

        try {
            app(ReviewLoyaltyReceiptClaim::class)->approve($claim, Auth::user(), $this->reviewAmountCop * 100, (string) Str::uuid());
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->pendingClaims);
        Flux::modal('revisar-solicitud')->close();
        $this->reviewingClaimId = null;
        Flux::toast(text: __('Solicitud aprobada y puntos acreditados.'));
    }

    public function rejectClaim(): void
    {
        if (! $this->reviewingClaimId) {
            return;
        }

        if (trim($this->rejectReason) === '') {
            Flux::toast(variant: 'danger', text: __('Indica un motivo de rechazo.'));

            return;
        }

        $claim = LoyaltyReceiptClaim::findOrFail($this->reviewingClaimId);

        try {
            app(ReviewLoyaltyReceiptClaim::class)->reject($claim, Auth::user(), $this->rejectReason);
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->pendingClaims);
        Flux::modal('revisar-solicitud')->close();
        $this->reviewingClaimId = null;
        Flux::toast(text: __('Solicitud rechazada.'));
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Merkapuntos') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Gestiona tu adhesión, tus premios y el escáner de Merkamigo Premia.') }}</flux:text>
    </div>

    <nav class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" aria-label="{{ __('Secciones de Merkapuntos') }}">
        @foreach (['resumen' => __('Resumen'), 'premios' => __('Premios'), 'escaner' => __('Escáner'), 'resultados' => __('Resultados')] as $tab => $label)
            <button
                type="button"
                wire:click="setTab('{{ $tab }}')"
                aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}"
                @class([
                    'shrink-0 border-b-2 px-3 py-2.5 text-sm font-medium transition',
                    'border-brand-600 text-brand-600' => $activeTab === $tab,
                    'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' => $activeTab !== $tab,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- Resumen: adhesión y política --}}
    @if ($activeTab === 'resumen')
        <div class="space-y-4">
            @if (! $this->enrollment)
                <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5 dark:border-transparent dark:bg-brand-500/10">
                    <flux:heading size="lg" class="mb-1">{{ __('Activa Merkamigo Premia en tu vitrina') }}</flux:heading>
                    <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Crea recompensas, motiva próximas compras y fortalece el vínculo con tu comunidad local. Tú financias tus premios y decides cuánto invertir.') }}</flux:text>

                    <flux:field class="mb-4">
                        <flux:label>{{ __('Presupuesto mensual (COP, opcional)') }}</flux:label>
                        <flux:input type="number" min="0" wire:model="budgetCop" placeholder="150000" />
                    </flux:field>

                    <flux:checkbox wire:model="consentAccepted" :label="__('Acepto las condiciones del programa')" class="mb-4" />

                    <flux:button variant="primary" wire:click="enroll" wire:loading.attr="disabled" wire:target="enroll">
                        {{ __('Unirme al programa') }}
                    </flux:button>
                </div>
            @else
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <flux:heading size="lg">{{ __('Estado de tu adhesión') }}</flux:heading>
                        <flux:badge :color="$this->enrollment->isActive() ? 'green' : 'amber'">
                            {{ match ($this->enrollment->status) {
                                'activa' => __('Activo'),
                                'pendiente' => __('Pendiente de política'),
                                'suspendida' => __('Suspendido'),
                                'retirada' => __('Retirado'),
                                default => $this->enrollment->status,
                            } }}
                        </flux:badge>
                    </div>

                    @if ($this->enrollment->budget_cents)
                        <flux:text class="text-sm text-zinc-500">{{ __('Presupuesto mensual: $:amount', ['amount' => number_format($this->enrollment->budget_cents / 100, 0, ',', '.')]) }}</flux:text>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-1">{{ __('Política de acumulación') }}</flux:heading>

                    @if ($this->activePolicy)
                        <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Tus clientes acumulan :points punto(s) por cada $:unit de compra verificada.', ['points' => $this->activePolicy->rule['points_per_unit'], 'unit' => number_format($this->activePolicy->rule['unit_cents'] / 100, 0, ',', '.')]) }}
                        </flux:text>
                        <flux:text class="mb-4 text-xs text-zinc-400">{{ __('Versión :version · vigente desde :date', ['version' => $this->activePolicy->version, 'date' => $this->activePolicy->effective_from?->format('d/m/Y')]) }}</flux:text>
                    @else
                        <flux:text class="mb-4 text-sm text-zinc-500">{{ __('Define tu regla de acumulación para poder activar el programa.') }}</flux:text>
                    @endif

                    <div class="mb-4 grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Merkapuntos por unidad') }}</flux:label>
                            <flux:input type="number" min="1" wire:model="pointsPerUnit" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Por cada $ de compra (COP)') }}</flux:label>
                            <flux:input type="number" min="1" wire:model="unitCop" />
                        </flux:field>
                    </div>

                    <flux:button variant="{{ $this->activePolicy ? 'ghost' : 'primary' }}" wire:click="publishPolicy" wire:loading.attr="disabled" wire:target="publishPolicy">
                        {{ $this->activePolicy ? __('Publicar nueva versión') : __('Publicar política') }}
                    </flux:button>
                </div>

                @if (! $this->enrollment->isActive() && $this->activePolicy)
                    <flux:button variant="primary" class="w-full" wire:click="activateProgram" wire:loading.attr="disabled" wire:target="activateProgram">
                        {{ __('Activar Merkamigo Premia') }}
                    </flux:button>
                @endif
            @endif
        </div>
    @endif

    {{-- Premios --}}
    @if ($activeTab === 'premios')
        <div>
            @unless ($this->enrollment?->isActive())
                <x-states.empty :title="__('Activa el programa primero')" :description="__('Completa la adhesión y publica una política en la pestaña Resumen antes de crear premios.')" />
            @else
                <div class="mb-4 flex justify-end">
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="startNewReward">
                        {{ __('Nuevo premio') }}
                    </flux:button>
                </div>

                @if ($this->rewards->isEmpty())
                    <x-states.empty :title="__('Todavía no tienes premios')" :description="__('Crea tu primer premio para que tus clientes empiecen a canjear.')" />
                @else
                    <div class="space-y-3">
                        @foreach ($this->rewards as $reward)
                            <article id="premio-{{ $reward->id }}" class="scroll-mt-24 flex items-center gap-4 rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                    @if ($reward->imageUrl() ?? $reward->product?->primaryImage()?->url())
                                        <img src="{{ $reward->imageUrl() ?? $reward->product?->primaryImage()?->url() }}" class="size-full object-cover" alt="{{ $reward->title }}">
                                    @else
                                        <div class="flex size-full items-center justify-center text-zinc-400"><flux:icon.gift class="size-8" variant="outline" /></div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $reward->title }}</p>
                                    @if ($reward->product)
                                        <p class="truncate text-xs font-medium text-brand-600 dark:text-brand-300">{{ __('Producto: :product', ['product' => $reward->product->name]) }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-zinc-500">
                                        {{ __(':points Merkapuntos · costo $:cost · :stock', [
                                            'points' => $reward->points_cost,
                                            'cost' => number_format($reward->full_cost_cents / 100, 0, ',', '.'),
                                            'stock' => $reward->stock_total === null ? __('sin límite de unidades') : __(':available de :total disponibles', ['available' => $reward->stockAvailable(), 'total' => $reward->stock_total]),
                                        ]) }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <flux:badge size="sm" :color="match ($reward->status) {
                                        'publicado' => 'green',
                                        'pausado' => 'amber',
                                        'agotado' => 'red',
                                        'archivado' => 'zinc',
                                        default => 'zinc',
                                    }">
                                        {{ match ($reward->status) {
                                            'borrador' => __('Borrador'),
                                            'publicado' => __('Activo'),
                                            'pausado' => __('Pausado'),
                                            'agotado' => __('Agotado'),
                                            'archivado' => __('Archivado'),
                                            default => $reward->status,
                                        } }}
                                    </flux:badge>

                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editReward({{ $reward->id }})" aria-label="{{ __('Editar :reward', ['reward' => $reward->title]) }}" />

                                    @if ($reward->status === 'borrador')
                                        <flux:button size="sm" variant="primary" wire:click="publishReward({{ $reward->id }})">{{ __('Publicar') }}</flux:button>
                                    @elseif ($reward->status === 'publicado')
                                        <flux:button size="sm" variant="ghost" wire:click="pauseReward({{ $reward->id }})">{{ __('Pausar') }}</flux:button>
                                    @elseif ($reward->status === 'pausado')
                                        <flux:button size="sm" variant="ghost" wire:click="resumeReward({{ $reward->id }})">{{ __('Reactivar') }}</flux:button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endunless
        </div>

        <flux:modal name="premio" class="w-full !max-w-2xl">
            <form wire:submit="saveReward" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $editingRewardId ? __('Editar premio') : __('Nuevo premio') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Asocia la recompensa a un producto y agrega una imagen especial para la promoción.') }}</flux:text>
                </div>

                <flux:select wire:model="rewardProductId" :label="__('Producto asociado')">
                    <flux:select.option value="">{{ __('Selecciona un producto') }}</flux:select.option>
                    @foreach ($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="rewardTitle" :label="__('Título')" placeholder="{{ __('Café de la casa') }}" />
                <flux:textarea wire:model="rewardDescription" :label="__('Descripción (opcional)')" rows="2" />

                <flux:input type="file" wire:model="rewardImage" :label="__('Imagen de la promoción (opcional)')" accept="image/jpeg,image/png,image/webp" />

                @if ($rewardImage || $rewardExistingImageUrl)
                    <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                        <img src="{{ $rewardImage?->temporaryUrl() ?? $rewardExistingImageUrl }}" class="aspect-[16/9] w-full object-cover" alt="{{ __('Vista previa de la promoción') }}">
                        <p class="bg-zinc-50 px-4 py-2 text-xs text-zinc-500 dark:bg-white/[0.03]">{{ $rewardImage ? __('Nueva imagen promocional') : __('Imagen promocional actual') }}</p>
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input type="number" min="1" wire:model.live="rewardPointsCost" :label="__('Merkapuntos')" placeholder="120" />
                    <flux:input type="number" min="1" wire:model.live="rewardFullCostCop" :label="__('Costo real del premio (COP)')" placeholder="8000" />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input type="number" min="1" wire:model="rewardStockTotal" :label="__('Unidades disponibles (opcional)')" placeholder="{{ __('Sin límite') }}" />
                    <flux:input type="date" wire:model="rewardValidUntil" :label="__('Vigente hasta (opcional)')" />
                </div>

                <flux:text class="text-xs text-zinc-400">{{ __('El costo real es lo que de verdad te cuesta entregar este premio — se usa para proteger tu presupuesto, no es un precio al público.') }}</flux:text>

                {{--
                    Asistente de recompensa (TODO_Correccion_Logica_Merkapuntos.md):
                    los puntos se calculan desde el ticket promedio × cuántas
                    compras quieres premiar, NUNCA desde el costo del premio
                    directamente — el costo solo se usa para mostrar qué tan
                    caro es el incentivo frente a ese gasto. Todo se recalcula
                    solo, sin botón de "calcular". El ticket promedio es
                    AUTOMÁTICO (pedido del usuario: "así es muy difícil para
                    el emprendedor") — solo se pide a mano cuando el negocio
                    es tan nuevo que todavía no hay suficientes compras
                    registradas para calcularlo.
                --}}
                <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-500/10">
                    <p class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-violet-700 dark:text-violet-300">
                        <flux:icon.light-bulb class="size-4" variant="solid" />
                        {{ __('Asistente de recompensa') }}
                    </p>
                    <flux:text class="mb-3 text-xs text-zinc-600 dark:text-zinc-300">{{ __('Te ayudamos a crear una recompensa atractiva para tus clientes sin afectar innecesariamente tu margen.') }}</flux:text>

                    @if ($this->averageTicketCents && ! $overrideAverageTicket)
                        <div class="flex items-center justify-between gap-2 rounded-xl border border-violet-200 bg-white/70 px-3 py-2 dark:border-violet-800 dark:bg-zinc-900/50">
                            <div>
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">{{ __('Ticket promedio (calculado automáticamente)') }}</p>
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">${{ number_format($this->averageTicketCents / 100, 0, ',', '.') }}</p>
                            </div>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="toggleAverageTicketOverride">{{ __('Ajustar') }}</flux:button>
                        </div>
                    @else
                        <flux:input type="number" min="1" wire:model.live="rewardAverageTicketCop" :label="__('Ticket promedio de tus clientes (COP)')" placeholder="20000" />

                        @if ($this->averageTicketCents)
                            <flux:button type="button" variant="ghost" size="sm" wire:click="toggleAverageTicketOverride" class="mt-1.5">{{ __('Usar el calculado automáticamente') }}</flux:button>
                        @else
                            <flux:text class="mt-1.5 text-[0.65rem] text-zinc-500">{{ __('Aún no tienes suficientes compras registradas en Merkapuntos para calcularlo solo — en cuanto las tengas, lo calculamos por ti.') }}</flux:text>
                        @endif
                    @endif

                    @if ($this->rewardSuggestion && isset($this->rewardSuggestion['error']))
                        <p class="mt-3 text-xs text-red-600 dark:text-red-400">{{ $this->rewardSuggestion['error'] }}</p>
                    @elseif ($this->rewardSuggestion)
                        <p class="mb-2 mt-4 text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ __('¿Cuándo quieres premiar a tu cliente?') }}</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($this->rewardSuggestion['recommendations'] as $option)
                                <button
                                    type="button"
                                    wire:click="useSuggestion({{ $option['points'] }})"
                                    @class([
                                        'rounded-xl border p-2.5 text-left transition hover:border-brand-300',
                                        'border-brand-400 bg-white ring-1 ring-brand-300 dark:bg-zinc-900' => $option['purchases'] === 6,
                                        'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $option['purchases'] !== 6,
                                    ])
                                >
                                    <p class="text-[0.6rem] font-semibold uppercase tracking-wide text-zinc-500">
                                        @if ($option['purchases'] === 6)
                                            ⭐ {{ __('Recomendado') }}
                                        @elseif ($option['purchases'] < 6)
                                            🔥 {{ __('Más atractivo') }}
                                        @else
                                            🛡 {{ __('Protege tu margen') }}
                                        @endif
                                    </p>
                                    <p class="text-lg font-bold text-brand-600 dark:text-brand-300">{{ $option['points'] }}</p>
                                    <p class="text-[0.65rem] text-zinc-500">{{ __('Merkapuntos') }}</p>
                                    <p class="mt-1.5 text-[0.65rem] text-zinc-500">{{ __('≈ :count compras', ['count' => $option['purchases']]) }}</p>
                                    <p class="text-[0.65rem] text-zinc-500">${{ number_format($option['target_spend_cents'] / 100, 0, ',', '.') }}</p>
                                    <p class="text-[0.65rem] text-zinc-500">{{ __('Incentivo: :pct%', ['pct' => rtrim(rtrim(number_format($option['percentage'], 2), '0'), '.')]) }}</p>
                                    @foreach ($option['warnings'] as $warning)
                                        <p class="mt-1 text-[0.6rem] leading-tight text-amber-600 dark:text-amber-400">⚠️ {{ __($warning) }}</p>
                                    @endforeach
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->manualPointsEstimate)
                        <div class="mt-3 rounded-xl border border-zinc-200 bg-white/70 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
                            <p>{{ __('Gasto aproximado requerido: :amount', ['amount' => '$'.number_format($this->manualPointsEstimate['required_spend_cents'] / 100, 0, ',', '.')]) }}</p>
                            @if ($this->manualPointsEstimate['estimated_purchases'] !== null)
                                <p>{{ __('Número aproximado de compras: ≈ :count', ['count' => number_format($this->manualPointsEstimate['estimated_purchases'], 1)]) }}</p>
                            @endif
                            @if ($this->manualPointsEstimate['percentage'] !== null)
                                <p>{{ __('Incentivo equivalente: :pct%', ['pct' => rtrim(rtrim(number_format($this->manualPointsEstimate['percentage'], 2), '0'), '.')]) }}</p>
                            @endif
                            @foreach ($this->manualPointsEstimate['warnings'] as $warning)
                                <p class="mt-1 text-amber-600 dark:text-amber-400">⚠️ {{ __($warning) }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveReward,rewardImage">
                        {{ $editingRewardId ? __('Guardar cambios') : __('Guardar como borrador') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Escáner --}}
    @if ($activeTab === 'escaner')
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            @if (! $scanResult)
                <div
                    x-data="merkamigoQrImageScanner($wire, {
                        invalidImage: @js(__('Selecciona una foto válida.')),
                        imageTooLarge: @js(__('La imagen no puede superar 15 MB.')),
                        qrNotFound: @js(__('No encontramos un código QR legible. Acerca la cámara, evita reflejos e inténtalo de nuevo.')),
                        unknownError: @js(__('No pudimos leer la imagen. Inténtalo de nuevo o ingresa el código manualmente.')),
                    })"
                >
                    <flux:heading size="lg" class="mb-1">{{ __('Escanear código QR') }}</flux:heading>
                    <flux:text class="mb-5 text-sm text-zinc-500">{{ __('Toma una foto del QR del cliente o del canje. Al detectarlo, continuaremos automáticamente con el proceso correspondiente.') }}</flux:text>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <input x-ref="cameraInput" type="file" class="hidden" accept="image/*" capture="environment" x-on:change="decode($event)">
                        <button type="button" x-on:click="$refs.cameraInput.click()" x-bind:disabled="reading" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 disabled:cursor-wait disabled:opacity-60">
                            <flux:icon.camera class="size-5" />
                            {{ __('Tomar foto del QR') }}
                        </button>

                        <input x-ref="galleryInput" type="file" class="hidden" accept="image/*" x-on:change="decode($event)">
                        <button type="button" x-on:click="$refs.galleryInput.click()" x-bind:disabled="reading" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 disabled:cursor-wait disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            <flux:icon.photo class="size-5" />
                            {{ __('Elegir imagen del QR') }}
                        </button>
                    </div>

                    <div x-cloak x-show="reading" class="mt-3 flex items-center gap-2 text-sm font-medium text-brand-600">
                        <flux:icon.loading class="size-4" />
                        {{ __('Leyendo el código QR…') }}
                    </div>

                    <p x-cloak x-show="error" x-text="error" role="alert" class="mt-3 text-sm text-red-600 dark:text-red-400"></p>

                    <div class="my-5 flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                        {{ __('O usa el código manual') }}
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                    </div>

                    <form wire:submit="scan" class="flex gap-2">
                        <flux:input wire:model="scanInput" placeholder="idn_... {{ __('o') }} rdm_..." class="flex-1" />
                        <flux:button type="submit" variant="primary">{{ __('Continuar') }}</flux:button>
                    </form>
                </div>
            @elseif ($scanResult['type'] === 'purchase')
                <flux:badge color="blue" class="mb-3">{{ __('Cliente identificado') }}</flux:badge>
                <flux:heading size="lg" class="mb-4">{{ $scanResult['customer_name'] }}</flux:heading>

                <flux:field class="mb-2">
                    <flux:label>{{ __('Valor pagado (COP)') }}</flux:label>
                    <flux:input type="number" min="1" wire:model.live="purchaseAmountCop" placeholder="40000" />
                </flux:field>

                @if ($this->scanPreviewPoints > 0)
                    <flux:text class="mb-4 text-sm font-semibold text-brand-600">{{ __('Se acreditarán :points Merkapuntos.', ['points' => $this->scanPreviewPoints]) }}</flux:text>
                @elseif (! $this->activePolicy)
                    <flux:text class="mb-4 text-sm text-amber-600">{{ __('Este negocio no tiene una política de acumulación activa.') }}</flux:text>
                @endif

                <div class="flex gap-2">
                    <flux:button variant="ghost" wire:click="cancelScan">{{ __('Cancelar') }}</flux:button>
                    <flux:button variant="primary" class="flex-1" wire:click="confirmPurchase" wire:loading.attr="disabled" wire:target="confirmPurchase">
                        {{ __('Registrar y dar :points puntos', ['points' => $this->scanPreviewPoints]) }}
                    </flux:button>
                </div>
            @elseif ($scanResult['type'] === 'delivery')
                @php($redemption = \App\Domain\Loyalty\Models\LoyaltyRedemption::with('account.user')->find($scanResult['redemption_id']))
                @if ($redemption && $redemption->status === 'reservado')
                    <flux:badge color="amber" class="mb-3">{{ __('Canje identificado') }}</flux:badge>
                    <flux:heading size="lg" class="mb-1">{{ $redemption->reward_snapshot['title'] ?? __('Premio') }}</flux:heading>
                    <flux:text class="mb-4 text-sm text-zinc-500">
                        {{ __('Cliente: :name · :points Merkapuntos', ['name' => $redemption->account->user->name, 'points' => $redemption->points_reserved]) }}
                    </flux:text>

                    <div class="flex gap-2">
                        <flux:button variant="ghost" wire:click="cancelScan">{{ __('Cancelar') }}</flux:button>
                        <flux:button variant="primary" class="flex-1" wire:click="confirmDelivery" wire:loading.attr="disabled" wire:target="confirmDelivery">
                            {{ __('Entregar premio') }}
                        </flux:button>
                    </div>
                @else
                    <x-states.empty :title="__('Este canje ya no está disponible')" :description="__('Puede que ya se haya entregado, cancelado o vencido.')" />
                    <flux:button variant="ghost" class="mt-4" wire:click="cancelScan">{{ __('Volver al escáner') }}</flux:button>
                @endif
            @endif
        </div>
    @endif

    {{-- Resultados --}}
    @if ($activeTab === 'resultados')
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ __('Clientes') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->metrics['customers'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ __('Puntos emitidos') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->metrics['issued'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ __('Canjes entregados') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->metrics['redeemed'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ __('Canjes pendientes') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->metrics['pending'] }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="mb-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Presupuesto comprometido') }}</p>
                <p class="text-sm text-zinc-500">
                    {{ __('$:spent gastado · $:reserved reservado en canjes pendientes', [
                        'spent' => number_format($this->metrics['budgetSpentCents'] / 100, 0, ',', '.'),
                        'reserved' => number_format($this->metrics['budgetReservedCents'] / 100, 0, ',', '.'),
                    ]) }}
                </p>
            </div>

            <div>
                <flux:heading size="lg" class="mb-3">{{ __('Solicitudes excepcionales pendientes') }}</flux:heading>

                @if ($this->pendingClaims->isEmpty())
                    <x-states.empty :title="__('Sin solicitudes pendientes')" :description="__('Cuando un cliente reporte una compra olvidada, aparecerá aquí.')" />
                @else
                    <div class="space-y-3">
                        @foreach ($this->pendingClaims as $claim)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $claim->customer->name }}</p>
                                    @if ($claim->description)
                                        <p class="truncate text-sm text-zinc-500">{{ $claim->description }}</p>
                                    @endif
                                    <p class="text-xs text-zinc-400">{{ $claim->created_at->diffForHumans() }}</p>
                                </div>
                                <flux:button size="sm" variant="primary" wire:click="startReviewingClaim({{ $claim->id }})">{{ __('Revisar') }}</flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <flux:modal name="revisar-solicitud" class="w-full !max-w-md">
            @if ($this->reviewingClaim)
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __('Revisar solicitud') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ $this->reviewingClaim->customer->name }}</flux:text>

                    @if ($this->reviewingClaim->description)
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">{{ $this->reviewingClaim->description }}</flux:text>
                    @endif

                    <flux:link :href="route('emprendedores.negocios.merkapuntos.solicitudes.recibo', [$this->business, $this->reviewingClaim])" target="_blank">
                        {{ __('Ver recibo adjunto') }} →
                    </flux:link>

                    <flux:field>
                        <flux:label>{{ __('Valor pagado (COP)') }}</flux:label>
                        <flux:input type="number" min="1" wire:model="reviewAmountCop" placeholder="40000" />
                    </flux:field>

                    <flux:button variant="primary" class="w-full" wire:click="approveClaim" wire:loading.attr="disabled" wire:target="approveClaim">
                        {{ __('Aprobar y acreditar puntos') }}
                    </flux:button>

                    <flux:textarea wire:model="rejectReason" :label="__('Motivo del rechazo (si vas a rechazar)')" rows="2" />
                    <flux:button variant="danger" class="w-full" wire:click="rejectClaim" wire:loading.attr="disabled" wire:target="rejectClaim">
                        {{ __('Rechazar') }}
                    </flux:button>
                </div>
            @endif
        </flux:modal>
    @endif
</section>
