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
 * "Publicaciones" del negocio (2.2 del TODO social, Sprint 2): crear y
 * listar posts, sin flujo de edición de borrador todavía (ver
 * TODO-Social-Sprint2.md — se publica de inmediato, reduce el alcance a
 * propósito para este sprint).
 */
new #[Title('Publicaciones')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $type = 'texto';

    public string $body = '';

    /** @var array<int, \Illuminate\Http\UploadedFile> */
    public array $photos = [];

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
    public function posts()
    {
        return $this->business->posts()
            ->where('type', '!=', 'video')
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

        try {
            app(CreatePost::class)->handle($this->business, [
                'type' => $this->type,
                'body' => $this->body,
                'product_ids' => $this->product_ids,
            ], $this->photos, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('body', $e->validator->errors()->first());

            return;
        }

        $this->reset(['type', 'body', 'photos', 'product_ids']);
        $this->type = 'texto';

        unset($this->posts);

        Flux::modal('create-publication')->close();
        Flux::toast(variant: 'success', text: __('¡Publicación creada!'));
    }

    public function suggestWithAi(): void
    {
        $this->authorize('update', $this->business);

        $suggestion = app(GenerateSocialSalesCopy::class)->handle($this->business, 'publicacion', [
            'text' => $this->body,
            'type' => $this->type,
            'product_ids' => $this->product_ids,
        ]);

        if (! $suggestion) {
            Flux::toast(variant: 'warning', text: __('No fue posible generar una sugerencia. Intenta nuevamente.'));

            return;
        }

        $this->body = $suggestion;
        Flux::toast(text: __('Borrador generado. Revísalo antes de publicar.'));
    }

    public function removePendingPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function delete(int $postId): void
    {
        $this->authorize('update', $this->business);

        $post = $this->business->posts()->findOrFail($postId);

        app(DeletePost::class)->handle($post, Auth::user());

        unset($this->posts);

        Flux::toast(variant: 'success', text: __('Publicación eliminada.'));
    }
}; ?>

