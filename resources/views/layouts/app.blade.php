{{--
    Pedido del usuario (2026-10-06): "Toda las secciones que tienen los
    clientes deben verse como el site principal, con el header normal, y
    los contenidos en el centro, esto así como está me confunde con el
    modo emprendedor." Este es el layout POR DEFECTO de Livewire
    (`config('livewire.component_layout')` = `layouts::app`, sin override
    en este proyecto) — lo usan tanto páginas con `<x-layouts::app>`
    explícito (`clientes/actividad.blade.php`, `clientes/favoritos.blade.php`)
    como páginas Livewire sin `#[Layout(...)]` propio (`pages::clientes.pedidos`,
    `pages::clientes.compras`, `pages::settings.profile`, etc.) — por eso
    arreglarlo UNA sola vez acá, en el punto de entrada compartido, cubre
    todas esas pantallas de una vez en vez de tocarlas una por una.

    Un cliente ve el header normal del sitio (`x-layouts::cliente`, el
    mismo de Vitrinas/Comunidad/Eventos/Merkapuntos). Un emprendedor (o
    alguien que todavía no eligió experiencia) sigue viendo el panel con
    sidebar — ese sí es su contexto real de trabajo, no algo que haya que
    cambiarle.
--}}
@if (auth()->user()->experience === 'cliente')
    <x-layouts::cliente :title="$title ?? null">
        {{ $slot }}
    </x-layouts::cliente>
@else
    <x-layouts::app.sidebar :title="$title ?? null">
        <flux:main :class="auth()->user()->experience === 'emprendedor' ? 'entrepreneur-main' : ''">
            {{ $slot }}
        </flux:main>
    </x-layouts::app.sidebar>
@endif
