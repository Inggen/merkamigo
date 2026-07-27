<x-layouts::public :title="__('Municipios')">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <flux:heading size="xl" class="mb-2">{{ __('Municipios activos') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Merkamigo empieza en Cajicá y Zipaquirá, y va a más municipios pronto.') }}</flux:subheading>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($municipalities as $municipality)
                <a href="{{ route('plaza.show', $municipality) }}" class="rounded-xl border border-zinc-200 p-4 hover:border-brand-300 dark:border-zinc-700" wire:navigate>
                    <flux:heading>{{ $municipality->name }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $municipality->department }}</flux:text>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::public>