<section class="w-full space-y-6" x-data="{ previewPost: null }" x-on:keydown.escape.window="previewPost = null">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-3xl !font-bold !tracking-tight">{{ __('Publicaciones') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Comparte novedades de tu negocio en el feed de Merkamigo.') }}</flux:subheading>
        </div>
        <flux:modal.trigger name="create-publication">
            <flux:button variant="primary" icon="plus">{{ __('Crear publicación') }}</flux:button>
        </flux:modal.trigger>
    </header>

    <section class="entrepreneur-card p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-2.5">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Tus publicaciones') }}</h2>
            <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ $this->posts->count() }}</span>
        </div>
        <div class="space-y-3">
            @forelse ($this->posts as $post)
                <article class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-3 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="previewPost = {{ $post->id }}" class="group relative h-28 w-full shrink-0 overflow-hidden rounded-xl bg-zinc-100 sm:w-44 dark:bg-zinc-800" aria-label="{{ __('Previsualizar publicación') }}">
                        @if ($post->media->first())
                            <img src="{{ $post->media->first()->url() }}" class="size-full object-cover transition duration-300 group-hover:scale-105" alt="{{ __('Vista previa de la publicación') }}">
                        @else
                            <div class="flex size-full items-center justify-center"><flux:icon.document-text class="size-10 text-zinc-400" /></div>
                        @endif
                        <span class="absolute inset-x-2 bottom-2 inline-flex items-center justify-center gap-1.5 rounded-full bg-black/65 px-3 py-1.5 text-xs text-white"><flux:icon.eye class="size-4" />{{ __('Vista previa') }}</span>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 font-medium text-zinc-900 dark:text-white">{{ $post->body ?: __('Publicación sin texto') }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $post->published_at?->diffForHumans() ?? __('Borrador') }}</p>
                        <div class="mt-2 flex flex-wrap gap-4 text-sm text-zinc-500">
                            <span class="inline-flex items-center gap-1"><flux:icon.heart class="size-4" />{{ $post->reactions_count }}</span>
                            <span class="inline-flex items-center gap-1"><flux:icon.chat-bubble-oval-left class="size-4" />{{ $post->comments_count }}</span>
                            @if ($post->products->isNotEmpty())<span class="inline-flex items-center gap-1"><flux:icon.cube class="size-4" />{{ $post->products->count() }}</span>@endif
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2 self-end sm:self-auto">
                        <flux:button type="button" variant="ghost" icon="eye" x-on:click="previewPost = {{ $post->id }}" aria-label="{{ __('Previsualizar publicación') }}" />
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="delete({{ $post->id }})" wire:confirm="{{ __('¿Eliminar esta publicación?') }}" aria-label="{{ __('Eliminar publicación') }}" />
                    </div>
                </article>

                <template x-teleport="body">
                    <div x-show="previewPost === {{ $post->id }}" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true">
                        <button type="button" x-on:click="previewPost = null" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="{{ __('Cerrar') }}"><flux:icon.x-mark class="size-7" /></button>
                        <article class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-zinc-900">
                            <div class="flex items-center gap-3 p-4">
                                <div class="flex size-10 items-center justify-center rounded-full bg-brand-50 font-bold text-brand-600">M</div>
                                <div><p class="font-semibold">{{ $this->business->name }}</p><p class="text-xs text-zinc-500">{{ $post->published_at?->diffForHumans() ?? __('Borrador') }}</p></div>
                            </div>
                            @if ($post->body)<p class="whitespace-pre-line px-4 pb-4 text-sm leading-relaxed">{{ $post->body }}</p>@endif
                            @if ($post->media->isNotEmpty())
                                <div class="grid gap-0.5 {{ $post->media->count() > 1 ? 'grid-cols-2' : '' }}">
                                    @foreach ($post->media->take(4) as $media)<img src="{{ $media->url() }}" class="max-h-[55vh] size-full object-cover" alt="">@endforeach
                                </div>
                            @endif
                            @if ($post->products->isNotEmpty())<div class="flex flex-wrap gap-2 p-4">@foreach ($post->products as $product)<span class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium dark:bg-zinc-800">{{ $product->name }}</span>@endforeach</div>@endif
                            <div class="flex items-center gap-6 border-t border-zinc-200 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300"><span>♥ {{ $post->reactions_count }}</span><span>○ {{ $post->comments_count }}</span><span>{{ __('Compartir') }}</span></div>
                        </article>
                    </div>
                </template>
            @empty
                <x-states.empty :title="__('Todavía no has publicado nada')" :description="__('Cuenta novedades de tu negocio para que aparezcan en el feed.')" />
            @endforelse
        </div>
    </section>

    <flux:modal name="create-publication" class="w-full !max-w-6xl">
        <div class="mb-5"><flux:heading size="lg">{{ __('Crear publicación') }}</flux:heading><flux:subheading>{{ __('Comparte una novedad con tu comunidad.') }}</flux:subheading></div>
        <form wire:submit="save" class="grid overflow-hidden rounded-2xl border border-zinc-200 lg:grid-cols-2 dark:border-zinc-700">
            <div class="space-y-4 p-5 lg:border-e lg:border-zinc-200 dark:lg:border-zinc-700">
                <div class="flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">1</span><h2 class="font-semibold">{{ __('Contenido de la publicación') }}</h2></div>
                <flux:select wire:model.live="type" :label="__('Tipo')"><flux:select.option value="texto">{{ __('Texto') }}</flux:select.option><flux:select.option value="imagen">{{ __('Imagen') }}</flux:select.option><flux:select.option value="carrusel">{{ __('Carrusel de fotos') }}</flux:select.option><flux:select.option value="promocion">{{ __('Promoción') }}</flux:select.option></flux:select>
                <flux:textarea wire:model.live.debounce.300ms="body" :label="__('¿Qué quieres contar?')" rows="4" placeholder="{{ __('Ej: ¡Llegaron nuevos colores esta semana!') }}" />
                <div class="flex justify-end"><flux:button type="button" size="sm" variant="ghost" icon="sparkles" wire:click="suggestWithAi" wire:loading.attr="disabled">{{ __('Sugerir texto con IA') }}</flux:button></div>
                @error('body')<flux:text class="text-sm text-red-600">{{ $message }}</flux:text>@enderror
                <x-forms.image-upload-field wire:model="photos" multiple accept="image/*" :title="__('Fotos (opcional)')" :hint="__('Selecciona una o varias imágenes JPG, PNG o WEBP.')" :preview-urls="collect($photos)->map(fn ($photo) => $photo->temporaryUrl())->all()" remove-action="removePendingPhoto" :error="$errors->first('photos')" />
                @if ($this->availableProducts->isNotEmpty())
                    <div><flux:text class="mb-2 font-medium">{{ __('Etiquetar productos (opcional)') }}</flux:text><div class="grid gap-2 sm:grid-cols-2">@foreach ($this->availableProducts as $product)<label class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-700"><input type="checkbox" wire:model.live="product_ids" value="{{ $product->id }}">{{ $product->name }}</label>@endforeach</div></div>
                @endif
                <div class="flex justify-end gap-2 pt-2"><flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Publicar') }}</flux:button></div>
            </div>
            <div class="bg-zinc-50 p-5 dark:bg-zinc-900/70">
                <div class="mb-4 flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">2</span><div><h2 class="font-semibold">{{ __('Vista previa') }}</h2><p class="text-xs text-zinc-500">{{ __('Así aparecerá en el feed.') }}</p></div></div>
                <article class="mx-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
                    <div class="flex items-center gap-3 p-4"><div class="flex size-10 items-center justify-center rounded-full bg-brand-50 font-bold text-brand-600">M</div><div><p class="text-sm font-semibold">{{ $this->business->name }}</p><p class="text-xs text-zinc-500">{{ __('ahora') }}</p></div></div>
                    <p class="min-h-16 whitespace-pre-line px-4 pb-4 text-sm">{{ $body ?: __('Tu texto aparecerá aquí…') }}</p>
                    @if (count($photos))<div class="grid gap-0.5 {{ count($photos) > 1 ? 'grid-cols-2' : '' }}">@foreach (collect($photos)->take(4) as $photo)<img src="{{ $photo->temporaryUrl() }}" class="h-40 w-full object-cover" alt="">@endforeach</div>@else<div class="flex h-48 items-center justify-center bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon.photo class="size-12" /></div>@endif
                    <div class="flex gap-5 border-t border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700"><span>♡ {{ __('Me gusta') }}</span><span>○ {{ __('Comentar') }}</span><span>{{ __('Compartir') }}</span></div>
                </article>
            </div>
        </form>
    </flux:modal>
</section>
