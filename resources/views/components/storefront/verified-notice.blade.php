@props(['business'])

<section
    data-verified-notice
    class="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-teal-50/70 to-emerald-50 p-5 shadow-sm dark:border-emerald-500/30 dark:from-emerald-950/40 dark:via-teal-950/25 dark:to-emerald-950/40 sm:p-6"
    aria-labelledby="verified-storefront-title"
>
    <div class="flex items-center gap-4 sm:gap-6">
        <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm sm:size-20">
            <flux:icon.shield-check class="size-10 sm:size-12" variant="solid" />
        </div>

        <div class="min-w-0 border-l border-emerald-200 pl-4 sm:pl-6 dark:border-emerald-500/30">
            <h2 id="verified-storefront-title" class="text-lg font-bold text-emerald-950 dark:text-emerald-100 sm:text-xl">
                {{ __('Vitrina verificada') }}
            </h2>
            <p class="mt-1 text-sm leading-6 text-emerald-900/80 dark:text-emerald-100/75">
                {{ __('La identidad y documentos de este negocio fueron revisados por Merkamigo.') }}
                <span class="block">{{ __('No implica garantía de calidad, pago ni entrega por parte de Merkamigo.') }}</span>
            </p>
            @if (($confirmedOrdersCount = $business->confirmedOrdersCount()) > 0)
                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-200 dark:ring-emerald-500/30">
                    <flux:icon.check-circle class="size-3.5" variant="solid" />
                    {{ trans_choice(':count pedido confirmado|:count pedidos confirmados', $confirmedOrdersCount, ['count' => $confirmedOrdersCount]) }}
                </span>
            @endif
        </div>
    </div>
</section>
