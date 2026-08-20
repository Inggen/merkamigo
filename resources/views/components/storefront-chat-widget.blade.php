@props(['business'])

@if ($business->canUseAiChatbot())
    <div
        x-data="{
            open: false,
            sending: false,
            history: [],
            messages: [{ role: 'assistant', text: '👋 ¿Qué quieres saber? Puedo contarte sobre productos, precios, horarios y más.' }],
            question: '',
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
                    const response = await fetch(@js(route('api.v1.plaza.negocios.chat', ['business' => $business->slug])), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                        body: JSON.stringify({ question, history: this.history }),
                    });

                    const { data } = response.ok ? await response.json() : { data: null };

                    this.messages.pop();

                    if (data?.answer) {
                        this.messages.push({ role: 'assistant', text: data.answer });
                        this.history.push({ role: 'user', content: question });
                        this.history.push({ role: 'assistant', content: data.answer });

                        while (this.history.length > 12) {
                            this.history.shift();
                        }
                    } else {
                        this.messages.push({ role: 'assistant', text: 'No pude responder en este momento. Escríbele directo al negocio para que te ayude.' });
                    }
                } catch {
                    this.messages.pop();
                    this.messages.push({ role: 'assistant', text: 'No pude responder en este momento. Escríbele directo al negocio para que te ayude.' });
                } finally {
                    this.sending = false;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        this.$refs.chatInput?.focus();
                    });
                }
            },
            scrollToBottom() {
                this.$refs.chatMessages.scrollTop = this.$refs.chatMessages.scrollHeight;
            },
        }"
        class="fixed right-4 bottom-24 z-40 md:right-6 md:bottom-6"
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
                    <div class="truncate text-sm font-semibold">{{ __('Pregúntale al negocio') }}</div>
                    <div class="truncate text-xs text-white/80">{{ $business->name }}</div>
                </div>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-white/90 transition hover:bg-white/15"
                    aria-label="{{ __('Cerrar chat') }}"
                >
                    <flux:icon.x-mark class="size-5" variant="outline" />
                </button>
            </div>

            <div x-ref="chatMessages" class="flex-1 space-y-2 overflow-y-auto px-4 py-3">
                <template x-for="(message, index) in messages" :key="index">
                    <div
                        class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-6"
                        :class="{
                            'ml-auto bg-brand-600 text-white': message.role === 'user',
                            'mr-auto bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200': message.role === 'assistant',
                            'italic opacity-60': message.typing,
                        }"
                        x-text="message.text"
                    ></div>
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

        <button
            type="button"
            x-on:click="open = ! open"
            class="flex size-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition hover:bg-brand-700"
            :aria-label="open ? '{{ __('Cerrar chat') }}' : '{{ __('Pregúntale al negocio') }}'"
        >
            <flux:icon.chat-bubble-left-right x-show="! open" class="size-6" variant="outline" />
            <flux:icon.x-mark x-show="open" x-cloak class="size-6" variant="outline" />
        </button>
    </div>
@endif
