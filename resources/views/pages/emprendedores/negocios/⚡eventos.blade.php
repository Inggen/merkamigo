<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Actions\CheckInEventAttendance;
use App\Domain\Events\Actions\ManageEventBlockedDate;
use App\Domain\Events\Actions\ManageEventBusinessEquipment;
use App\Domain\Events\Actions\ManageEventDish;
use App\Domain\Events\Actions\ManageEventSpace;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Actions\PublishPublicEventToFeed;
use App\Domain\Events\Actions\UpdateEventSettings;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use App\Domain\Events\Models\EventEquipmentType;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Events\Models\EventSetting;
use App\Support\Media\MediaUploader;
use Flux\Flux;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel de Eventos del negocio
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fases 2 y 5): activar/
 * desactivar el módulo, configurar agenda/tarifas/menú/equipos — "sin
 * tocar código" (criterio de aceptación de la Fase 2) — y administrar
 * los eventos públicos de la agenda (Fase 5: formulario, estados,
 * "Publicar en el feed"). Mismo patrón de panel con pestañas que
 * `⚡merkapuntos.blade.php`. La pestaña "Reservas" lista las reservas
 * privadas reales creadas desde el cotizador público (Fase 3/4).
 */
new #[Title('Eventos')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $activeTab = 'agenda';

    public string $configTab = 'general';

    // Configuración general
    public bool $enabled = false;

    public bool $publicEventsEnabled = false;

    public bool $privateReservationsEnabled = false;

    public ?int $maxCapacity = null;

    public int $maxDurationHours = 3;

    public int $minAdvanceHours = 24;

    public int $holdMinutes = 30;

    public string $policyText = '';

    public string $cancellationText = '';

    /** @var array<string, array{closed: bool, open: ?string, close: ?string}> */
    public array $weeklySchedule = [];

    // Tarifas
    public string $pricingMode = 'platos';

    public ?int $hourlyRateCop = null;

    // Nuevo espacio
    public string $spaceName = '';

    public ?int $spaceCapacity = null;

    public ?UploadedFile $spaceImage = null;

    public ?int $editingSpaceId = null;

    public ?string $currentSpaceImageUrl = null;

    // Nueva fecha bloqueada
    public string $blockedDate = '';

    public string $blockedReason = '';

    // Nuevo plato
    public string $dishName = '';

    public string $dishDescription = '';

    public ?int $dishPriceCop = null;

    // Nuevo equipo propio
    public string $customEquipmentName = '';

    public ?int $customEquipmentFeeCop = null;

    // Evento público (Fase 5)
    public ?int $editingPublicEventId = null;

    public string $eventTitle = '';

    public string $eventCategory = '';

    public string $eventDescription = '';

    public ?UploadedFile $eventCover = null;

    public ?string $currentEventCoverUrl = null;

    public ?int $eventMunicipalityId = null;

    public string $eventLocationText = '';

    public string $eventStartsAt = '';

    public string $eventEndsAt = '';

    public ?int $eventCapacity = null;

    public bool $eventBlocksSpace = false;

    public ?int $eventSpaceId = null;

    // Check-in de asistentes (pedido del usuario, 2026-10-08): leer el QR
    // de una reserva de cupo para verificar la asistencia en la puerta.
    public string $checkinInput = '';

    public ?array $checkinResult = null;

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

        $settings = $business->eventSetting;

        $this->enabled = $settings?->enabled ?? false;
        $this->publicEventsEnabled = $settings?->public_events_enabled ?? false;
        $this->privateReservationsEnabled = $settings?->private_reservations_enabled ?? false;
        $this->maxCapacity = $settings?->max_capacity;
        $this->maxDurationHours = $settings?->max_duration_hours ?? 3;
        $this->minAdvanceHours = $settings?->min_advance_hours ?? 24;
        $this->holdMinutes = $settings?->hold_minutes ?? 30;
        $this->policyText = $settings?->policy_text ?? '';
        $this->cancellationText = $settings?->cancellation_text ?? '';
        $this->pricingMode = $settings?->pricing_mode ?? EventSetting::PRICING_PLATOS;
        $this->hourlyRateCop = $settings?->hourly_rate_cents ? intdiv($settings->hourly_rate_cents, 100) : null;

        $defaultSchedule = [];
        foreach (Business::DAY_LABELS as $day => $label) {
            $defaultSchedule[$day] = ['closed' => true, 'open' => null, 'close' => null];
        }
        $this->weeklySchedule = array_replace_recursive($defaultSchedule, $settings?->weekly_schedule ?? []);
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function spaces()
    {
        return $this->business->eventSpaces()->orderBy('name')->get();
    }

    #[Computed]
    public function blockedDates()
    {
        return $this->business->eventBlockedDates()->orderBy('date')->get();
    }

    #[Computed]
    public function dishes()
    {
        return $this->business->eventDishes()->orderBy('position')->get();
    }

    #[Computed]
    public function equipmentTypes()
    {
        return EventEquipmentType::query()->where('is_active', true)->orderBy('position')->get();
    }

    #[Computed]
    public function selectedEquipment()
    {
        return $this->business->eventEquipment()->with('equipmentType')->get();
    }

    #[Computed]
    public function reservations()
    {
        return $this->business->eventReservations()->latest('starts_at')->paginate(10);
    }

    #[Computed]
    public function publicEvents()
    {
        return $this->business->publicEvents()->with('post')->latest('starts_at')->get();
    }

    #[Computed]
    public function municipalities()
    {
        return Municipality::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Métricas de eventos pedidas por el usuario (2026-10-08): "cantidad
     * de reservas, cantidad de pagos realizados en línea, cantidad de
     * personas que asistieron". Agregadas a nivel de negocio (todas sus
     * reservas de cupo), no solo del evento que se esté editando.
     *
     * @return array{reservations: int, onlinePayments: int, attended: int}
     */
    #[Computed]
    public function attendanceMetrics(): array
    {
        $attendanceIds = $this->business->eventAttendances()->pluck('id');

        return [
            'reservations' => $attendanceIds->count(),
            'onlinePayments' => EventAttendancePaymentAttempt::whereIn('event_attendance_id', $attendanceIds)
                ->where('status', EventAttendancePaymentAttempt::APROBADO)
                ->count(),
            'attended' => (int) $this->business->eventAttendances()->whereNotNull('checked_in_at')->sum('quantity'),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setConfigTab(string $tab): void
    {
        $this->configTab = $tab;
    }

    public function saveGeneral(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'maxCapacity' => ['nullable', 'integer', 'min:1'],
            'maxDurationHours' => ['required', 'integer', 'min:1'],
            'minAdvanceHours' => ['required', 'integer', 'min:0'],
            'holdMinutes' => ['required', 'integer', 'min:5'],
            'policyText' => ['nullable', 'string', 'max:2000'],
            'cancellationText' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(UpdateEventSettings::class)->handle($this->business, [
                'enabled' => $this->enabled,
                'public_events_enabled' => $this->publicEventsEnabled,
                'private_reservations_enabled' => $this->privateReservationsEnabled,
                'max_capacity' => $this->maxCapacity,
                'max_duration_hours' => $this->maxDurationHours,
                'min_advance_hours' => $this->minAdvanceHours,
                'hold_minutes' => $this->holdMinutes,
                'policy_text' => $this->policyText ?: null,
                'cancellation_text' => $this->cancellationText ?: null,
                'weekly_schedule' => $this->weeklySchedule,
            ], Auth::user());
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->business);
        Flux::toast(text: __('Configuración general guardada.'));
    }

    public function saveRates(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'pricingMode' => ['required', 'in:platos,horas,hibrido'],
            'hourlyRateCop' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            app(UpdateEventSettings::class)->handle($this->business, [
                'pricing_mode' => $this->pricingMode,
                'hourly_rate_cents' => $this->hourlyRateCop ? $this->hourlyRateCop * 100 : null,
            ]);
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->business);
        Flux::toast(text: __('Tarifas guardadas.'));
    }

    public function addSpace(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'spaceName' => ['required', 'string', 'max:120'],
            'spaceCapacity' => ['nullable', 'integer', 'min:1'],
            'spaceImage' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = [
            'name' => $this->spaceName,
            'capacity' => $this->spaceCapacity,
        ];

        if ($this->editingSpaceId) {
            $space = $this->business->eventSpaces()->findOrFail($this->editingSpaceId);
            app(ManageEventSpace::class)->update($space, $data, $this->spaceImage);
            $message = __('Espacio actualizado.');
        } else {
            app(ManageEventSpace::class)->create($this->business, $data, $this->spaceImage);
            $message = __('Espacio agregado.');
        }

        $this->resetSpaceForm();
        unset($this->spaces);
        Flux::modal('espacio-evento')->close();
        Flux::toast(text: $message);
    }

    public function startNewSpace(): void
    {
        $this->resetSpaceForm();
        Flux::modal('espacio-evento')->show();
    }

    public function editSpace(int $spaceId): void
    {
        $this->authorize('update', $this->business);

        $space = $this->business->eventSpaces()->findOrFail($spaceId);
        $this->editingSpaceId = $space->id;
        $this->spaceName = $space->name;
        $this->spaceCapacity = $space->capacity;
        $this->spaceImage = null;
        $this->currentSpaceImageUrl = $space->imageUrl();
        Flux::modal('espacio-evento')->show();
    }

    public function cancelSpaceEdit(): void
    {
        $this->resetSpaceForm();
        Flux::modal('espacio-evento')->close();
    }

    public function deleteSpace(int $spaceId): void
    {
        $this->authorize('update', $this->business);

        $space = $this->business->eventSpaces()->findOrFail($spaceId);
        app(ManageEventSpace::class)->delete($space);

        unset($this->spaces);
        Flux::toast(text: __('Espacio eliminado.'));
    }

    private function resetSpaceForm(): void
    {
        $this->reset(['spaceName', 'spaceCapacity', 'spaceImage', 'editingSpaceId', 'currentSpaceImageUrl']);
        $this->resetValidation(['spaceName', 'spaceCapacity', 'spaceImage']);
    }

    public function addBlockedDate(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'blockedDate' => ['required', 'date', 'after_or_equal:today'],
            'blockedReason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(ManageEventBlockedDate::class)->add($this->business, $this->blockedDate, $this->blockedReason ?: null);
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->reset(['blockedDate', 'blockedReason']);
        unset($this->blockedDates);
        Flux::modal('fecha-bloqueada')->close();
        Flux::toast(text: __('Fecha bloqueada.'));
    }

    public function startNewBlockedDate(): void
    {
        $this->reset(['blockedDate', 'blockedReason']);
        $this->resetValidation(['blockedDate', 'blockedReason']);
        Flux::modal('fecha-bloqueada')->show();
    }

    public function removeBlockedDate(int $blockedDateId): void
    {
        $this->authorize('update', $this->business);

        $blockedDate = $this->business->eventBlockedDates()->findOrFail($blockedDateId);
        app(ManageEventBlockedDate::class)->remove($blockedDate);

        unset($this->blockedDates);
        Flux::toast(text: __('Bloqueo eliminado.'));
    }

    public function addDish(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'dishName' => ['required', 'string', 'max:120'],
            'dishDescription' => ['nullable', 'string', 'max:255'],
            'dishPriceCop' => ['required', 'integer', 'min:0'],
        ]);

        app(ManageEventDish::class)->create($this->business, [
            'name' => $this->dishName,
            'description' => $this->dishDescription ?: null,
            'price_cents' => $this->dishPriceCop * 100,
        ]);

        $this->reset(['dishName', 'dishDescription', 'dishPriceCop']);
        unset($this->dishes);
        Flux::toast(text: __('Plato agregado.'));
    }

    public function toggleDishAvailability(int $dishId): void
    {
        $this->authorize('update', $this->business);

        $dish = $this->business->eventDishes()->findOrFail($dishId);
        app(ManageEventDish::class)->update($dish, ['is_available' => ! $dish->is_available]);

        unset($this->dishes);
    }

    public function deleteDish(int $dishId): void
    {
        $this->authorize('update', $this->business);

        $dish = $this->business->eventDishes()->findOrFail($dishId);
        app(ManageEventDish::class)->delete($dish);

        unset($this->dishes);
        Flux::toast(text: __('Plato eliminado.'));
    }

    public function toggleGlobalEquipment(int $typeId): void
    {
        $this->authorize('update', $this->business);

        $type = EventEquipmentType::findOrFail($typeId);
        $already = $this->business->eventEquipment()->where('event_equipment_type_id', $type->id)->exists();

        if ($already) {
            app(ManageEventBusinessEquipment::class)->unselectGlobal($this->business, $type);
        } else {
            app(ManageEventBusinessEquipment::class)->selectGlobal($this->business, $type);
        }

        unset($this->selectedEquipment);
    }

    public function addCustomEquipment(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'customEquipmentName' => ['required', 'string', 'max:120'],
            'customEquipmentFeeCop' => ['nullable', 'integer', 'min:0'],
        ]);

        app(ManageEventBusinessEquipment::class)->addCustom($this->business, [
            'custom_name' => $this->customEquipmentName,
            'fee_cents' => $this->customEquipmentFeeCop ? $this->customEquipmentFeeCop * 100 : null,
        ]);

        $this->reset(['customEquipmentName', 'customEquipmentFeeCop']);
        unset($this->selectedEquipment);
        Flux::toast(text: __('Equipo agregado.'));
    }

    public function removeEquipment(int $equipmentId): void
    {
        $this->authorize('update', $this->business);

        $equipment = $this->business->eventEquipment()->findOrFail($equipmentId);
        app(ManageEventBusinessEquipment::class)->remove($equipment);

        unset($this->selectedEquipment);
        Flux::toast(text: __('Equipo eliminado.'));
    }

    public function startNewPublicEvent(): void
    {
        $this->reset([
            'editingPublicEventId', 'eventTitle', 'eventCategory', 'eventDescription', 'eventCover', 'currentEventCoverUrl',
            'eventMunicipalityId', 'eventLocationText', 'eventStartsAt', 'eventEndsAt', 'eventCapacity',
            'eventBlocksSpace', 'eventSpaceId',
        ]);
        $this->eventMunicipalityId = $this->business->municipality_id;
        Flux::modal('evento-publico')->show();
    }

    public function editPublicEvent(int $eventId): void
    {
        $event = $this->business->publicEvents()->findOrFail($eventId);

        $this->editingPublicEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventCategory = $event->category ?? '';
        $this->eventDescription = $event->description ?? '';
        $this->eventCover = null;
        $this->currentEventCoverUrl = $event->coverUrl();
        $this->eventMunicipalityId = $event->municipality_id;
        $this->eventLocationText = $event->location_text ?? '';
        $this->eventStartsAt = $event->starts_at->format('Y-m-d\TH:i');
        $this->eventEndsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->eventCapacity = $event->capacity;
        $this->eventBlocksSpace = $event->blocks_space;
        $this->eventSpaceId = $event->event_space_id;

        Flux::modal('evento-publico')->show();
    }

    public function savePublicEvent(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'eventTitle' => ['required', 'string', 'max:160'],
            'eventCategory' => ['nullable', 'string', 'max:40'],
            'eventDescription' => ['nullable', 'string', 'max:3000'],
            'eventCover' => ['nullable', 'image', 'max:5120'],
            'eventMunicipalityId' => ['nullable', 'integer'],
            'eventLocationText' => ['nullable', 'string', 'max:160'],
            'eventStartsAt' => ['required', 'date'],
            'eventEndsAt' => ['nullable', 'date', 'after:eventStartsAt'],
            'eventCapacity' => ['nullable', 'integer', 'min:1'],
            'eventBlocksSpace' => ['boolean'],
            'eventSpaceId' => ['nullable', 'integer'],
        ]);

        $data = [
            'title' => $this->eventTitle,
            'category' => $this->eventCategory ?: null,
            'description' => $this->eventDescription ?: null,
            'municipality_id' => $this->eventMunicipalityId,
            'location_text' => $this->eventLocationText ?: null,
            'starts_at' => $this->eventStartsAt,
            'ends_at' => $this->eventEndsAt ?: null,
            'capacity' => $this->eventCapacity,
            'blocks_space' => $this->eventBlocksSpace,
            'event_space_id' => $this->eventBlocksSpace ? $this->eventSpaceId : null,
        ];

        if ($this->eventCover) {
            $data['cover_path'] = app(MediaUploader::class)->store($this->eventCover, 'event_cover', "events/{$this->business->id}");
        }

        try {
            if ($this->editingPublicEventId) {
                $event = $this->business->publicEvents()->findOrFail($this->editingPublicEventId);
                app(ManagePublicEvent::class)->update($event, $data);
            } else {
                app(ManagePublicEvent::class)->create($this->business, $data, Auth::user());
            }
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->publicEvents);
        Flux::modal('evento-publico')->close();
        Flux::toast(text: __('Evento guardado.'));
    }

    public function publishPublicEvent(int $eventId): void
    {
        $this->authorize('update', $this->business);

        $event = $this->business->publicEvents()->findOrFail($eventId);

        try {
            app(ManagePublicEvent::class)->publish($event, Auth::user());
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->publicEvents);
        Flux::toast(text: __('Evento publicado en la agenda pública.'));
    }

    public function cancelPublicEvent(int $eventId): void
    {
        $this->authorize('update', $this->business);

        $event = $this->business->publicEvents()->findOrFail($eventId);

        try {
            app(ManagePublicEvent::class)->cancel($event, Auth::user());
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->publicEvents);
        Flux::toast(text: __('Evento cancelado.'));
    }

    public function deletePublicEvent(int $eventId): void
    {
        $this->authorize('update', $this->business);

        $event = $this->business->publicEvents()->findOrFail($eventId);

        try {
            app(ManagePublicEvent::class)->delete($event);
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->publicEvents);
        Flux::toast(text: __('Evento eliminado.'));
    }

    public function publishToFeed(int $eventId): void
    {
        $this->authorize('update', $this->business);

        $event = $this->business->publicEvents()->findOrFail($eventId);

        try {
            app(PublishPublicEventToFeed::class)->handle($event, Auth::user());
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->publicEvents);
        Flux::toast(text: __('Publicado en el feed.'));
    }

    public function lookupCheckin(): void
    {
        $this->authorize('view', $this->business);

        $raw = trim($this->checkinInput);

        if ($raw === '') {
            return;
        }

        try {
            $attendance = app(CheckInEventAttendance::class)->findByToken($this->business, $raw);
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->checkinResult = ['attendance_id' => $attendance->id];
    }

    public function confirmCheckin(): void
    {
        if (! $this->checkinResult) {
            return;
        }

        $attendance = EventAttendance::findOrFail($this->checkinResult['attendance_id']);

        try {
            app(CheckInEventAttendance::class)->handle($this->business, Auth::user(), $attendance);
        } catch (EventActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->attendanceMetrics);
        Flux::toast(text: __('Asistencia registrada.'));
        $this->cancelCheckin();
    }

    public function cancelCheckin(): void
    {
        $this->reset(['checkinInput', 'checkinResult']);
    }
}; ?>

