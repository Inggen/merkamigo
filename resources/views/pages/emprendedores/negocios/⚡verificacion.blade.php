<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Actions\RequestBusinessVerification;
use App\Domain\Trust\Models\BusinessVerification;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Solicitud de verificación del negocio (3.1 del TODO: "pasaporte de
 * confianza"). El flujo de revisión (aprobar/pedir ajustes/vencer/revocar)
 * ya existe en Filament (`ReviewBusinessVerification`) — esta página es
 * únicamente el lado del negocio: solicitar y ver el estado.
 */
new #[Title('Verificación de tu negocio')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $legal_name = '';
    public string $contact_name = '';
    public string $contact_document_type = 'CC';
    public ?string $contact_document_number = '';
    public ?string $request_note = '';
    public $document;

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

        $verification = $business->currentVerification();

        if ($verification) {
            $this->legal_name = $verification->legal_name ?? '';
            $this->contact_name = $verification->contact_name ?? '';
            $this->contact_document_type = $verification->contact_document_type ?? 'CC';
            $this->contact_document_number = $verification->contact_document_number;
            $this->request_note = $verification->request_note;
        }
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function verification(): ?BusinessVerification
    {
        return $this->business->currentVerification();
    }

    /**
     * Puede solicitar (o volver a solicitar) mientras no haya una solicitud
     * activa en revisión o ya verificada vigente.
     */
    #[Computed]
    public function canRequest(): bool
    {
        $verification = $this->verification;

        if (! $verification) {
            return true;
        }

        return in_array($verification->status, [
            BusinessVerification::REQUIERE_AJUSTES,
            BusinessVerification::VENCIDA,
            BusinessVerification::REVOCADA,
        ], true);
    }

    public function submit(): void
    {
        $this->authorize('update', $this->business);

        $data = $this->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_document_type' => ['required', 'in:CC,CE,NIT,Pasaporte'],
            'contact_document_number' => ['required', 'string', 'max:50'],
            'request_note' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        app(RequestBusinessVerification::class)->handle(
            $this->business,
            Auth::user(),
            $data,
            $this->document,
        );

        $this->reset('document');
        unset($this->verification, $this->canRequest);

        Flux::toast(variant: 'success', text: __('Solicitud de verificación enviada.'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Verificación de tu negocio') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Una insignia de verificación básica ayuda a que los clientes confíen más en tu negocio. No implica garantía de calidad, pago ni entrega.') }}
        </flux:text>
    </div>

    @if ($this->verification)
        <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:subheading>{{ __('Estado actual') }}</flux:subheading>
                <flux:badge :color="match ($this->verification->status) {
                    \App\Domain\Trust\Models\BusinessVerification::VERIFICADA => 'green',
                    \App\Domain\Trust\Models\BusinessVerification::EN_REVISION => 'amber',
                    \App\Domain\Trust\Models\BusinessVerification::REQUIERE_AJUSTES => 'amber',
                    \App\Domain\Trust\Models\BusinessVerification::REVOCADA, \App\Domain\Trust\Models\BusinessVerification::VENCIDA => 'red',
                    default => 'zinc',
                }">
                    {{ match ($this->verification->status) {
                        \App\Domain\Trust\Models\BusinessVerification::VERIFICADA => __('Verificada'),
                        \App\Domain\Trust\Models\BusinessVerification::EN_REVISION => __('En revisión'),
                        \App\Domain\Trust\Models\BusinessVerification::REQUIERE_AJUSTES => __('Requiere ajustes'),
                        \App\Domain\Trust\Models\BusinessVerification::REVOCADA => __('Revocada'),
                        \App\Domain\Trust\Models\BusinessVerification::VENCIDA => __('Vencida'),
                        default => __('Sin iniciar'),
                    } }}
                </flux:badge>
            </div>

            @if ($this->verification->review_note)
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Nota del equipo de Merkamigo: :nota', ['nota' => $this->verification->review_note]) }}
                </flux:text>
            @endif

            @if ($this->verification->expires_at)
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Vigente hasta: :fecha', ['fecha' => $this->verification->expires_at->format('d/m/Y')]) }}
                </flux:text>
            @endif

            @if ($this->verification->verification_document_path)
                <div class="mt-3">
                    <flux:link :href="route('emprendedores.negocios.verificacion.documento', $this->business)" target="_blank" class="text-sm">
                        {{ __('Ver documento enviado') }}
                    </flux:link>
                </div>
            @endif
        </div>
    @endif

    @if ($this->canRequest)
        <form wire:submit="submit" class="space-y-6">
            <flux:input wire:model="legal_name" :label="__('Razón social')" required />
            <flux:input wire:model="contact_name" :label="__('Nombre del responsable')" required />

            <flux:select wire:model="contact_document_type" :label="__('Tipo de documento del responsable')">
                <flux:select.option value="CC">{{ __('Cédula de ciudadanía') }}</flux:select.option>
                <flux:select.option value="CE">{{ __('Cédula de extranjería') }}</flux:select.option>
                <flux:select.option value="NIT">{{ __('NIT') }}</flux:select.option>
                <flux:select.option value="Pasaporte">{{ __('Pasaporte') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="contact_document_number" :label="__('Número de documento')" required />

            <div class="space-y-3" x-data="{ fileName: '' }">
                <div class="space-y-1">
                    <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">
                        {{ __('Documento de respaldo (opcional, ej. cámara de comercio o cédula)') }}
                    </flux:text>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Adjunta JPG, PNG o PDF de hasta 8 MB.') }}
                    </flux:text>
                </div>

                <label class="block cursor-pointer rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/90 p-4 transition hover:border-brand-400 hover:bg-white dark:border-zinc-700 dark:bg-zinc-900/60 dark:hover:border-brand-500">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                <flux:icon.document-arrow-up class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ __('Adjuntar documento') }}
                                </div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400" x-text="fileName || '{{ __('Se guarda de forma privada. Solo tu equipo y el equipo de Merkamigo pueden verlo.') }}'"></div>
                            </div>
                        </div>

                        <span class="inline-flex shrink-0 items-center rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm">
                            {{ __('Seleccionar archivo') }}
                        </span>
                    </div>

                    <input
                        type="file"
                        wire:model="document"
                        accept="image/*,.pdf"
                        class="sr-only"
                        x-on:change="fileName = $event.target.files[0]?.name ?? ''"
                    >
                </label>

                @error('document')
                    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            </div>

            <flux:textarea wire:model="request_note" :label="__('Nota adicional (opcional)')" rows="3" />

            <flux:button type="submit" variant="primary">{{ __('Enviar solicitud') }}</flux:button>
        </form>
    @else
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Tu solicitud ya está en revisión o tu negocio ya está verificado. Te avisaremos si necesitamos algo más.') }}
        </flux:text>
    @endif
</section>
