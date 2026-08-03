<x-layouts::cliente :title="__('Documentacion de API')">
    <section class="mx-auto max-w-4xl px-6 py-12">
        <div class="rounded-3xl border border-zinc-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">{{ __('API publica') }}</p>
            <h1 class="mt-3 text-3xl font-semibold text-zinc-900">{{ __('Merkamigo API') }}</h1>
            <p class="mt-4 text-base leading-7 text-zinc-600">
                {{ __('Punto de entrada publico para integraciones y descubrimiento automatizado.') }}
            </p>

            <dl class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-zinc-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('Base URL') }}</dt>
                    <dd class="mt-2 break-all text-sm text-zinc-900">{{ $apiBaseUrl }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('Estado') }}</dt>
                    <dd class="mt-2 break-all text-sm text-zinc-900">{{ $healthUrl }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('OpenAPI') }}</dt>
                    <dd class="mt-2 break-all text-sm text-zinc-900">{{ $openApiUrl }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('API Catalog') }}</dt>
                    <dd class="mt-2 break-all text-sm text-zinc-900">{{ $catalogUrl }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $openApiUrl }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white">
                    {{ __('Ver especificacion OpenAPI') }}
                </a>
                <a href="{{ $healthUrl }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700">
                    {{ __('Ver health endpoint') }}
                </a>
                <a href="{{ $catalogUrl }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700">
                    {{ __('Ver api-catalog') }}
                </a>
            </div>
        </div>
    </section>
</x-layouts::cliente>