<section class="mx-auto w-full max-w-6xl space-y-5">
    <header class="entrepreneur-card overflow-hidden bg-gradient-to-r from-white via-white to-rose-50 p-6 dark:from-zinc-900 dark:via-zinc-900 dark:to-rose-950/30">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon.calendar-days class="size-7" variant="outline" />
                </span>
                <div>
                    <flux:heading size="xl">{{ __('Eventos') }}</flux:heading>
                    <flux:text class="mt-1 max-w-2xl text-zinc-500 dark:text-zinc-400">{{ __('Organiza tus espacios, horarios y eventos desde un solo lugar.') }}</flux:text>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center">
                @foreach ([
                    [__('Espacios'), $this->spaces->count()],
                    [__('Eventos'), $this->publicEvents->count()],
                    [__('Reservas'), $this->reservations->total()],
                ] as [$label, $value])
                    <div class="min-w-20 rounded-xl border border-zinc-200/80 bg-white/80 px-3 py-2 dark:border-white/10 dark:bg-white/5">
                        <p class="text-lg font-black text-zinc-950 dark:text-white">{{ $value }}</p>
                        <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </header>

    <nav class="flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800" aria-label="{{ __('Secciones de Eventos') }}">
        @foreach (['agenda' => __('Agenda'), 'reservas' => __('Reservas'), 'checkin' => __('Check-in'), 'configuracion' => __('Configuración')] as $tab => $label)
            <button
                type="button"
                wire:click="setTab('{{ $tab }}')"
                aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}"
                @class([
                    'shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition',
                    'bg-white text-brand-600 shadow-sm dark:bg-zinc-700 dark:text-brand-300' => $activeTab === $tab,
                    'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => $activeTab !== $tab,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- Agenda: horario, espacios y fechas bloqueadas --}}
    @if ($activeTab === 'agenda')
        <div class="space-y-5">
            <div class="entrepreneur-card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-zinc-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                            <flux:icon.calendar-date-range class="size-5" variant="outline" />
                        </span>
                        <div>
                            <flux:heading size="lg">{{ __('Fechas bloqueadas') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Días en los que no recibes eventos, aunque estén dentro de tu horario habitual.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ trans_choice(':count fecha|:count fechas', $this->blockedDates->count(), ['count' => $this->blockedDates->count()]) }}</flux:badge>
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="startNewBlockedDate">{{ __('Agregar fecha') }}</flux:button>
                    </div>
                </div>

                <div class="p-6">
                    @if ($this->blockedDates->isNotEmpty())
                        <div class="space-y-2">
                            @foreach ($this->blockedDates as $blocked)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 dark:border-white/10 dark:bg-white/[0.03]">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $blocked->date->translatedFormat('d \d\e F \d\e Y') }}</p>
                                        @if ($blocked->reason)
                                            <p class="text-xs text-zinc-500">{{ $blocked->reason }}</p>
                                        @endif
                                    </div>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeBlockedDate({{ $blocked->id }})" aria-label="{{ __('Quitar bloqueo') }}" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/60 px-6 py-8 text-center dark:border-zinc-700 dark:bg-white/[0.02]">
                            <flux:icon.calendar-days class="mx-auto size-8 text-zinc-400" variant="outline" />
                            <p class="mt-2 text-sm font-medium text-zinc-500">{{ __('No tienes fechas bloqueadas.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <form wire:submit="saveGeneral" class="entrepreneur-card p-6">
                <div class="mb-5 flex items-start gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300">
                        <flux:icon.clock class="size-5" variant="outline" />
                    </span>
                    <div>
                        <flux:heading size="lg">{{ __('Horario de atención para eventos') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Define los días y horarios en los que recibes eventos.') }}</flux:text>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Domain\Businesses\Models\Business::DAY_LABELS as $day => $label)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $label }}</span>
                                <flux:checkbox wire:model.live="weeklySchedule.{{ $day }}.closed" :label="__('Cerrado')" />
                            </div>

                            @if (! ($weeklySchedule[$day]['closed'] ?? true))
                                <div class="mt-3 flex items-center gap-2">
                                    <input type="time" wire:model="weeklySchedule.{{ $day }}.open" class="min-w-0 flex-1 rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                    <span class="text-xs text-zinc-400">{{ __('a') }}</span>
                                    <input type="time" wire:model="weeklySchedule.{{ $day }}.close" class="min-w-0 flex-1 rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                </div>
                            @else
                                <p class="mt-2 text-xs text-zinc-400">{{ __('No se reciben eventos este día.') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <flux:button type="submit" variant="primary" icon="check" class="mt-5" wire:loading.attr="disabled" wire:target="saveGeneral">
                    {{ __('Guardar horario') }}
                </flux:button>
            </form>

            <div class="entrepreneur-card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-zinc-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                    <div>
                        <flux:heading size="lg">{{ __('Espacios') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Muestra visualmente los salones y áreas disponibles para reservar.') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ trans_choice(':count espacio|:count espacios', $this->spaces->count(), ['count' => $this->spaces->count()]) }}</flux:badge>
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="startNewSpace">{{ __('Crear espacio') }}</flux:button>
                    </div>
                </div>

                <div class="p-6">
                    @if ($this->spaces->isNotEmpty())
                        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($this->spaces as $space)
                                <article class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-zinc-900">
                                    <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-brand-100 via-rose-50 to-amber-100 dark:from-brand-950 dark:via-zinc-900 dark:to-amber-950">
                                        @if ($space->imageUrl())
                                            <img src="{{ $space->imageUrl() }}" class="size-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $space->name }}">
                                        @else
                                            <div class="flex size-full flex-col items-center justify-center text-brand-500/70 dark:text-brand-300/60">
                                                <flux:icon.building-office-2 class="size-12" variant="outline" />
                                                <span class="mt-2 text-xs font-semibold">{{ __('Sin imagen') }}</span>
                                            </div>
                                        @endif
                                        <div class="absolute right-3 top-3 flex gap-2">
                                            <flux:button size="sm" variant="filled" icon="pencil-square" wire:click="editSpace({{ $space->id }})" aria-label="{{ __('Editar :name', ['name' => $space->name]) }}" />
                                            <flux:button size="sm" variant="filled" icon="trash" wire:click="deleteSpace({{ $space->id }})" wire:confirm="{{ __('¿Eliminar este espacio?') }}" aria-label="{{ __('Eliminar :name', ['name' => $space->name]) }}" />
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="truncate font-black text-zinc-950 dark:text-white">{{ $space->name }}</h3>
                                        <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.users class="size-4" variant="outline" />
                                            {{ $space->capacity ? __('Hasta :capacity personas', ['capacity' => $space->capacity]) : __('Capacidad por definir') }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mb-6 rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/60 px-6 py-10 text-center dark:border-zinc-700 dark:bg-white/[0.02]">
                            <span class="mx-auto inline-flex size-12 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                                <flux:icon.photo class="size-6" variant="outline" />
                            </span>
                            <h3 class="mt-3 font-bold">{{ __('Agrega tu primer espacio') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('Incluye una buena fotografía para que tus clientes puedan conocerlo.') }}</p>
                        </div>
                    @endif

                </div>
            </div>

            {{--
                Eventos públicos (Fase 5): formulario, estados
                borrador/publicado/finalizado/cancelado, "Publicar en el
                feed". `blocks_space` reutiliza el mismo motor de
                disponibilidad que las reservas privadas (Fase 3): un
                evento público que bloquea espacio impide reservas
                solapadas y viceversa.
            --}}
            <div class="entrepreneur-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-5 dark:border-white/10">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                            <flux:icon.ticket class="size-5" variant="outline" />
                        </span>
                        <div>
                            <flux:heading size="lg">{{ __('Eventos públicos') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Crea experiencias que aparecerán en la agenda pública de Merkamigo.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ trans_choice(':count evento|:count eventos', $this->publicEvents->count(), ['count' => $this->publicEvents->count()]) }}</flux:badge>
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="startNewPublicEvent">{{ __('Crear evento') }}</flux:button>
                    </div>
                </div>

                <div class="p-6">

                @unless ($this->business->isOnPaidPlan())
                    <div class="mb-4 flex items-start gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.information-circle class="mt-0.5 size-4 shrink-0" variant="outline" />
                        <span>
                            {{ __('En la agenda pública, los eventos de negocios en Emprendedor o Negocios aparecen primero dentro de un mismo día.') }}
                            <flux:link :href="route('emprendedores.negocios.plan', $this->business)" wire:navigate class="font-medium">{{ __('Ver planes') }}</flux:link>
                        </span>
                    </div>
                @endunless

                @if ($this->publicEvents->isEmpty())
                    <x-states.empty :title="__('Todavía no has creado eventos públicos')" :description="__('Crea tu primer evento para que aparezca en la agenda de Merkamigo.')" />
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->publicEvents as $event)
                            <article id="evento-{{ $event->id }}" class="scroll-mt-24 flex overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg sm:flex-col dark:border-white/10 dark:bg-zinc-900">
                                <div class="relative aspect-square w-28 shrink-0 overflow-hidden bg-gradient-to-br from-violet-100 via-rose-50 to-amber-100 sm:aspect-[16/10] sm:w-full dark:from-violet-950 dark:via-zinc-900 dark:to-amber-950">
                                    @if ($event->coverUrl())
                                        <img src="{{ $event->coverUrl() }}" class="size-full object-cover" alt="{{ $event->title }}">
                                    @else
                                        <div class="flex size-full flex-col items-center justify-center text-violet-500/70 dark:text-violet-300/60">
                                            <flux:icon.ticket class="size-10" variant="outline" />
                                            <span class="mt-2 hidden text-xs font-semibold sm:block">{{ __('Sin portada') }}</span>
                                        </div>
                                    @endif
                                    <div class="absolute left-3 top-3">
                                        <flux:badge size="sm" :color="match ($event->status) {
                                            \App\Domain\Events\Models\PublicEvent::PUBLICADO => 'green',
                                            \App\Domain\Events\Models\PublicEvent::BORRADOR => 'zinc',
                                            \App\Domain\Events\Models\PublicEvent::FINALIZADO => 'blue',
                                            \App\Domain\Events\Models\PublicEvent::CANCELADO => 'red',
                                            default => 'zinc',
                                        }">
                                            {{ match ($event->status) {
                                                \App\Domain\Events\Models\PublicEvent::PUBLICADO => __('Publicado'),
                                                \App\Domain\Events\Models\PublicEvent::BORRADOR => __('Borrador'),
                                                \App\Domain\Events\Models\PublicEvent::FINALIZADO => __('Finalizado'),
                                                \App\Domain\Events\Models\PublicEvent::CANCELADO => __('Cancelado'),
                                                default => $event->status,
                                            } }}
                                        </flux:badge>
                                    </div>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col p-4">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black text-zinc-950 dark:text-white">{{ $event->title }}</h3>
                                        <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.calendar-days class="size-4" variant="outline" />
                                            {{ $event->starts_at->translatedFormat('d M Y, g:i a') }}
                                        </p>
                                        @if ($event->categoryLabel() || $event->location_text)
                                            <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ collect([$event->categoryLabel(), $event->location_text])->filter()->join(' · ') }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-auto sm:pt-4">

                                    @if (in_array($event->status, [\App\Domain\Events\Models\PublicEvent::BORRADOR, \App\Domain\Events\Models\PublicEvent::PUBLICADO]))
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editPublicEvent({{ $event->id }})">{{ __('Editar') }}</flux:button>
                                    @endif

                                    @if ($event->status === \App\Domain\Events\Models\PublicEvent::BORRADOR)
                                        <flux:button size="sm" variant="primary" wire:click="publishPublicEvent({{ $event->id }})">{{ __('Publicar') }}</flux:button>
                                    @elseif ($event->status === \App\Domain\Events\Models\PublicEvent::PUBLICADO)
                                        @if (! $event->post)
                                            <flux:button size="sm" variant="ghost" icon="megaphone" wire:click="publishToFeed({{ $event->id }})">{{ __('Publicar en el feed') }}</flux:button>
                                        @else
                                            <flux:badge size="sm" color="zinc" icon="check">{{ __('En el feed') }}</flux:badge>
                                        @endif
                                        <flux:button size="sm" variant="ghost" wire:click="cancelPublicEvent({{ $event->id }})" wire:confirm="{{ __('¿Cancelar este evento?') }}">{{ __('Cancelar') }}</flux:button>
                                    @endif

                                    @if ($event->status === \App\Domain\Events\Models\PublicEvent::BORRADOR)
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deletePublicEvent({{ $event->id }})" wire:confirm="{{ __('¿Eliminar este evento?') }}" aria-label="{{ __('Eliminar :title', ['title' => $event->title]) }}" />
                                    @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
                </div>
            </div>
        </div>
    @endif

    <flux:modal name="fecha-bloqueada" class="w-full !max-w-lg">
        <form wire:submit="addBlockedDate" class="space-y-5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                    <flux:icon.calendar-date-range class="size-5" variant="outline" />
                </span>
                <div>
                    <flux:heading size="lg">{{ __('Agregar fecha bloqueada') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Indica un día en el que no recibirás reservas para eventos.') }}</flux:text>
                </div>
            </div>

            <flux:input type="date" wire:model="blockedDate" :label="__('Fecha')" />
            <flux:input wire:model="blockedReason" :label="__('Motivo (opcional)')" placeholder="{{ __('Cierre por mantenimiento') }}" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="lock-closed" wire:loading.attr="disabled" wire:target="addBlockedDate">
                    {{ __('Bloquear fecha') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="espacio-evento" class="w-full !max-w-xl">
        <form wire:submit="addSpace" class="space-y-5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon.building-office-2 class="size-5" variant="outline" />
                </span>
                <div>
                    <flux:heading size="lg">{{ $editingSpaceId ? __('Editar espacio') : __('Crear espacio') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">{{ $editingSpaceId ? __('Actualiza la información o reemplaza la imagen actual.') : __('Agrega un salón o área disponible para tus eventos.') }}</flux:text>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="spaceName" :label="__('Nombre del espacio')" placeholder="{{ __('Salón principal') }}" />
                <flux:input type="number" min="1" wire:model="spaceCapacity" :label="__('Capacidad (opcional)')" />
            </div>

            <flux:input type="file" wire:model="spaceImage" :label="__('Imagen del espacio (opcional)')" accept="image/jpeg,image/png,image/webp" />

            @if ($spaceImage || $currentSpaceImageUrl)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-white/10">
                    <img src="{{ $spaceImage?->temporaryUrl() ?? $currentSpaceImageUrl }}" class="aspect-[16/9] w-full object-cover" alt="{{ __('Vista previa del espacio') }}">
                    <div class="bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                        <p class="text-sm font-bold">{{ $spaceImage ? __('Nueva imagen') : __('Imagen actual') }}</p>
                        <p class="text-xs text-zinc-500">{{ $spaceImage ? __('La imagen se optimizará automáticamente.') : __('Selecciona otra imagen si quieres reemplazarla.') }}</p>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-5 py-8 text-center dark:border-zinc-700 dark:bg-white/[0.02]">
                    <flux:icon.photo class="mx-auto size-8 text-zinc-400" variant="outline" />
                    <p class="mt-2 text-sm text-zinc-500">{{ __('Usa una imagen horizontal, clara y bien iluminada.') }}</p>
                </div>
            @endif

            <div class="flex flex-wrap justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="cancelSpaceEdit">{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" variant="primary" :icon="$editingSpaceId ? 'check' : 'plus'" wire:loading.attr="disabled" wire:target="addSpace,spaceImage">
                    {{ $editingSpaceId ? __('Guardar cambios') : __('Crear espacio') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="evento-publico" class="w-full !max-w-2xl">
        <form wire:submit="savePublicEvent" class="space-y-5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                    <flux:icon.ticket class="size-5" variant="outline" />
                </span>
                <div>
                    <flux:heading size="lg">{{ $editingPublicEventId ? __('Editar evento') : __('Crear evento') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">{{ $editingPublicEventId ? __('Actualiza la información y la portada del evento.') : __('Crea una experiencia para compartirla en la agenda de Merkamigo.') }}</flux:text>
                </div>
            </div>

            <flux:input wire:model="eventTitle" :label="__('Título')" placeholder="{{ __('Noche de música y café') }}" />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select wire:model="eventCategory" :label="__('Categoría (opcional)')">
                    <flux:select.option value="">{{ __('Sin categoría') }}</flux:select.option>
                    @foreach (\App\Domain\Events\Models\PublicEvent::CATEGORIES as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="eventMunicipalityId" :label="__('Municipio')">
                    <flux:select.option value="">{{ __('Sin definir') }}</flux:select.option>
                    @foreach ($this->municipalities as $municipality)
                        <flux:select.option value="{{ $municipality->id }}">{{ $municipality->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="eventDescription" :label="__('Descripción (opcional)')" rows="3" />

            <flux:input type="file" wire:model="eventCover" :label="__('Imagen de portada (opcional)')" accept="image/*" />
            @if ($eventCover || $currentEventCoverUrl)
                <img src="{{ $eventCover?->temporaryUrl() ?? $currentEventCoverUrl }}" class="aspect-[16/7] w-full rounded-2xl object-cover" alt="{{ __('Vista previa del evento') }}">
            @endif

            <flux:input wire:model="eventLocationText" :label="__('Ubicación (opcional)')" placeholder="{{ __('Cra 7 # 4-28, Zipaquirá') }}" />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input type="datetime-local" wire:model="eventStartsAt" :label="__('Inicio')" />
                <flux:input type="datetime-local" wire:model="eventEndsAt" :label="__('Fin (opcional)')" />
            </div>

            <flux:input type="number" min="1" wire:model="eventCapacity" :label="__('Capacidad (opcional)')" class="sm:max-w-xs" />

            <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                <flux:checkbox wire:model.live="eventBlocksSpace" :label="__('Este evento ocupa mi espacio de eventos')" />
                <flux:text class="mt-1 text-xs text-zinc-500">{{ __('Actívalo si este evento bloquea reservas privadas en ese horario. Una publicación solo informativa no necesita esto.') }}</flux:text>

                @if ($eventBlocksSpace)
                    <flux:select wire:model="eventSpaceId" :label="__('Espacio')" class="mt-3">
                        <flux:select.option value="">{{ __('Selecciona un espacio') }}</flux:select.option>
                        @foreach ($this->spaces as $space)
                            <flux:select.option value="{{ $space->id }}">{{ $space->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :icon="$editingPublicEventId ? 'check' : 'plus'" wire:loading.attr="disabled" wire:target="savePublicEvent,eventCover">
                    {{ $editingPublicEventId ? __('Guardar cambios') : __('Crear evento') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Reservas --}}
    @if ($activeTab === 'reservas')
        <div>
            @if ($this->reservations->isEmpty())
                <x-states.empty
                    :title="__('Todavía no hay reservas')"
                    :description="__('Cuando un cliente reserve y pague tu espacio desde tu vitrina, su reserva aparecerá aquí.')"
                />
            @else
                <div class="space-y-3">
                    @foreach ($this->reservations as $reservation)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $reservation->prospect_name }}</p>
                                    <p class="text-sm text-zinc-500">{{ $reservation->starts_at->translatedFormat('d M Y, g:i a') }} · {{ __(':count personas', ['count' => $reservation->party_size]) }}</p>
                                </div>
                                <flux:badge size="sm" :color="match ($reservation->status) {
                                    EventReservation::CONFIRMADA => 'green',
                                    EventReservation::PENDIENTE_PAGO => 'amber',
                                    EventReservation::PAGO_FALLIDO, EventReservation::VENCIDA, EventReservation::CANCELADA => 'red',
                                    default => 'zinc',
                                }">
                                    {{ $reservation->status }}
                                </flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $this->reservations->links() }}
                </div>
            @endif
        </div>
    @endif

    {{--
        Check-in (pedido del usuario, 2026-10-08): leer el QR de una
        reserva de CUPO para verificar la asistencia en la puerta, y ver
        las métricas de eventos ("cantidad de reservas, cantidad de
        pagos realizados en línea, cantidad de personas que asistieron").
    --}}
    @if ($activeTab === 'checkin')
        <div class="space-y-5">
            <div class="grid grid-cols-3 gap-3">
                <div class="entrepreneur-card p-4 text-center">
                    <p class="text-xs text-zinc-500">{{ __('Reservas') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->attendanceMetrics['reservations'] }}</p>
                </div>
                <div class="entrepreneur-card p-4 text-center">
                    <p class="text-xs text-zinc-500">{{ __('Pagos en línea') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->attendanceMetrics['onlinePayments'] }}</p>
                </div>
                <div class="entrepreneur-card p-4 text-center">
                    <p class="text-xs text-zinc-500">{{ __('Asistieron') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->attendanceMetrics['attended'] }}</p>
                </div>
            </div>

            <div class="entrepreneur-card p-5">
                @if (! $checkinResult)
                    <flux:heading size="lg" class="mb-1">{{ __('Verificar entrada') }}</flux:heading>
                    <flux:text class="mb-4 text-sm text-zinc-500">{{ __('Escanea o ingresa el código del QR de la entrada del asistente. Sin lector de cámara por ahora: un lector físico conectado como teclado también funciona.') }}</flux:text>

                    <form wire:submit="lookupCheckin" class="flex gap-2">
                        <flux:input wire:model="checkinInput" placeholder="evt_..." class="flex-1" />
                        <flux:button type="submit" variant="primary">{{ __('Buscar') }}</flux:button>
                    </form>
                @else
                    @php($attendance = \App\Domain\Events\Models\EventAttendance::with('publicEvent')->find($checkinResult['attendance_id']))
                    @if ($attendance && $attendance->isCheckedIn())
                        <flux:badge color="green" class="mb-3">{{ __('Ingreso ya registrado') }}</flux:badge>
                        <flux:heading size="lg" class="mb-1">{{ $attendance->attendee_name }}</flux:heading>
                        <flux:text class="mb-4 text-sm text-zinc-500">
                            {{ $attendance->publicEvent->title }} · {{ __(':count cupo(s)', ['count' => $attendance->quantity]) }} · {{ $attendance->checked_in_at->translatedFormat('d M Y, g:i a') }}
                        </flux:text>
                        <flux:button variant="ghost" wire:click="cancelCheckin">{{ __('Volver') }}</flux:button>
                    @elseif ($attendance)
                        <flux:badge color="blue" class="mb-3">{{ __('Reserva identificada') }}</flux:badge>
                        <flux:heading size="lg" class="mb-1">{{ $attendance->attendee_name }}</flux:heading>
                        <flux:text class="mb-4 text-sm text-zinc-500">
                            {{ $attendance->publicEvent->title }} · {{ __(':count cupo(s)', ['count' => $attendance->quantity]) }}
                        </flux:text>

                        <div class="flex gap-2">
                            <flux:button variant="ghost" wire:click="cancelCheckin">{{ __('Cancelar') }}</flux:button>
                            <flux:button variant="primary" class="flex-1" wire:click="confirmCheckin" wire:loading.attr="disabled" wire:target="confirmCheckin">
                                {{ __('Registrar entrada') }}
                            </flux:button>
                        </div>
                    @else
                        <x-states.empty :title="__('Esta reserva ya no está disponible')" :description="__('Puede que ya se haya cancelado o no exista.')" />
                        <flux:button variant="ghost" class="mt-4" wire:click="cancelCheckin">{{ __('Volver') }}</flux:button>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Configuración --}}
    @if ($activeTab === 'configuracion')
        <div class="space-y-6">
            <nav class="flex gap-1 overflow-x-auto" aria-label="{{ __('Secciones de configuración') }}">
                @foreach (['general' => __('General'), 'tarifas' => __('Tarifas y menú'), 'equipos' => __('Equipos')] as $tab => $label)
                    <button
                        type="button"
                        wire:click="setConfigTab('{{ $tab }}')"
                        @class([
                            'shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition',
                            'bg-brand-600 text-white' => $configTab === $tab,
                            'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => $configTab !== $tab,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>

            {{-- General --}}
            @if ($configTab === 'general')
                <form wire:submit="saveGeneral" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:switch wire:model="enabled" :label="__('Ofrecer eventos')" :description="__('Muestra la opción de reservar eventos en tu perfil público.')" />
                        </div>
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:switch wire:model="publicEventsEnabled" :label="__('Publicar eventos abiertos')" :description="__('Permite que cualquier persona vea tus eventos en la agenda pública.')" />
                        </div>
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:switch wire:model="privateReservationsEnabled" :label="__('Aceptar reservas privadas')" :description="__('Recibe solicitudes para eventos privados, con pago en línea.')" />
                        </div>
                    </div>

                    @unless ($this->business->hasWompiConnected())
                        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                            <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0" variant="outline" />
                            <div>
                                {{ __('No tienes tu cuenta Wompi conectada. No podrás activar "Aceptar reservas privadas" (necesita pago verificado) hasta que la conectes en') }}
                                <flux:link :href="route('emprendedores.negocios.cobros-en-linea', $this->business)" wire:navigate>{{ __('Cobros en línea') }}</flux:link>.
                            </div>
                        </div>
                    @endunless

                    <div class="grid gap-3 sm:grid-cols-3">
                        <flux:input type="number" min="1" wire:model="maxCapacity" :label="__('Capacidad máxima')" placeholder="30" />
                        <flux:input type="number" min="1" wire:model="maxDurationHours" :label="__('Duración máxima (horas)')" />
                        <flux:input type="number" min="0" wire:model="minAdvanceHours" :label="__('Anticipación mínima (horas)')" />
                    </div>

                    <flux:input type="number" min="5" wire:model="holdMinutes" :label="__('Minutos de retención de una franja mientras se paga')" class="sm:max-w-xs" />

                    <flux:textarea wire:model="policyText" :label="__('Condiciones visibles para el cliente (opcional)')" rows="3" />
                    <flux:textarea wire:model="cancellationText" :label="__('Política de cancelación visible para el cliente (opcional)')" rows="3" />

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveGeneral">
                        {{ __('Guardar configuración') }}
                    </flux:button>
                </form>
            @endif

            {{-- Tarifas y menú --}}
            @if ($configTab === 'tarifas')
                <div class="space-y-6">
                    <form wire:submit="saveRates" class="space-y-4">
                        <flux:heading size="lg" class="mb-1">{{ __('Modelo de tarifas') }}</flux:heading>
                        <flux:text class="mb-2 text-sm text-zinc-500">{{ __('Elige cómo quieres cobrar tus eventos.') }}</flux:text>

                        <flux:radio.group wire:model="pricingMode" variant="cards" class="grid gap-3 sm:grid-cols-3">
                            <flux:radio value="platos" icon="cake" :label="__('Por platos')" :description="__('Cobras únicamente por el consumo de platos del evento.')" />
                            <flux:radio value="horas" icon="clock" :label="__('Por horas')" :description="__('Cobras un valor fijo por el tiempo de duración del evento.')" />
                            <flux:radio value="hibrido" icon="sparkles" :label="__('Híbrido')" :description="__('Cobras por horas y además por el consumo de platos.')" />
                        </flux:radio.group>

                        @if ($pricingMode !== 'platos')
                            <flux:input type="number" min="0" wire:model="hourlyRateCop" :label="__('Tarifa por hora (COP)')" placeholder="80000" class="sm:max-w-xs" />
                        @endif

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveRates">
                            {{ __('Guardar tarifas') }}
                        </flux:button>
                    </form>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:heading size="lg" class="mb-1">{{ __('Menú del evento') }}</flux:heading>
                        <flux:text class="mb-4 text-sm text-zinc-500">{{ __('Agrega los platos disponibles para tus eventos y define sus precios.') }}</flux:text>

                        @if ($this->dishes->isNotEmpty())
                            <div class="mb-4 space-y-2">
                                @foreach ($this->dishes as $dish)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $dish->name }}</p>
                                            <p class="text-xs text-zinc-500">${{ number_format($dish->price_cents / 100, 0, ',', '.') }}{{ $dish->description ? ' · '.$dish->description : '' }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <flux:switch wire:click="toggleDishAvailability({{ $dish->id }})" :checked="$dish->is_available" :aria-label="__('Disponible: :name', ['name' => $dish->name])" />
                                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteDish({{ $dish->id }})" wire:confirm="{{ __('¿Eliminar este plato?') }}" aria-label="{{ __('Eliminar :name', ['name' => $dish->name]) }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form wire:submit="addDish" class="flex flex-wrap items-end gap-3">
                            <flux:input wire:model="dishName" :label="__('Plato')" placeholder="{{ __('Crepes') }}" class="min-w-40 flex-1" />
                            <flux:input wire:model="dishDescription" :label="__('Descripción (opcional)')" placeholder="{{ __('Rellenos de chocolate') }}" class="min-w-40 flex-1" />
                            <flux:input type="number" min="0" wire:model="dishPriceCop" :label="__('Precio (COP)')" placeholder="25000" class="w-36" />
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="addDish">{{ __('Agregar plato') }}</flux:button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Equipos --}}
            @if ($configTab === 'equipos')
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-1">{{ __('Equipos para el evento') }}</flux:heading>
                    <flux:text class="mb-4 text-sm text-zinc-500">{{ __('Selecciona los equipos que ofreces. El catálogo global lo administra la plataforma; también puedes agregar equipos propios.') }}</flux:text>

                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($this->equipmentTypes as $type)
                            @php($selected = $this->selectedEquipment->firstWhere('event_equipment_type_id', $type->id))
                            <button
                                type="button"
                                wire:click="toggleGlobalEquipment({{ $type->id }})"
                                @class([
                                    'flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition',
                                    'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $selected,
                                    'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' => ! $selected,
                                ])
                            >
                                @if ($type->icon)
                                    <flux:icon :name="$type->icon" class="size-4" variant="outline" />
                                @endif
                                {{ $type->name }}
                            </button>
                        @endforeach
                    </div>

                    @php($customEquipment = $this->selectedEquipment->whereNull('event_equipment_type_id'))
                    @if ($customEquipment->isNotEmpty())
                        <div class="mb-4 space-y-2">
                            @foreach ($customEquipment as $equipment)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $equipment->custom_name }}</p>
                                        @if ($equipment->fee_cents)
                                            <p class="text-xs text-zinc-500">${{ number_format($equipment->fee_cents / 100, 0, ',', '.') }}</p>
                                        @endif
                                    </div>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeEquipment({{ $equipment->id }})" aria-label="{{ __('Eliminar :name', ['name' => $equipment->custom_name]) }}" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit="addCustomEquipment" class="flex flex-wrap items-end gap-3">
                        <flux:input wire:model="customEquipmentName" :label="__('Nombre del equipo propio')" placeholder="{{ __('Máquina de humo') }}" class="min-w-48 flex-1" />
                        <flux:input type="number" min="0" wire:model="customEquipmentFeeCop" :label="__('Cargo (COP, opcional)')" placeholder="50000" class="w-40" />
                        <flux:button type="submit" variant="ghost" wire:loading.attr="disabled" wire:target="addCustomEquipment">{{ __('Añadir equipo propio') }}</flux:button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</section>
