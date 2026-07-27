@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#faf7f5] text-slate-900 antialiased">
        <div class="relative isolate overflow-hidden min-h-screen">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(227,52,47,0.12),_transparent_22%),radial-gradient(circle_at_bottom_right,_rgba(227,52,47,0.08),_transparent_18%),linear-gradient(180deg,_#fffdfc_0%,_#f8f4f2_100%)]"></div>
            <div class="pointer-events-none absolute left-[-6rem] top-16 h-64 w-64 rounded-full bg-[#f7cfc9]/30 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-10 right-10 h-72 w-72 rounded-full bg-[#fde7e3]/50 blur-3xl"></div>

            <main class="relative mx-auto flex min-h-screen w-full max-w-[1440px] items-center justify-center px-4 py-6 sm:px-6 lg:px-10">
                <div class="w-full overflow-hidden rounded-[2rem] border border-white/80 bg-white/80 shadow-[0_24px_80px_rgba(15,23,42,0.18)] backdrop-blur xl:rounded-[2.5rem]">
                    <div class="flex items-center justify-between bg-[#1f1f1f] px-5 py-4 text-white">
                        <div class="flex items-center gap-2.5">
                            <span class="size-3 rounded-full bg-[#ff5f56]"></span>
                            <span class="size-3 rounded-full bg-[#ffbd2f]"></span>
                            <span class="size-3 rounded-full bg-[#27c93f]"></span>
                        </div>

                        <div class="hidden items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-2 text-sm text-white/90 sm:flex">
                            <svg class="size-4 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="5" y="10" width="14" height="10" rx="2" stroke-linejoin="round"/>
                            </svg>
                            <span>admin.merkamigo.com</span>
                        </div>

                        <div class="flex items-center gap-4 text-white/70">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 12a9 9 0 1 1-2.64-6.36" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 3v6h-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="5" cy="12" r="1.8"/>
                                <circle cx="12" cy="12" r="1.8"/>
                                <circle cx="19" cy="12" r="1.8"/>
                            </svg>
                        </div>
                    </div>

                    <div class="grid min-h-[740px] gap-0 xl:grid-cols-[1.05fr_0.95fr]">
                        <section class="relative hidden overflow-hidden xl:flex">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(227,52,47,0.10),_transparent_20%),linear-gradient(180deg,_rgba(255,255,255,0.95)_0%,_rgba(255,250,248,0.92)_100%)]"></div>
                            <div class="absolute left-[-7rem] top-[-4rem] h-72 w-72 rounded-full border border-[#f6d8d2] bg-[#fbeceb]/60"></div>
                            <div class="absolute bottom-[-5rem] right-[-2rem] h-64 w-64 rounded-full border border-[#fde4df] bg-[#fff5f2]/80"></div>

                            <div class="relative flex w-full flex-col justify-between px-12 py-14">
                                <div class="space-y-10">
                                    <x-admin.auth.brand />

                                    <div class="max-w-xl space-y-5">
                                        <h1 class="text-6xl font-semibold leading-[0.95] tracking-[-0.04em] text-slate-800">
                                            Administración
                                            <span class="block text-[#e3342f]">Merkamigo</span>
                                        </h1>

                                        <p class="max-w-lg text-2xl leading-relaxed text-slate-600">
                                            Gestiona negocios, solicitudes y la comunidad local.
                                        </p>
                                    </div>
                                </div>

                                <x-admin.auth.storefront-illustration class="w-full text-[#ef7a72]" />
                            </div>
                        </section>

                        <section class="relative flex items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
                            <div class="w-full max-w-[34rem] rounded-[2rem] border border-[#f1e5e2] bg-white/95 p-6 shadow-[0_24px_60px_rgba(226,62,52,0.08)] sm:p-8 lg:p-10">
                                <div class="mb-8 space-y-5 xl:hidden">
                                    <x-admin.auth.brand compact />

                                    <div class="space-y-3">
                                        <h1 class="text-4xl font-semibold leading-none tracking-[-0.04em] text-slate-800 sm:text-5xl">
                                            Administración
                                            <span class="block text-[#e3342f]">Merkamigo</span>
                                        </h1>

                                        <p class="max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                                            Gestiona negocios, solicitudes y la comunidad local.
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    {{ $slot }}
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
