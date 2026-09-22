<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Social\Actions\DeletePost;
use App\Domain\Social\Actions\GenerateSocialSalesCopy;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Reels" del negocio (Fase 4 del TODO social, Sprint 4): mismo flujo que
 * "Publicaciones", forzando `type = video` — un reel es un post de video,
 * no un dominio aparte (ver `App\Http\Controllers\ReelController`).
 */
new #[Title('Reels')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $body = '';

    public $video = null;

    /** @var array<int, int> */
    public array $product_ids = [];

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
    public function reels()
    {
        return $this->business->posts()
            ->where('type', 'video')
            ->with(['media', 'products'])
            ->withCount(['reactions', 'visibleComments as comments_count'])
            ->latest('created_at')
            ->get();
    }

    #[Computed]
    public function availableProducts()
    {
        return $this->business->products()->where('status', 'publicado')->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'video' => ['required', 'file'],
        ], [], ['video' => __('video')]);

        try {
            app(CreatePost::class)->handle($this->business, [
                'type' => 'video',
                'body' => $this->body,
                'product_ids' => $this->product_ids,
            ], [$this->video], Auth::user());
        } catch (ValidationException $e) {
            $this->addError('body', $e->validator->errors()->first());

            return;
        }

        $this->reset(['body', 'video', 'product_ids']);

        unset($this->reels);

        Flux::modal('create-reel')->close();
        Flux::toast(variant: 'success', text: __('¡Reel publicado!'));
    }

    public function removeVideo(): void
    {
        $this->reset('video');
        $this->resetErrorBag('video');
    }

    public function delete(int $postId): void
    {
        $this->authorize('update', $this->business);

        $post = $this->business->posts()->where('type', 'video')->findOrFail($postId);

        app(DeletePost::class)->handle($post, Auth::user());

        unset($this->reels);

        Flux::toast(variant: 'success', text: __('Reel eliminado.'));
    }

    public function suggestWithAi(): void
    {
        $this->authorize('update', $this->business);

        $suggestion = app(GenerateSocialSalesCopy::class)->handle($this->business, 'reel', [
            'text' => $this->body,
            'product_ids' => $this->product_ids,
        ]);

        if (! $suggestion) {
            Flux::toast(variant: 'warning', text: __('No fue posible generar una sugerencia.'));

            return;
        }

        $this->body = $suggestion;
        Flux::toast(text: __('Borrador generado. Revísalo antes de publicar.'));
    }
}; ?>

