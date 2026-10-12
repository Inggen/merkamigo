{{--
    Pedido del usuario (2026-10-11): footer simple de una sola franja,
    en reemplazo del footer completo (`public-footer.blade.php`, que
    queda intacto como "plan B" por si se vuelve a necesitar — ninguna
    otra vista lo referencia directamente, solo este `@include`).

    A diferencia del footer anterior (mucho más alto, con columnas de
    Explora/Ayuda/Legal), este es una sola franja delgada — así que,
    a diferencia de ahí, acá SÍ hay riesgo real de que el chatbot
    flotante (fijo en la esquina inferior derecha, posición de
    escritorio desde `md:` — ver storefront-chat-widget.blade.php)
    termine tapando los enlaces de la derecha al hacer scroll hasta el
    final de la página. `md:pr-48` en el bloque de enlaces reserva ese
    espacio desde el MISMO punto en el que el widget pasa a su
    posición de escritorio (probado con mediciones reales a 900px,
    1280px, 1440px y 1920px — `lg:` no alcanzaba a cubrir 900px).
    Mismo ajuste que ya se hizo para el botón "Enviar" de Mensajes por
    el mismo motivo.
--}}
<footer class="bg-brand-800 text-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-center gap-2 px-6 py-4 text-center text-sm sm:flex-row sm:justify-between sm:gap-4 sm:text-left md:pr-48">
        <p class="text-white/90">
            &copy; {{ now()->year }} {{ config('app.name', 'Merkamigo') }}
        </p>

        <p class="inline-flex items-center gap-1.5 text-white/90">
            {{ __('Hecho con') }}
            <flux:icon.heart class="size-4 text-rose-300" variant="solid" />
            {{ __('por') }}
            <a href="https://inggen.com" target="_blank" rel="noopener noreferrer" class="font-semibold underline decoration-white/40 underline-offset-4 transition hover:decoration-white">
                Inggen
            </a>
        </p>

        <nav class="flex items-center gap-2 text-white/90">
            <a href="{{ route('terminos') }}" wire:navigate class="transition hover:text-white hover:underline">{{ __('Términos') }}</a>
            <span class="text-white/40">|</span>
            <a href="{{ route('privacidad') }}" wire:navigate class="transition hover:text-white hover:underline">{{ __('Privacidad') }}</a>
            <span class="text-white/40">|</span>
            <a href="{{ route('sitemap') }}" class="transition hover:text-white hover:underline">{{ __('Sitemap') }}</a>
        </nav>
    </div>
</footer>
