<div
    data-verified-awning
    aria-hidden="true"
    class="pointer-events-none absolute inset-x-0 top-0 z-20 flex overflow-hidden rounded-t-xl drop-shadow-md"
>
    @for ($segment = 0; $segment < 24; $segment++)
        <span class="h-7 min-w-6 flex-1 rounded-b-xl sm:h-8 {{ $segment % 2 === 0 ? 'bg-brand-600' : 'bg-white' }}"></span>
    @endfor
</div>