<section class="w-full space-y-6" x-data="{ previewReel: null }" x-on:keydown.escape.window="previewReel = null">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><flux:heading size="xl" class="!text-3xl !font-bold !tracking-tight">{{ __('Reels') }}</flux:heading><flux:subheading class="mt-1">{{ __('Comparte videos cortos de tu negocio — aparecen en Reels de Merkamigo.') }}</flux:subheading></div>
        <flux:modal.trigger name="create-reel"><flux:button variant="primary" icon="plus">{{ __('Crear reel') }}</flux:button></flux:modal.trigger>
    </header>

    <section class="entrepreneur-card p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-2.5"><h2 class="text-lg font-semibold">{{ __('Tus reels') }}</h2><span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ $this->reels->count() }}</span></div>
        <div class="space-y-3">
            @forelse ($this->reels as $reel)
                <article class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-3 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="previewReel = {{ $reel->id }}" class="group relative h-32 w-full shrink-0 overflow-hidden rounded-xl bg-black sm:w-44" aria-label="{{ __('Previsualizar reel') }}">
                        @if ($reel->media->first())<video src="{{ $reel->media->first()->url() }}" class="size-full object-cover" muted preload="metadata"></video>@endif
                        <span class="absolute inset-0 flex items-center justify-center"><span class="flex size-11 items-center justify-center rounded-full bg-white/90 text-brand-600 shadow"><flux:icon.play class="ms-0.5 size-5" /></span></span>
                        <span class="absolute inset-x-2 bottom-2 rounded-full bg-black/65 px-3 py-1.5 text-center text-xs text-white">{{ __('Vista previa') }}</span>
                    </button>
                    <div class="min-w-0 flex-1"><p class="line-clamp-2 font-medium">{{ $reel->body ?: __('Reel sin descripción') }}</p><p class="mt-1 text-xs text-zinc-500">{{ $reel->published_at?->diffForHumans() ?? __('Borrador') }}</p><div class="mt-2 flex gap-4 text-sm text-zinc-500"><span>♥ {{ $reel->reactions_count }}</span><span>○ {{ $reel->comments_count }}</span></div></div>
                    <div class="flex shrink-0 gap-2 self-end sm:self-auto"><flux:button type="button" variant="ghost" icon="eye" x-on:click="previewReel = {{ $reel->id }}" aria-label="{{ __('Previsualizar reel') }}" /><flux:button type="button" variant="ghost" icon="trash" wire:click="delete({{ $reel->id }})" wire:confirm="{{ __('¿Eliminar este reel?') }}" aria-label="{{ __('Eliminar reel') }}" /></div>
                </article>
                <template x-teleport="body">
                    <div x-show="previewReel === {{ $reel->id }}" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-3" role="dialog" aria-modal="true">
                        <button type="button" x-on:click="previewReel = null" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="{{ __('Cerrar') }}"><flux:icon.x-mark class="size-7" /></button>
                        <div class="relative h-full max-h-[900px] w-full max-w-md overflow-hidden rounded-3xl bg-black shadow-2xl ring-1 ring-white/15">
                            @if ($reel->media->first())<x-media.video-player :src="$reel->media->first()->url()" aspect="auto" fit="cover" class="h-full rounded-none shadow-none" autoplay loop />@endif
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-5 pt-28 text-white"><p class="font-semibold">{{ $this->business->name }}</p>@if ($reel->body)<p class="mt-2 text-sm">{{ $reel->body }}</p>@endif<div class="mt-4 flex gap-5 text-sm"><span>♥ {{ $reel->reactions_count }}</span><span>○ {{ $reel->comments_count }}</span></div></div>
                        </div>
                    </div>
                </template>
            @empty
                <x-states.empty :title="__('Todavía no has publicado reels')" :description="__('Sube un video corto para que aparezca en Reels de Merkamigo.')" />
            @endforelse
        </div>
    </section>

    <flux:modal name="create-reel" class="w-full !max-w-6xl">
        <div class="mb-5"><flux:heading size="lg">{{ __('Crear reel') }}</flux:heading><flux:subheading>{{ __('Comparte un video corto y conecta con tu comunidad.') }}</flux:subheading></div>
        <form wire:submit="save" class="grid overflow-hidden rounded-2xl border border-zinc-200 lg:grid-cols-2 dark:border-zinc-700">
            <div class="space-y-4 p-5 lg:border-e lg:border-zinc-200 dark:lg:border-zinc-700">
                <div class="flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">1</span><h2 class="font-semibold">{{ __('Contenido del reel') }}</h2></div>
                <div><flux:text class="mb-2 font-medium">{{ __('Video') }}</flux:text>@if ($video)<div class="relative overflow-hidden rounded-xl"><x-media.video-player :src="$video->temporaryUrl()" aspect="auto" class="h-48" /><button type="button" wire:click="removeVideo" class="absolute right-2 top-2 z-30 flex size-8 items-center justify-center rounded-full bg-black/70 text-white" aria-label="{{ __('Quitar video') }}"><flux:icon.x-mark class="size-5" /></button></div>@else<label class="flex min-h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-center hover:border-brand-300 hover:bg-brand-50 dark:border-zinc-700 dark:bg-zinc-900"><flux:icon.video-camera class="size-10 text-zinc-400" /><span class="font-medium">{{ __('Seleccionar video') }}</span><span class="text-xs text-zinc-500">MP4, WEBM o MOV</span><input type="file" wire:model="video" accept="video/mp4,video/webm,video/quicktime" class="sr-only"></label>@endif @error('video')<flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text>@enderror</div>
                <flux:textarea wire:model.live.debounce.300ms="body" :label="__('Descripción (opcional)')" rows="3" placeholder="{{ __('Ej: Así preparamos nuestro pan artesanal 🍞') }}" />
                <div class="flex justify-end"><flux:button type="button" size="sm" variant="ghost" icon="sparkles" wire:click="suggestWithAi" wire:loading.attr="disabled">{{ __('Sugerir texto con IA') }}</flux:button></div>
                @if ($this->availableProducts->isNotEmpty())<div><flux:text class="mb-2 font-medium">{{ __('Etiquetar productos (opcional)') }}</flux:text><div class="grid gap-2 sm:grid-cols-2">@foreach ($this->availableProducts as $product)<label class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-700"><input type="checkbox" wire:model.live="product_ids" value="{{ $product->id }}">{{ $product->name }}</label>@endforeach</div></div>@endif
                <div class="flex justify-end gap-2 pt-2"><flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Publicar reel') }}</flux:button></div>
            </div>
            <div class="bg-zinc-50 p-5 dark:bg-zinc-900/70">
                <div class="mb-4 flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">2</span><div><h2 class="font-semibold">{{ __('Vista previa') }}</h2><p class="text-xs text-zinc-500">{{ __('Así aparecerá en Reels.') }}</p></div></div>
                <div class="mx-auto w-full max-w-[310px] rounded-[2.5rem] bg-zinc-950 p-2.5 shadow-2xl"><div class="relative aspect-[9/16] overflow-hidden rounded-[2rem] bg-zinc-800 text-white">@if ($video)<video src="{{ $video->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" autoplay loop muted playsinline></video>@else<div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center text-white/60"><flux:icon.video-camera class="size-12" /><p class="px-8 text-sm">{{ __('Selecciona un video para ver la vista previa.') }}</p></div>@endif<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-4 pt-24"><p class="text-sm font-semibold">{{ $this->business->name }}</p><p class="mt-1 min-h-10 text-sm">{{ $body ?: __('Tu descripción aparecerá aquí…') }}</p><div class="mt-3 flex gap-4 text-sm"><span>♡</span><span>○</span><span>↗</span></div></div></div></div>
            </div>
        </form>
    </flux:modal>
</section>
