@props([
    'model',
    'label',
    'file' => null,
    'storedUrl' => null,
])

<div>
    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>

    <div
        x-data="{ isDragging: false }"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="
            isDragging = false;
            const file = $event.dataTransfer.files[0];
            if (! file) { return; }
            const transfer = new DataTransfer();
            transfer.items.add(file);
            $refs.input.files = transfer.files;
            $refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        "
        x-on:click="$refs.input.click()"
        :class="isDragging ? 'border-primary-500 bg-primary-500/5 dark:bg-primary-500/10' : 'border-gray-300 dark:border-gray-600'"
        class="mt-1 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed p-3 text-center transition-colors hover:border-primary-400"
        wire:loading.class="opacity-50 pointer-events-none"
        wire:target="{{ $model }}"
    >
        <input type="file" x-ref="input" wire:model="{{ $model }}" accept="image/*" class="hidden" />

        @if ($file)
            <img src="{{ $file->temporaryUrl() }}" class="h-20 w-full rounded object-cover" />
        @elseif ($storedUrl)
            <img src="{{ $storedUrl }}" class="h-20 w-full rounded object-cover" />
        @else
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3.75 3.75 0 0 1 4.161 4.667 4.5 4.5 0 0 1-1.234 8.433H6.75Z" />
            </svg>
            <span class="text-[11px] text-gray-500 dark:text-gray-400">Arrastra o haz clic</span>
        @endif
    </div>
</div>
