@props([
    'url',
    'text' => '',
])

{{--
    Pedido del usuario (2026-10-16): poder republicar un estado o un
    reel en WhatsApp, Facebook o Instagram — mismos tres iconos y mismo
    comportamiento que ya existía solo para productos
    (`vitrinas/product.blade.php`), ahora como componente reutilizable
    para no duplicar el marcado. Instagram no tiene un enlace web que
    abra directo a "compartir" con contenido precargado — como en
    productos, se copia el enlace y se abre Instagram para pegarlo a
    mano.
--}}
<div {{ $attributes->class(['flex items-center gap-1.5']) }}>
    <a
        href="https://wa.me/?text={{ urlencode(trim($text.' '.$url)) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex size-8 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
        aria-label="{{ __('Compartir por WhatsApp') }}"
        title="{{ __('Compartir por WhatsApp') }}"
    >
        <svg viewBox="0 0 24 24" class="size-4 fill-current" aria-hidden="true"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.62 2 2.23 6.38 2.23 11.79c0 1.73.45 3.42 1.31 4.91L2 22l5.46-1.43a9.74 9.74 0 0 0 4.57 1.16h.01c5.41 0 9.79-4.39 9.79-9.8 0-2.62-1.02-5.08-2.88-6.92Zm-7.02 15.16h-.01a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.24.85.87-3.15-.2-.32a8.12 8.12 0 0 1-1.25-4.35c0-4.49 3.67-8.15 8.18-8.15 2.18 0 4.22.85 5.75 2.39a8.08 8.08 0 0 1 2.38 5.76c0 4.5-3.66 8.16-8.05 8.16Zm4.47-6.11c-.24-.12-1.42-.7-1.64-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18-.71-.64-1.19-1.42-1.33-1.66-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.48-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.57 4.09 3.61.57.25 1.02.4 1.37.52.58.18 1.1.15 1.52.09.46-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/></svg>
    </a>
    <a
        href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex size-8 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
        aria-label="{{ __('Compartir en Facebook') }}"
        title="{{ __('Compartir en Facebook') }}"
    >
        <svg viewBox="0 0 24 24" class="size-4 fill-current" aria-hidden="true"><path d="M13.5 21v-7h2.3l.4-3h-2.7V9.1c0-.9.3-1.6 1.6-1.6H16V4.8c-.5-.1-1.3-.2-2.2-.2-2.2 0-3.8 1.3-3.8 4V11H7.8v3H10V21h3.5Z"/></svg>
    </a>
    <button
        type="button"
        x-on:click="(() => {
            const url = @js($url);
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(url).catch(() => {});
            } else {
                const temp = document.createElement('textarea');
                temp.value = url;
                temp.style.position = 'fixed';
                temp.style.opacity = '0';
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
            }
            $flux.toast(@js(__('Enlace copiado para compartir en Instagram')));
        })()"
        class="inline-flex size-8 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
        aria-label="{{ __('Compartir en Instagram') }}"
        title="{{ __('Compartir en Instagram') }}"
    >
        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor" stroke="none"/></svg>
    </button>
</div>
