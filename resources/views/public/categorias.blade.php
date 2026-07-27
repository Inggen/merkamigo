<x-layouts::public :title="__('Categorías')">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <flux:heading size="xl" class="mb-6">{{ __('Categorías') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            @foreach ($categories as $category)
                <flux:badge size="lg">{{ $category->name }}</flux:badge>
            @endforeach
        </div>
    </div>
</x-layouts::public>
