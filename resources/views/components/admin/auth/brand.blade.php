@props([
    'compact' => false,
])

<div {{ $attributes->class([
    'flex items-center text-slate-900',
    'gap-2.5' => $compact,
    'gap-4' => ! $compact,
]) }}>
    <div @class([
        'rounded-2xl bg-white shadow-sm ring-1 ring-red-100',
        'p-2.5' => $compact,
        'p-3' => ! $compact,
    ])>
        <x-app-logo-icon @class([
            'text-[#e3342f]',
            'size-9' => $compact,
            'size-14' => ! $compact,
        ]) />
    </div>

    <div class="leading-none">
        <div @class([
            'font-semibold tracking-tight text-slate-900',
            'text-[2.95rem]' => ! $compact,
            'text-[2rem]' => $compact,
        ])>
            <span class="text-[#e3342f]">Merka</span><span>migo</span>
        </div>

        <div @class([
            'mt-2 text-[0.7rem] font-medium uppercase tracking-[0.34em] text-slate-500' => ! $compact,
            'mt-1 text-[0.52rem] font-medium uppercase tracking-[0.24em] text-slate-500' => $compact,
        ])>
            Cercania · Comunidad · Visibilidad
        </div>
    </div>
</div>
