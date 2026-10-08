<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(22rem,.8fr)]">
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">{{ __('Migraciones de base de datos') }}</x-slot>
                <x-slot name="description">{{ __('Selecciona únicamente los archivos que quieres ejecutar. La lista muestra primero los más recientes.') }}</x-slot>

                <div class="space-y-4">
                    @if ($migrationStatusError)
                        <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                            {{ $migrationStatusError }}
                        </div>
                    @elseif ($pendingMigrations === [] && $discardedMigrations === [])
                        <div class="flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300">
                            <x-filament::icon icon="heroicon-o-check-circle" class="size-6 shrink-0" />
                            <div>
                                <p class="font-semibold">{{ __('La base de datos está actualizada') }}</p>
                                <p class="text-sm opacity-80">{{ __('No hay migraciones pendientes.') }}</p>
                            </div>
                        </div>
                    @elseif ($pendingMigrations !== [])
                        <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3 text-warning-700 dark:text-warning-300">
                                    <x-filament::icon icon="heroicon-o-circle-stack" class="size-6 shrink-0" />
                                    <div>
                                        <p class="font-semibold">{{ trans_choice(':count migración pendiente|:count migraciones pendientes', count($pendingMigrations), ['count' => count($pendingMigrations)]) }}</p>
                                        <p class="text-xs opacity-80">{{ __('Más recientes primero') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" wire:click="selectAllMigrations" class="font-semibold text-primary-700 hover:underline dark:text-primary-300">
                                        {{ __('Seleccionar todas') }}
                                    </button>
                                    <span class="text-zinc-400">·</span>
                                    <button type="button" wire:click="clearMigrationSelection" class="font-semibold text-zinc-600 hover:underline dark:text-zinc-300">
                                        {{ __('Limpiar selección') }}
                                    </button>
                                </div>
                            </div>
                            <ul class="mt-4 max-h-80 space-y-2 overflow-y-auto text-xs text-zinc-700 dark:text-zinc-300">
                                @foreach ($pendingMigrations as $migration)
                                    <li wire:key="pending-migration-{{ $migration }}" class="flex items-center gap-3 rounded-lg bg-white/70 px-3 py-2.5 dark:bg-black/20">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedMigrations"
                                            value="{{ $migration }}"
                                            aria-label="{{ __('Seleccionar :migration', ['migration' => $migration]) }}"
                                            class="size-4 rounded border-zinc-300 text-primary-600 focus:ring-primary-600 dark:border-white/20 dark:bg-white/5"
                                        >
                                        <code class="min-w-0 flex-1 break-all">{{ $migration }}</code>
                                        <x-filament::icon-button
                                            wire:click="discardMigration('{{ $migration }}')"
                                            wire:confirm="{{ __('Esta migración dejará de aparecer como ejecutable en esta herramienta. No se marcará como ejecutada en Laravel. ¿Descartarla?') }}"
                                            icon="heroicon-o-eye-slash"
                                            color="gray"
                                            size="sm"
                                            :label="__('Descartar :migration', ['migration' => $migration])"
                                        />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($discardedMigrations !== [])
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-start gap-3">
                                <x-filament::icon icon="heroicon-o-eye-slash" class="mt-0.5 size-5 shrink-0 text-zinc-500" />
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ trans_choice(':count migración descartada|:count migraciones descartadas', count($discardedMigrations), ['count' => count($discardedMigrations)]) }}
                                    </p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                        {{ __('Solo se omiten desde esta herramienta. Un php artisan migrate ejecutado por otro medio todavía puede aplicarlas.') }}
                                    </p>
                                </div>
                            </div>
                            <ul class="mt-4 max-h-52 space-y-2 overflow-y-auto text-xs">
                                @foreach ($discardedMigrations as $migration)
                                    <li wire:key="discarded-migration-{{ $migration }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 dark:border-white/10 dark:bg-black/20">
                                        <code class="min-w-0 flex-1 break-all text-zinc-600 dark:text-zinc-300">{{ $migration }}</code>
                                        <x-filament::button wire:click="restoreMigration('{{ $migration }}')" size="xs" variant="outlined" color="gray">
                                            {{ __('Restaurar') }}
                                        </x-filament::button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <x-filament::button
                            wire:click="runSelectedMigrations"
                            wire:confirm="{{ __('Se ejecutarán únicamente las migraciones seleccionadas, desde la más antigua hasta la más reciente. Antes de continuar debes tener una copia de seguridad reciente. ¿Continuar?') }}"
                            wire:loading.attr="disabled"
                            wire:target="runSelectedMigrations"
                            icon="heroicon-o-play"
                            :disabled="$selectedMigrations === [] || filled($migrationStatusError)"
                        >
                            {{ __('Ejecutar seleccionadas (:count)', ['count' => count($selectedMigrations)]) }}
                        </x-filament::button>
                        <x-filament::button wire:click="refreshMigrationStatus" wire:loading.attr="disabled" variant="outlined" icon="heroicon-o-arrow-path">
                            {{ __('Actualizar estado') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('Comandos permitidos') }}</x-slot>
                <x-slot name="description">{{ __('Acciones frecuentes de despliegue. La herramienta no acepta comandos arbitrarios.') }}</x-slot>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($this->availableCommands() as $key => $task)
                        <div class="flex flex-col justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-white/10">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $task['label'] }}</p>
                                <p class="mt-1 text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $task['description'] }}</p>
                                <code class="mt-3 inline-flex rounded-lg bg-zinc-100 px-2.5 py-1.5 text-xs text-zinc-600 dark:bg-white/5 dark:text-zinc-300">php artisan {{ $task['command'] }}</code>
                            </div>
                            <x-filament::button
                                wire:click="runCommand('{{ $key }}')"
                                wire:confirm="{{ $task['confirmation'] }}"
                                wire:loading.attr="disabled"
                                wire:target="runCommand('{{ $key }}')"
                                size="sm"
                                variant="outlined"
                            >
                                {{ __('Ejecutar') }}
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">{{ __('Último resultado') }}</x-slot>
                @if ($lastCommandLabel)
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $lastCommandLabel }}</p>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $lastExitCode === 0 ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' }}">
                            {{ $lastExitCode === 0 ? __('Correcto') : __('Error :code', ['code' => $lastExitCode]) }}
                        </span>
                    </div>
                    <pre class="max-h-[32rem] overflow-auto whitespace-pre-wrap rounded-xl bg-zinc-950 p-4 text-xs leading-5 text-zinc-100">{{ $lastOutput }}</pre>
                @else
                    <div class="rounded-xl bg-zinc-50 p-5 text-center text-sm text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        {{ __('La salida del próximo comando aparecerá aquí.') }}
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('Actividad reciente') }}</x-slot>
                <div class="space-y-3">
                    @forelse ($this->recentExecutions() as $execution)
                        <div class="rounded-xl border border-zinc-200 p-3 text-sm dark:border-white/10">
                            <div class="flex items-center justify-between gap-3">
                                <code class="font-semibold text-zinc-900 dark:text-white">artisan {{ $execution->metadata['command'] ?? '' }}</code>
                                <span class="text-xs {{ ($execution->metadata['successful'] ?? false) ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ ($execution->metadata['successful'] ?? false) ? __('Correcto') : __('Error') }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">{{ $execution->user?->name }} · {{ $execution->created_at?->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Todavía no se han ejecutado tareas desde esta herramienta.') }}</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
