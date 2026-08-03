<div class="min-h-screen bg-[#faf7f5] font-sans text-slate-900">
    <div class="relative isolate overflow-hidden min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(227,52,47,0.12),_transparent_22%),radial-gradient(circle_at_bottom_right,_rgba(227,52,47,0.08),_transparent_18%),linear-gradient(180deg,_#fffdfc_0%,_#f8f4f2_100%)]">
        <div class="pointer-events-none absolute left-[-6rem] top-14 h-64 w-64 rounded-full bg-[#f7cfc9]/30 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-8 right-8 h-72 w-72 rounded-full bg-[#fde7e3]/50 blur-3xl"></div>

        <main class="mx-auto flex min-h-screen w-full max-w-[1440px] items-center justify-center px-4 py-6 sm:px-6 lg:px-10">
            <div class="grid w-full max-w-[1320px] rounded-2xl overflow-hidden bg-white/85 backdrop-blur xl:grid-cols-[1.05fr_0.95fr]">
                <section class="relative hidden overflow-hidden xl:flex">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(227,52,47,0.10),_transparent_20%),linear-gradient(180deg,_rgba(255,255,255,0.96)_0%,_rgba(255,250,248,0.92)_100%)]"></div>
                    <div class="absolute left-[-7rem] top-[-4rem] h-72 w-72 rounded-full border border-[#f6d8d2] bg-[#fbeceb]/60"></div>
                    <div class="absolute bottom-[-5rem] right-[-2rem] h-64 w-64 rounded-full border border-[#fde4df] bg-[#fff5f2]/80"></div>

                    <div class="relative flex w-full flex-col justify-between px-12 py-14">
                        <div class="space-y-10">
                            <x-admin.auth.brand />
                        </div>

                        <img
                            src="{{ asset('images/fondo-login-admin.svg') }}"
                            alt="Fondo ilustrado del acceso administrativo"
                            class="w-full rounded-2xl object-contain" style="opacity:40%"
                        >
                    </div>
                </section>

                <section class="relative flex items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
                    <div class="w-full max-w-[34rem] rounded-2xl border border-[#f1e5e2] bg-white/95 p-6 shadow-[0_24px_60px_rgba(226,62,52,0.08)] sm:p-8 lg:p-10">
                        <div class="mb-8 space-y-5 xl:hidden">
                            <x-admin.auth.brand compact />
                        </div>

                        @if (filled($this->userUndertakingMultiFactorAuthentication))
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#e3342f]">
                                        Seguridad
                                    </p>

                                    <h2 class="text-3xl font-semibold tracking-[-0.04em] text-slate-900">
                                        Verificación en dos pasos
                                    </h2>

                                    <p class="max-w-md text-base leading-7 text-slate-500">
                                        Completa el método de verificación configurado para continuar al panel.
                                    </p>
                                </div>

                                <div class="[&_.fi-form-actions]:mt-6 [&_.fi-input-wrp]:rounded-2xl [&_.fi-input-wrp]:border-slate-200 [&_.fi-input-wrp]:bg-white [&_.fi-input-wrp]:shadow-sm [&_.fi-section]:rounded-2xl [&_.fi-section]:border-slate-100 [&_.fi-section]:bg-slate-50/80 [&_.fi-btn]:rounded-2xl [&_.fi-btn]:bg-[#e3342f] [&_.fi-btn]:py-3 [&_.fi-btn]:text-base [&_.fi-btn:hover]:bg-[#cf2f2a]">
                                    {{ $this->content }}
                                </div>
                            </div>
                        @else
                            <div class="space-y-6" x-data="{ revealPassword: false }">
                                <div class="space-y-2">
                                    <h2 class="text-3xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-[2.2rem]">
                                        Administración <span class="text-[#e3342f]">Merkamigo</span>
                                    </h2>

                                    <p class="max-w-md text-base leading-7 text-slate-500">
                                        Gestiona negocios, solicitudes y la comunidad local.
                                    </p>
                                </div>

                                <form wire:submit="authenticate" class="space-y-5">
                                    <div class="space-y-2">
                                        <label for="admin-email" class="block text-sm font-medium text-slate-700">
                                            Correo electrónico
                                        </label>

                                        <div class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition focus-within:border-[#e3342f] focus-within:ring-4 focus-within:ring-[#e3342f]/10">
                                            <svg class="size-5 shrink-0 text-slate-400 group-focus-within:text-[#e3342f]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke-linejoin="round"/>
                                                <path d="m5 8 7 5 7-5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>

                                            <input
                                                id="admin-email"
                                                type="text"
                                                wire:model.blur="data.email"
                                                autocomplete="username"
                                                placeholder="admin@merkamigo.com"
                                                class="w-full border-0 bg-transparent p-0 text-base text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                            >
                                        </div>

                                        @error('data.email')
                                            <p class="text-sm font-medium text-[#e3342f]">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label for="admin-password" class="block text-sm font-medium text-slate-700">
                                            Contraseña
                                        </label>

                                        <div class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition focus-within:border-[#e3342f] focus-within:ring-4 focus-within:ring-[#e3342f]/10">
                                            <svg class="size-5 shrink-0 text-slate-400 group-focus-within:text-[#e3342f]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <rect x="5" y="10" width="14" height="10" rx="2" stroke-linejoin="round"/>
                                            </svg>

                                            <input
                                                id="admin-password"
                                                x-bind:type="revealPassword ? 'text' : 'password'"
                                                wire:model.blur="data.password"
                                                autocomplete="current-password"
                                                placeholder="••••••••"
                                                class="w-full border-0 bg-transparent p-0 text-base text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                            >

                                            <button
                                                type="button"
                                                x-on:click="revealPassword = ! revealPassword"
                                                class="inline-flex shrink-0 text-slate-400 transition hover:text-[#e3342f]"
                                            >
                                                <svg x-show="! revealPassword" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                <svg x-cloak x-show="revealPassword" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="m3 3 18 18" stroke-linecap="round"/>
                                                    <path d="M10.6 10.7A3 3 0 0 0 13.3 13.4" stroke-linecap="round"/>
                                                    <path d="M9.9 5.1A11.4 11.4 0 0 1 12 5c6 0 9.5 7 9.5 7a15.1 15.1 0 0 1-3.1 3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M6.2 6.3C4.1 7.8 2.5 10 2.5 12c0 0 3.5 6 9.5 6 1.7 0 3.2-.5 4.5-1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </div>

                                        @error('data.password')
                                            <p class="text-sm font-medium text-[#e3342f]">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
                                        <label class="inline-flex items-center gap-3 text-sm text-slate-600">
                                            <input
                                                type="checkbox"
                                                wire:model.live="data.remember"
                                                class="size-4 rounded border-slate-300 text-[#e3342f] focus:ring-[#e3342f]/25"
                                            >
                                            <span>Recordarme</span>
                                        </label>

                                        @if ($this->getPasswordResetUrl())
                                            <a href="{{ $this->getPasswordResetUrl() }}" class="text-sm font-medium text-[#e3342f] transition hover:text-[#c92f2a]">
                                                ¿Olvidaste tu contraseña?
                                            </a>
                                        @endif
                                    </div>

                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-[#e3342f] px-5 py-4 text-lg font-semibold text-white shadow-[0_14px_30px_rgba(227,52,47,0.28)] transition hover:bg-[#cf2f2a] focus:outline-none focus:ring-4 focus:ring-[#e3342f]/20 disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        Ingresar al panel
                                    </button>
                                </form>

                                <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-4 sm:px-5">
                                    <div class="flex items-start gap-4">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 3 5 6v5c0 4.6 2.6 8.9 7 11 4.4-2.1 7-6.4 7-11V6l-7-3Z" stroke-linejoin="round"/>
                                                <path d="M12 10v4" stroke-linecap="round"/>
                                                <circle cx="12" cy="16.5" r=".75" fill="currentColor" stroke="none"/>
                                            </svg>
                                        </div>

                                        <div class="space-y-1">
                                            <p class="text-base font-medium text-slate-800">
                                                Acceso exclusivo para administradores autorizados.
                                            </p>
                                            <p class="text-sm leading-6 text-slate-500">
                                                Usa tus credenciales internas para gestionar la operación y las solicitudes de la plataforma.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-6 text-center">
                                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-medium text-slate-600 transition hover:text-[#e3342f]">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Volver a Merkamigo
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
