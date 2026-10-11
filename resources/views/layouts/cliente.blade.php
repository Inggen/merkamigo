@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'robots' => null,
    'pageSchemaType' => 'WebPage',
    'pageSchemaData' => [],
    'schemaGraph' => [],
    'ogType' => 'website',
    'showChatWidget' => true,
    // Conservado para compatibilidad con vistas existentes. La navegación
    // del cliente ahora vive en el encabezado y el contenido usa todo el ancho.
    'showSidebar' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => $canonical,
            'robots' => $robots,
            'pageSchemaType' => $pageSchemaType,
            'pageSchemaData' => $pageSchemaData,
            'schemaGraph' => $schemaGraph,
            'ogType' => $ogType,
        ])
    </head>
    <body class="min-h-screen bg-mist dark:bg-zinc-900 dark:text-white">
        <x-cliente-nav />

        {{--
            Paridad con `layouts/app/sidebar.blade.php` (2026-10-06): un
            cliente ahora también puede ver esta pantalla mientras un
            superadmin lo está impersonando para soporte — sin este banner
            perdería la única forma de saber que está en ese modo y de
            salir de él.
        --}}
        @if (session(\App\Domain\Platform\Actions\StartUserImpersonation::SESSION_KEY))
            <div class="w-full border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Estás usando la cuenta de <strong>{{ auth()->user()->name }}</strong> como soporte.
                        Volverás a <strong>{{ data_get(session(\App\Domain\Platform\Actions\StartUserImpersonation::SESSION_KEY), 'impersonator_name', 'tu cuenta de superadmin') }}</strong> cuando cierres este modo.
                    </div>

                    <form method="POST" action="{{ route('impersonation.stop') }}">
                        @csrf
                        <flux:button type="submit" variant="filled" size="sm">
                            Volver a mi cuenta
                        </flux:button>
                    </form>
                </div>
            </div>
        @endif

        <main>
            {{ $slot }}
        </main>

        @include('partials.public-footer')

        {{--
            Pedido del usuario (2026-10-11): la barra inferior también
            debe verse en móvil para un visitante que todavía no inició
            sesión — antes solo se mostraba con @auth, así que un
            invitado perdía la navegación principal. Se oculta solo para
            un usuario autenticado con una experiencia distinta a
            "cliente" (p. ej. un dueño de negocio viendo el sitio
            público como él mismo, que tiene su propia navegación).

            `ClientBottomNav::visible()` es la única fuente de verdad de
            esta condición — el chatbot de abajo la usa también para
            saber si debe levantarse por encima de la barra; tenerla
            duplicada fue justo lo que rompió el chatbot para invitados
            la primera vez que se arregló esto acá.
        --}}
        @if (\App\Support\Navigation\ClientBottomNav::visible())
            <div
                data-mobile-bottom-nav-reserve
                class="h-[calc(4rem+env(safe-area-inset-bottom))] md:hidden"
                aria-hidden="true"
            ></div>

            <x-cliente-bottom-nav />
        @endif

        @if ($showChatWidget)
            <x-storefront-chat-widget
                :with-sound="false"
                character-gif="images/chatbot-merkamiga-IA.gif"
                character-frame1="images/chatbot-merkamiga-IA-frame1.png"
                :above-mobile-bottom-nav="\App\Support\Navigation\ClientBottomNav::visible()"
            />
        @endif

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @stack('scripts')
        @fluxScripts
    </body>
</html>
