<x-layouts::public :title="__('Preguntas frecuentes')">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <flux:heading size="xl" class="mb-2">{{ __('Preguntas frecuentes') }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Si tu duda no está aquí, escríbenos por WhatsApp desde soporte.') }}
        </flux:subheading>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700" x-data="{ open: null }">
            @foreach ($faqs as $index => $faq)
                <div class="py-4">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 text-left font-medium"
                        x-on:click="open = open === {{ $index }} ? null : {{ $index }}"
                    >
                        {{ $faq['pregunta'] }}
                        <flux:icon.chevron-down class="size-4 shrink-0 transition-transform" x-bind:class="open === {{ $index }} ? 'rotate-180' : ''" variant="outline" />
                    </button>

                    <div x-show="open === {{ $index }}" x-cloak>
                        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $faq['respuesta'] }}</flux:text>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            <flux:button :href="route('soporte')" variant="primary" wire:navigate>
                {{ __('Ir a soporte') }}
            </flux:button>
        </div>
    </div>
</x-layouts::public>
