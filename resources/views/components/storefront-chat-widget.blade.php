@props([
    'business' => null,
    'withSound' => true,
    'characterGif' => 'images/chatbot-merkamigo-IA.gif',
    'characterFrame1' => 'images/chatbot-merkamigo-IA-frame1.png',
    'mode' => null,
])

{{--
    El personaje queda quieto en el primer fotograma por defecto y, de vez
    en cuando, reproduce la animación completa (GIF), con sonido salvo que
    $withSound sea false (pedido del usuario: sin sonido en el inicio). Un
    <img> normal no permite pausar un GIF en su primer frame, así que se
    logra cambiando el src entre una imagen estática (ese primer frame,
    exportado aparte) y el GIF animado; el cache-bust (?t=) es necesario
    porque reasignar el mismo src no reinicia la animación en la mayoría
    de navegadores.

    Sin $business (página de inicio, sin un negocio puntual al que
    preguntarle) es el asistente general de la plataforma
    (`AnswerPlatformChatQuestion`); con $business es el chat de esa
    vitrina de siempre, solo visible si tiene el chatbot habilitado.

    `history` (lo que se le manda al modelo) y `messages` (lo que se ve en
    pantalla) quedan en localStorage por hilo de conversación — separado
    por negocio si es el chat de una vitrina, o por modo (general/
    emprendedor) si es el asistente general — así que navegar entre
    páginas del mismo contexto no reinicia la conversación, aunque el
    componente se vuelva a montar en cada carga completa de página.

    IMPORTANTE: nunca escribas comillas dobles (ni siquiera dentro de un
    comentario) en ningún punto del atributo `x-data="{...}"` de abajo —
    HTML corta el atributo ahí mismo y el resto de la etiqueta se renderiza
    como texto plano visible en la página (ya pasó tres veces en este
    archivo). Cualquier explicación con comillas va aquí arriba, en este
    comentario Blade, nunca dentro de `x-data`.
--}}
@if (! $business || $business->canUseAiChatbot())
    <div
        x-data="{
            open: false,
            sending: false,
            history: [],
            mode: @js($mode),
            pageName: @js(request()->route()?->getName()),
            pageStep: null,
            storageKey: @js($business ? 'merkamigo_chat_business_'.$business->slug : 'merkamigo_chat_'.($mode ?: 'general')),
            defaultMessage: @js(match (true) {
                (bool) $business => __('👋 ¿Qué quieres saber? Puedo contarte sobre productos, precios, horarios y más.'),
                $mode === 'emprendedor' => __('👋 ¿En qué te ayudo con tu panel? Puedo llevarte a cualquier sección o ayudarte a crear tu vitrina.'),
                default => __('👋 ¿Qué quieres saber? Puedo ayudarte a encontrar negocios, resolver dudas sobre Merkamigo y más.'),
            }),
            messages: [],
            question: '',
            fallbackErrorText: @js($business
                ? __('No pude responder en este momento. Escríbele directo al negocio para que te ayude.')
                : __('No pude responder en este momento. Intenta de nuevo en un momento.')),
            loadFromStorage() {
                try {
                    const saved = JSON.parse(localStorage.getItem(this.storageKey) || 'null');

                    if (saved?.messages?.length) {
                        this.messages = saved.messages;
                        this.history = saved.history ?? [];

                        return;
                    }
                } catch {
                    // localStorage corrupto o inaccesible (modo privado) — se
                    // sigue con una conversación nueva, sin bloquear el chat.
                }

                this.messages = [{ role: 'assistant', text: this.defaultMessage }];
            },
            saveToStorage() {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify({
                        messages: this.messages.slice(-20),
                        history: this.history,
                    }));
                } catch {
                    //
                }
            },
            newConversation() {
                localStorage.removeItem(this.storageKey);
                this.history = [];
                this.messages = [{ role: 'assistant', text: this.defaultMessage }];
            },
            async send() {
                const question = this.question.trim();

                if (! question || this.sending) {
                    return;
                }

                this.sending = true;
                this.question = '';
                this.messages.push({ role: 'user', text: question });
                this.messages.push({ role: 'assistant', text: 'Escribiendo…', typing: true });
                this.$nextTick(() => this.scrollToBottom());

                try {
                    // Sanctum autentica /api/* por cookie de sesión para
                    // este mismo dominio (`statefulApi()`) — eso exige
                    // mandar el token CSRF de la cookie `XSRF-TOKEN` a
                    // mano, porque `fetch` (a diferencia de Axios) no lo
                    // hace solo.
                    const xsrfToken = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');

                    const response = await fetch(@js($business
                        ? route('api.v1.plaza.negocios.chat', ['business' => $business->slug])
                        : route('api.v1.asistente.chat')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            question,
                            history: this.history,
                            ...(this.mode ? { mode: this.mode } : {}),
                            ...(this.mode && this.pageName ? { pagina_actual: this.pageName } : {}),
                            ...(this.mode && this.pageStep ? { paso_actual: this.pageStep } : {}),
                        }),
                    });

                    const { data } = response.ok ? await response.json() : { data: null };

                    this.messages.pop();

                    if (data?.answer) {
                        this.messages.push({ role: 'assistant', text: data.answer, action: data.action ?? null });
                        this.history.push({ role: 'user', content: question });
                        this.history.push({ role: 'assistant', content: data.answer });

                        while (this.history.length > 12) {
                            this.history.shift();
                        }
                    } else {
                        this.messages.push({ role: 'assistant', text: this.fallbackErrorText });
                    }
                } catch {
                    this.messages.pop();
                    this.messages.push({ role: 'assistant', text: this.fallbackErrorText });
                } finally {
                    this.sending = false;
                    this.saveToStorage();
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        this.$refs.chatInput?.focus();
                    });
                }
            },
            scrollToBottom() {
                this.$refs.chatMessages.scrollTop = this.$refs.chatMessages.scrollHeight;
            },
            bubbleMessages: [
                @js(__('¿Te ayudo?')),
                @js(__('Pregúntame algo')),
                @js(__('¿Tienes dudas?')),
                @js(__('Escríbeme por aquí')),
            ],
            bubbleText: '',
            characterStaticSrc: @js(asset($characterFrame1)),
            characterAnimatedSrc: @js(asset($characterGif)),
            characterSoundSrc: @js($withSound ? asset('sounds/48caea41-4829-4219-a830-ad1b7024a268.mp3') : null),
            characterSrc: '',
            isAnimating: false,
            scheduleIdleAnimation() {
                setTimeout(() => {
                    if (this.open) {
                        this.scheduleIdleAnimation();

                        return;
                    }

                    this.playIdleAnimation();
                }, 7000 + Math.random() * 9000);
            },
            playIdleAnimation() {
                this.characterSrc = `${this.characterAnimatedSrc}?t=${Date.now()}`;
                this.isAnimating = true;

                let reverted = false;
                const revert = () => {
                    if (reverted) return;
                    reverted = true;
                    this.characterSrc = this.characterStaticSrc;
                    this.isAnimating = false;
                    this.scheduleIdleAnimation();
                };

                if (! this.characterSoundSrc) {
                    setTimeout(revert, 3500);

                    return;
                }

                const audio = new Audio(this.characterSoundSrc);
                audio.addEventListener('ended', revert);
                audio.addEventListener('error', () => setTimeout(revert, 3000));

                const playPromise = audio.play();
                if (playPromise?.catch) {
                    playPromise.catch(() => setTimeout(revert, 3000));
                }
            },
        }"
        x-init="
            bubbleText = bubbleMessages[Math.floor(Math.random() * bubbleMessages.length)];
            characterSrc = characterStaticSrc;
            scheduleIdleAnimation();
            loadFromStorage();
        "
        x-on:wizard-step-changed.window="pageStep = $event.detail.label ?? null"
        class="fixed right-4 bottom-0 z-40 md:right-6 md:bottom-0"
    >
        <div
            x-show="open"
            x-transition
            x-cloak
            x-trap.noscroll.inert="open"
            x-on:keydown.escape.window="open = false"
            class="mb-3 flex h-[28rem] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-brand-600 px-4 py-3 text-white dark:border-zinc-700">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">{{ __('Pregúntale a Merkamigo') }}</div>
                    <div class="truncate text-xs text-white/80">{{ $business?->name ?? __('Tu guía en Merkamigo') }}</div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        x-on:click="newConversation()"
                        class="inline-flex size-8 items-center justify-center rounded-full text-white/90 transition hover:bg-white/15"
                        title="{{ __('Nueva conversación') }}"
                        aria-label="{{ __('Nueva conversación') }}"
                    >
                        <flux:icon.pencil-square class="size-4" variant="outline" />
                    </button>
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="inline-flex size-8 items-center justify-center rounded-full text-white/90 transition hover:bg-white/15"
                        aria-label="{{ __('Cerrar chat') }}"
                    >
                        <flux:icon.x-mark class="size-5" variant="outline" />
                    </button>
                </div>
            </div>

            <div x-ref="chatMessages" class="flex-1 space-y-2 overflow-y-auto px-4 py-3">
                <template x-for="(message, index) in messages" :key="index">
                    <div class="max-w-[85%]" :class="message.role === 'user' ? 'ml-auto' : 'mr-auto'">
                        <div
                            class="rounded-2xl px-3 py-2 text-sm leading-6"
                            :class="{
                                'bg-brand-600 text-white': message.role === 'user',
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200': message.role === 'assistant',
                                'italic opacity-60': message.typing,
                            }"
                            x-text="message.text"
                        ></div>

                        <template x-if="message.action?.url">
                            <a
                                :href="message.action.url"
                                class="mt-1.5 inline-flex items-center gap-1 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                x-text="message.action.label"
                            ></a>
                        </template>
                    </div>
                </template>
            </div>

            <form x-on:submit.prevent="send" class="flex items-center gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <input
                    x-ref="chatInput"
                    x-model="question"
                    type="text"
                    placeholder="{{ __('Escribe tu pregunta…') }}"
                    autocomplete="off"
                    :disabled="sending"
                    class="min-w-0 flex-1 rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-brand-400 focus:ring-brand-400 disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                >
                <button
                    type="submit"
                    :disabled="sending || ! question.trim()"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white transition hover:bg-brand-700 disabled:opacity-60"
                    aria-label="{{ __('Enviar pregunta') }}"
                >
                    <flux:icon.paper-airplane class="size-4" variant="outline" />
                </button>
            </form>
        </div>

        <div x-show="! open" class="mb-1 flex justify-end">
            <div
                class="rounded-2xl rounded-br-sm bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-md transition dark:bg-zinc-800 dark:text-zinc-200"
                :class="isAnimating ? 'ring-2 ring-brand-400 shadow-brand-300/50 scale-105' : ''"
                x-text="bubbleText"
            ></div>
        </div>

        <button
            type="button"
            x-on:click="open = ! open"
            class="relative block ml-auto"
            :aria-label="open ? '{{ __('Cerrar chat') }}' : '{{ __('Pregúntale a Merkamigo') }}'"
        >
            <span
                x-show="! open && isAnimating"
                x-cloak
                x-transition
                class="pointer-events-none absolute inset-0 -z-10 animate-ping rounded-full bg-brand-400/50"
            ></span>

            <img
                :src="characterSrc"
                alt="{{ __('Chatbot de Merkamigo') }}"
                x-show="! open"
                class="h-[90px] w-auto drop-shadow-lg transition hover:scale-105"
            >
            <span
                x-show="open"
                x-cloak
                class="flex size-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition hover:bg-brand-700"
            >
                <flux:icon.x-mark class="size-6" variant="outline" />
            </span>
        </button>
    </div>
@endif
