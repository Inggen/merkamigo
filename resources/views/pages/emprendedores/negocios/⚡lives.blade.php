<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\CalculateLiveControlMetrics;
use App\Domain\Social\Actions\ManageLiveStream;
use App\Domain\Social\Actions\InspectLiveSignal;
use App\Domain\Social\Actions\GenerateSocialSalesCopy;
use App\Domain\Social\Models\LiveStream;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('En vivo')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $title = '';

    public string $description = '';

    public string $scheduled_at = '';

    public $cover = null;

    /** @var array<int, int|string> */
    public array $product_ids = [];

    public string $replay_url = '';

    public ?int $destination_stream_id = null;

    public string $destination_provider = 'youtube';

    public string $destination_name = '';

    public string $destination_rtmp_url = '';

    public string $destination_stream_key = '';

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
        return $this->business->products()->where('status', 'publicado')->orderBy('name')->get();
    }

    #[Computed]
    public function streams()
    {
        return $this->business->liveStreams()->with(['products', 'pinnedProduct', 'streamingDestinations'])->latest()->get();
    }

    #[Computed]
    public function destinationNamePlaceholder(): string
    {
        return match ($this->destination_provider) {
            'youtube' => __('Ej. YouTube de mi negocio'),
            'facebook' => __('Ej. Facebook de mi negocio'),
            'instagram' => __('Ej. Instagram de mi negocio'),
            'tiktok' => __('Ej. TikTok de mi negocio'),
            default => __('Ej. Canal principal'),
        };
    }

    public function create(): void
    {
        $this->authorize('update', $this->business);

        try {
            app(ManageLiveStream::class)->create($this->business, [
                'title' => $this->title,
                'description' => $this->description,
                'scheduled_at' => $this->scheduled_at ?: null,
                'cover' => $this->cover,
                'product_ids' => array_map('intval', $this->product_ids),
            ], Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->reset(['title', 'description', 'scheduled_at', 'cover', 'product_ids']);
        unset($this->streams);
        Flux::modal('create-live')->close();
        Flux::toast(variant: 'success', text: __('Transmisión preparada. Revísala antes de iniciar.'));
    }

    public function regenerateStreamKey(int $streamId): void
    {
        app(ManageLiveStream::class)->regenerateStreamKey($this->ownedStream($streamId), Auth::user());
        unset($this->streams);
        Flux::toast(variant: 'success', text: __('La clave de transmisión fue regenerada.'));
    }

    public function openDestination(int $streamId): void
    {
        $stream = $this->ownedStream($streamId);
        abort_if($stream->status === LiveStream::FINALIZADO, 422);

        $this->reset(['destination_name', 'destination_rtmp_url', 'destination_stream_key']);
        $this->destination_stream_id = $stream->id;
        $this->destination_provider = 'youtube';
        $this->resetValidation();
        Flux::modal('live-destination')->show();
    }

    public function saveDestination(): void
    {
        $stream = $this->ownedStream((int) $this->destination_stream_id);
        abort_if($stream->status === LiveStream::FINALIZADO, 422);

        $validated = $this->validate([
            'destination_provider' => ['required', 'in:youtube,facebook,instagram,tiktok,custom'],
            'destination_name' => ['required', 'string', 'max:80'],
            'destination_rtmp_url' => ['required', 'string', 'max:2000', 'regex:~^rtmps?://~i'],
            'destination_stream_key' => ['required', 'string', 'max:500'],
        ]);

        $stream->streamingDestinations()->create([
            'provider' => $validated['destination_provider'],
            'name' => $validated['destination_name'],
            'rtmp_url' => $validated['destination_rtmp_url'],
            'stream_key' => $validated['destination_stream_key'],
            'is_enabled' => true,
        ]);

        $this->reset(['destination_stream_id', 'destination_name', 'destination_rtmp_url', 'destination_stream_key']);
        unset($this->streams);
        Flux::modal('live-destination')->close();
        Flux::toast(variant: 'success', text: __('Destino guardado de forma cifrada.'));
    }

    public function toggleDestination(int $streamId, int $destinationId): void
    {
        $destination = $this->ownedStream($streamId)->streamingDestinations()->findOrFail($destinationId);
        $enabled = ! $destination->is_enabled;
        $destination->update([
            'is_enabled' => $enabled,
            'status' => 'disconnected',
            'last_error' => $enabled ? $destination->last_error : null,
        ]);
        unset($this->streams);
    }

    public function deleteDestination(int $streamId, int $destinationId): void
    {
        $this->ownedStream($streamId)->streamingDestinations()->findOrFail($destinationId)->delete();
        unset($this->streams);
    }

    public function syncSignals(): void
    {
        foreach ($this->business->liveStreams()->whereIn('status', [LiveStream::BORRADOR, LiveStream::EN_VIVO])->get() as $stream) {
            $online = app(InspectLiveSignal::class)->handle($stream);

            if ($online && $stream->status === LiveStream::BORRADOR) {
                app(ManageLiveStream::class)->start($stream, Auth::user());
                continue;
            }

            $status = $online ? 'online' : ($stream->isLive() ? 'reconnecting' : 'offline');

            if ($stream->signal_status !== $status) {
                $stream->update(['signal_status' => $status]);
            }
        }

        unset($this->streams);
    }

    public function suggestWithAi(): void
    {
        $this->authorize('update', $this->business);

        $suggestion = app(GenerateSocialSalesCopy::class)->handle($this->business, 'live', [
            'text' => $this->description,
            'product_ids' => $this->product_ids,
        ]);

        if (! $suggestion) {
            Flux::toast(variant: 'warning', text: __('No fue posible generar una sugerencia.'));

            return;
        }

        $this->description = $suggestion;
        Flux::toast(text: __('Borrador generado. Revísalo antes de iniciar el Live.'));
    }

    public function start(int $streamId): void
    {
        $stream = $this->ownedStream($streamId);
        app(ManageLiveStream::class)->start($stream, Auth::user());
        unset($this->streams);
        Flux::toast(variant: 'success', text: __('¡Ya estás en vivo! Tus seguidores fueron notificados.'));
    }

    public function pinProduct(int $streamId, string $productId): void
    {
        $stream = $this->ownedStream($streamId);
        app(ManageLiveStream::class)->pinProduct($stream, $productId === '' ? null : (int) $productId, Auth::user());
        unset($this->streams);
    }

    public function end(int $streamId): void
    {
        $stream = $this->ownedStream($streamId);

        try {
            app(ManageLiveStream::class)->end($stream, $this->replay_url ?: null, Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->reset('replay_url');
        unset($this->streams);
        Flux::toast(variant: 'success', text: __('Transmisión finalizada.'));
    }

    private function ownedStream(int $streamId): LiveStream
    {
        $this->authorize('update', $this->business);

        return $this->business->liveStreams()->findOrFail($streamId);
    }
}; ?>

<section wire:poll.5s="syncSignals" class="w-full space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-3xl !font-bold !tracking-tight">{{ __('Live Commerce') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Transmite directamente desde Merkamigo, muestra productos y vende mientras conversas con tu comunidad.') }}</flux:subheading>
        </div>
        <flux:modal.trigger name="create-live">
            <flux:button variant="primary" icon="plus">{{ __('Preparar transmisión') }}</flux:button>
        </flux:modal.trigger>
    </header>

    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
        {{ __('Merkamigo recibe y reproduce tu señal. YouTube, Facebook, Instagram y TikTok se configuran únicamente como destinos opcionales de salida.') }}
    </div>

    <flux:modal name="create-live" class="max-h-[92vh] w-full !max-w-6xl overflow-y-auto">
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Preparar transmisión') }}</flux:heading>
            <flux:subheading>{{ __('Configura tu Live y revisa cómo lo verá tu comunidad.') }}</flux:subheading>
        </div>
        <form wire:submit="create" class="grid overflow-hidden rounded-2xl border border-zinc-200 lg:grid-cols-2 dark:border-zinc-700">
            <div class="space-y-5 p-5 lg:border-e lg:border-zinc-200 dark:lg:border-zinc-700">
                <div class="flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">1</span><h2 class="font-semibold">{{ __('Datos de la transmisión') }}</h2></div>

        <flux:input wire:model.live.debounce.300ms="title" :label="__('Título')" maxlength="120" required />
        <flux:textarea wire:model.live.debounce.300ms="description" :label="__('Descripción (opcional)')" rows="3" maxlength="1000" />
        <flux:input wire:model.live="scheduled_at" type="datetime-local" :label="__('Fecha y hora (opcional)')" />
        <div>
            <flux:label>{{ __('Imagen de portada (opcional)') }}</flux:label>
            <p class="mt-1 text-xs text-zinc-500">{{ __('Se mostrará antes y durante el Live, y al compartir el enlace. Recomendado: 1200 × 630 px.') }}</p>
            <label class="mt-2 flex min-h-36 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 text-center hover:border-brand-400 dark:border-zinc-700 dark:bg-zinc-900">
                @if ($cover)
                    <img src="{{ $cover->temporaryUrl() }}" class="h-48 w-full object-cover" alt="{{ __('Vista previa de la portada') }}">
                @else
                    <span class="flex flex-col items-center gap-2 p-5"><flux:icon.photo class="size-10 text-zinc-400" /><span class="text-sm font-medium">{{ __('Seleccionar imagen') }}</span><span class="text-xs text-zinc-500">JPG, PNG o WEBP · máximo 5 MB</span></span>
                @endif
                <input type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            @error('cover')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
            <flux:button type="button" size="sm" variant="ghost" icon="sparkles" wire:click="suggestWithAi" wire:loading.attr="disabled">
                {{ __('Sugerir descripción con IA') }}
            </flux:button>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
            <p class="font-semibold">{{ __('Señal propia de Merkamigo') }}</p>
            <p class="mt-1">{{ __('Al guardar crearemos un estudio para cámara y micrófono, más una URL y una clave privadas para OBS.') }}</p>
        </div>

        <div>
            <flux:label>{{ __('Productos que mostrarás') }}</flux:label>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @forelse ($this->products as $product)
                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <input type="checkbox" wire:model.live="product_ids" value="{{ $product->id }}" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                        <span class="min-w-0 flex-1 truncate">{{ $product->name }}</span>
                        @if ($product->price)
                            <span class="shrink-0 font-medium">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                        @endif
                    </label>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('Publica al menos un producto antes de preparar un Live.') }}</flux:text>
                @endforelse
            </div>
            @error('product_ids') <flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text> @enderror
        </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="bookmark" :disabled="$this->products->isEmpty()">{{ __('Guardar borrador') }}</flux:button>
                </div>
            </div>
            <div class="bg-zinc-50 p-5 dark:bg-zinc-900/70">
                <div class="mb-4 flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">2</span><div><h2 class="font-semibold">{{ __('Vista previa') }}</h2><p class="text-xs text-zinc-500">{{ __('Así se presentará tu Live.') }}</p></div></div>
                <div class="mx-auto w-full max-w-lg overflow-hidden rounded-2xl bg-zinc-950 text-white shadow-2xl">
                    <div class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-800 to-black">
                        @if ($cover)<img src="{{ $cover->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" alt="{{ __('Portada del Live') }}">@else<flux:icon.video-camera class="size-16 text-white/25" />@endif
                        <span class="absolute left-3 top-3 rounded-md bg-red-600 px-2 py-1 text-[10px] font-bold uppercase">{{ __('Vista previa') }}</span>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-4 pt-20"><h3 class="text-lg font-bold">{{ $title ?: __('Título de tu transmisión') }}</h3><p class="mt-1 line-clamp-2 text-sm text-white/75">{{ $description ?: __('Tu descripción aparecerá aquí…') }}</p></div>
                    </div>
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $this->business->name }}</p>
                            <p class="truncate text-xs text-white/60">{{ __('Transmitido por Merkamigo Live') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold">{{ count($product_ids) }} {{ __('productos') }}</span>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>

    <section class="entrepreneur-card space-y-4 p-4 sm:p-5">
        <div class="flex items-center gap-2.5"><h2 class="text-lg font-semibold">{{ __('Tus transmisiones') }}</h2><span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ $this->streams->count() }}</span></div>

        @forelse ($this->streams as $stream)
            @php
                $controlMetrics = ($stream->isLive() || $stream->isReplayAvailable())
                    ? app(CalculateLiveControlMetrics::class)->handle($stream)
                    : null;
                $duration = $controlMetrics
                    ? sprintf('%02d:%02d:%02d', intdiv($controlMetrics['duration_seconds'], 3600), intdiv($controlMetrics['duration_seconds'] % 3600, 60), $controlMetrics['duration_seconds'] % 60)
                    : null;
            @endphp
            <article class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ $stream->title }}</flux:heading>
                            <flux:badge :color="$stream->isLive() ? 'red' : ($stream->status === 'finalizado' ? 'zinc' : 'amber')">
                                {{ $stream->isLive() ? __('En vivo') : ($stream->status === 'finalizado' ? __('Finalizado') : __('Borrador')) }}
                            </flux:badge>
                        </div>
                        <flux:text class="text-sm text-zinc-500">{{ trans_choice(':count producto|:count productos', $stream->products->count(), ['count' => $stream->products->count()]) }}</flux:text>
                        <div class="mt-2 flex items-center gap-2"><span class="size-2 rounded-full {{ $stream->signal_status === 'online' ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span><span class="text-xs text-zinc-500">{{ $stream->signal_status === 'online' ? __('Señal conectada') : __('Esperando señal') }}</span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:modal.trigger name="preview-live-{{ $stream->id }}">
                            <flux:button size="sm" variant="ghost" icon="eye">{{ __('Vista previa') }}</flux:button>
                        </flux:modal.trigger>
                    @if ($stream->status !== 'borrador' || $stream->scheduled_at)
                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('live.show', $stream)" target="_blank">
                            {{ $stream->isLive() ? __('Ver Live') : ($stream->status === 'borrador' ? __('Ver página programada') : __('Ver replay')) }}
                        </flux:button>
                        <flux:button type="button" size="sm" variant="ghost" icon="document-duplicate" x-data x-on:click="navigator.clipboard.writeText(@js(route('live.show', $stream))); $flux.toast(@js(__('Enlace del Live copiado')))" :aria-label="__('Copiar enlace del Live')" />
                    @endif
                    </div>
                </div>

                @if ($stream->status !== 'finalizado')
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><p class="text-sm font-semibold">{{ __('Salidas a redes sociales') }}</p><p class="text-xs text-zinc-500">{{ __('Merkamigo conserva la señal principal y envía una copia a cada destino habilitado.') }}</p></div>
                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="openDestination({{ $stream->id }})">{{ __('Agregar destino') }}</flux:button>
                        </div>
                        @if ($stream->streamingDestinations->isNotEmpty())
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($stream->streamingDestinations as $destination)
                                    <div class="flex items-center gap-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800">
                                        <x-live.provider-icon :provider="$destination->provider" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium">{{ $destination->name }}</p>
                                            <p class="text-xs {{ $destination->status === 'transmitting' ? 'text-emerald-600' : ($destination->status === 'error' ? 'text-red-600' : 'text-zinc-500') }}">{{ $destination->is_enabled ? __('Habilitado · :status', ['status' => $destination->statusLabel()]) : __('Desactivado') }}</p>
                                            @if ($destination->is_enabled && $destination->last_error)<p class="mt-1 line-clamp-2 text-[11px] text-red-600" title="{{ $destination->last_error }}">{{ $destination->last_error }}</p>@endif
                                        </div>
                                        <flux:button size="sm" variant="ghost" :icon="$destination->is_enabled ? 'pause' : 'play'" wire:click="toggleDestination({{ $stream->id }}, {{ $destination->id }})" :aria-label="$destination->is_enabled ? __('Desactivar') : __('Activar')" />
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteDestination({{ $stream->id }}, {{ $destination->id }})" wire:confirm="{{ __('¿Eliminar este destino?') }}" :aria-label="__('Eliminar')" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($stream->status === 'borrador')
                    <div class="space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><p class="text-sm font-semibold">{{ __('Transmite desde Merkamigo') }}</p><p class="text-xs text-zinc-500">{{ __('Revisa tu cámara y micrófono antes de salir en vivo.') }}</p></div>
                            <flux:button size="sm" variant="primary" icon="video-camera" :href="route('emprendedores.negocios.lives.studio', [$this->business, $stream])">{{ __('Abrir estudio') }}</flux:button>
                        </div>
                        <details x-data="{ reveal: false }" class="group rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <summary class="cursor-pointer text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Transmitir con OBS (avanzado)') }}</summary>
                            <div class="mt-4 space-y-3">
                                <div><p class="text-xs font-medium text-zinc-500">{{ __('Servidor para OBS') }}</p><code class="mt-1 block truncate rounded bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-800">{{ $stream->rtmpIngestUrl() }}</code></div>
                                <div><p class="text-xs font-medium text-zinc-500">{{ __('Clave de transmisión privada') }}</p><code class="mt-1 block truncate rounded bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-800" x-text="reveal ? @js($stream->obsStreamKey()) : '••••••••••••••••'"></code></div>
                                <div class="flex flex-wrap gap-2"><flux:button type="button" size="sm" variant="ghost" icon="eye" x-on:click="reveal = !reveal" x-bind:aria-label="reveal ? @js(__('Ocultar clave')) : @js(__('Mostrar clave'))" /><flux:button type="button" size="sm" variant="ghost" icon="arrow-path" wire:click="regenerateStreamKey({{ $stream->id }})" wire:confirm="{{ __('La clave anterior dejará de funcionar. ¿Continuar?') }}">{{ __('Regenerar') }}</flux:button></div>
                            </div>
                        </details>
                    </div>
                @elseif ($stream->isLive())
                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Duración') }}</p><p class="mt-1 text-lg font-semibold tabular-nums">{{ $duration }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Audiencia ahora') }}</p><p class="mt-1 text-lg font-semibold">{{ $controlMetrics['current_viewers'] }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Espectadores únicos') }}</p><p class="mt-1 text-lg font-semibold">{{ $controlMetrics['unique_viewers'] }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Comentarios') }}</p><p class="mt-1 text-lg font-semibold">{{ $controlMetrics['comments'] }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Pedidos pagados') }}</p><p class="mt-1 text-lg font-semibold">{{ $controlMetrics['orders'] }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Productos vendidos') }}</p><p class="mt-1 text-lg font-semibold">{{ $controlMetrics['products_sold'] }}</p></div>
                        <div class="col-span-2 rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950"><p class="text-xs text-emerald-700 dark:text-emerald-300">{{ __('Ventas del Live') }}</p><p class="mt-1 text-lg font-semibold text-emerald-700 dark:text-emerald-300">${{ number_format($controlMetrics['sales_cents'] / 100, 0, ',', '.') }}</p></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select
                            :label="__('Producto fijado')"
                            :value="$stream->pinned_product_id"
                            wire:change="pinProduct({{ $stream->id }}, $event.target.value)"
                        >
                            <flux:select.option value="">{{ __('Sin producto fijado') }}</flux:select.option>
                            @foreach ($stream->products as $product)
                                <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="replay_url" type="url" :label="__('Enlace del replay (opcional)')" placeholder="https://" />
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:badge color="red">{{ trans_choice(':count espectador|:count espectadores', $stream->currentViewersCount(), ['count' => $stream->currentViewersCount()]) }}</flux:badge>
                        <flux:button size="sm" variant="primary" icon="video-camera" :href="route('emprendedores.negocios.lives.studio', [$this->business, $stream])">{{ __('Abrir estudio') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="end({{ $stream->id }})" wire:confirm="{{ __('¿Finalizar esta transmisión?') }}">
                            {{ __('Finalizar Live') }}
                        </flux:button>
                    </div>
                    @if ($stream->messages()->where('status', 'publicado')->exists())
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-semibold">{{ __('Comentarios recientes') }}</p>
                            <div class="mt-3 space-y-2">
                                @foreach ($stream->messages()->where('status', 'publicado')->with('user')->latest()->limit(5)->get() as $comment)
                                    <p class="text-sm"><span class="font-medium">{{ $comment->user->name }}:</span> <span class="text-zinc-600 dark:text-zinc-300">{{ $comment->body }}</span></p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @elseif ($stream->isReplayAvailable())
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Duración') }}</p><p class="mt-1 font-semibold tabular-nums">{{ $duration }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Espectadores') }}</p><p class="mt-1 font-semibold">{{ $controlMetrics['unique_viewers'] }}</p></div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800"><p class="text-xs text-zinc-500">{{ __('Pedidos pagados') }}</p><p class="mt-1 font-semibold">{{ $controlMetrics['orders'] }}</p></div>
                        <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950"><p class="text-xs text-emerald-700 dark:text-emerald-300">{{ __('Ventas') }}</p><p class="mt-1 font-semibold text-emerald-700 dark:text-emerald-300">${{ number_format($controlMetrics['sales_cents'] / 100, 0, ',', '.') }}</p></div>
                    </div>
                    <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Replay disponible y productos comprables.') }}</flux:text>
                @else
                    <flux:text class="text-sm text-zinc-500">{{ __('Finalizó sin replay.') }}</flux:text>
                @endif
            </article>
            <flux:modal name="preview-live-{{ $stream->id }}" class="w-full !max-w-5xl !bg-zinc-950 !p-0 text-white">
                <div class="relative flex aspect-video items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-800 to-black">
                    @if ($stream->coverUrl())<img src="{{ $stream->coverUrl() }}" class="absolute inset-0 size-full object-cover" alt="{{ $stream->title }}">@else<flux:icon.video-camera class="size-20 text-white/25" />@endif
                    @if ($stream->isLive())<span class="absolute left-4 top-4 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold uppercase">{{ __('En vivo') }}</span>@endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-6 pt-24"><h2 class="text-2xl font-bold">{{ $stream->title }}</h2>@if ($stream->description)<p class="mt-2 text-white/80">{{ $stream->description }}</p>@endif</div>
                </div>
                @if ($stream->products->isNotEmpty())<div class="flex gap-3 overflow-x-auto p-4">@foreach ($stream->products as $product)<span class="shrink-0 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-zinc-900">{{ $product->name }}</span>@endforeach</div>@endif
            </flux:modal>
        @empty
            <x-states.empty :title="__('Todavía no tienes transmisiones')" :description="__('Prepara tu primer Live para vender y conversar con tu comunidad.')" />
        @endforelse
    </section>

    <flux:modal name="live-destination" class="max-h-[92vh] w-full !max-w-2xl overflow-y-auto">
        <form wire:submit="saveDestination" class="space-y-5">
            <div><flux:heading size="lg">{{ __('Agregar salida RTMP') }}</flux:heading><flux:subheading>{{ __('La red social recibe una copia; el Live principal continúa alojado en Merkamigo.') }}</flux:subheading></div>
            <flux:select wire:model.live="destination_provider" :label="__('Plataforma')">
                <flux:select.option value="youtube">YouTube Live</flux:select.option>
                <flux:select.option value="facebook">Facebook Live</flux:select.option>
                <flux:select.option value="instagram">Instagram Live</flux:select.option>
                <flux:select.option value="tiktok">TikTok LIVE</flux:select.option>
                <flux:select.option value="custom">{{ __('RTMP personalizado') }}</flux:select.option>
            </flux:select>
            <x-live.destination-guide :provider="$destination_provider" />
            <flux:input wire:model="destination_name" :label="__('Nombre del destino')" :placeholder="$this->destinationNamePlaceholder" />
            <flux:input wire:model="destination_rtmp_url" :label="__('URL RTMP/RTMPS')" placeholder="rtmps://" />
            <flux:input wire:model="destination_stream_key" type="password" :label="__('Stream Key del destino')" autocomplete="off" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Guardar destino') }}</flux:button></div>
        </form>
    </flux:modal>
</section>
