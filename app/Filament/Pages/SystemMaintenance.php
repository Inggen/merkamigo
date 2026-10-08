<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Platform\Models\AuditLog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class SystemMaintenance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Mantenimiento';

    protected static ?string $title = 'Mantenimiento del sistema';

    protected static ?string $slug = 'mantenimiento';

    protected string $view = 'filament.pages.system-maintenance';

    /** @var array<int, string> */
    public array $pendingMigrations = [];

    /** @var array<int, string> */
    public array $selectedMigrations = [];

    /** @var array<int, string> */
    public array $discardedMigrations = [];

    public ?string $migrationStatusError = null;

    public ?string $lastCommandLabel = null;

    public ?string $lastOutput = null;

    public ?int $lastExitCode = null;

    /**
     * Lista cerrada: nunca se recibe el nombre real de un comando desde
     * el navegador. Para agregar una tarea futura se registra aquí.
     *
     * @var array<string, array{label: string, description: string, command: string, arguments: array<string, mixed>, confirmation: string}>
     */
    private const COMMANDS = [
        'optimize-clear' => [
            'label' => 'Limpiar todas las cachés',
            'description' => 'Limpia configuración, rutas, vistas, eventos y caché de la aplicación.',
            'command' => 'optimize:clear',
            'arguments' => [],
            'confirmation' => '¿Limpiar todas las cachés de la aplicación?',
        ],
        'optimize' => [
            'label' => 'Optimizar aplicación',
            'description' => 'Regenera las cachés recomendadas de Laravel para producción.',
            'command' => 'optimize',
            'arguments' => [],
            'confirmation' => '¿Regenerar las cachés de producción?',
        ],
        'config-cache' => [
            'label' => 'Regenerar configuración',
            'description' => 'Vuelve a crear la caché de configuración.',
            'command' => 'config:cache',
            'arguments' => [],
            'confirmation' => '¿Regenerar la caché de configuración?',
        ],
        'route-cache' => [
            'label' => 'Regenerar rutas',
            'description' => 'Vuelve a crear la caché de rutas.',
            'command' => 'route:cache',
            'arguments' => [],
            'confirmation' => '¿Regenerar la caché de rutas?',
        ],
        'view-cache' => [
            'label' => 'Regenerar vistas',
            'description' => 'Compila nuevamente todas las vistas Blade.',
            'command' => 'view:cache',
            'arguments' => [],
            'confirmation' => '¿Regenerar la caché de vistas?',
        ],
        'queue-restart' => [
            'label' => 'Reiniciar workers de cola',
            'description' => 'Solicita un reinicio seguro de los workers después de terminar el trabajo actual.',
            'command' => 'queue:restart',
            'arguments' => [],
            'confirmation' => '¿Solicitar el reinicio de los workers de cola?',
        ],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false;
    }

    public function mount(): void
    {
        $this->refreshMigrationStatus();
    }

    /**
     * @return array<string, array{label: string, description: string, command: string, arguments: array<string, mixed>, confirmation: string}>
     */
    public function availableCommands(): array
    {
        return self::COMMANDS;
    }

    public function refreshMigrationStatus(): void
    {
        $this->authorizeMaintenance();
        $this->migrationStatusError = null;

        try {
            /** @var Migrator $migrator */
            $migrator = app(Migrator::class);
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $discarded = $this->discardedMigrationNames();
            $pending = collect(array_keys($files))
                ->reject(fn (string $migration): bool => in_array($migration, $ran, true));

            $this->pendingMigrations = $pending
                ->reject(fn (string $migration): bool => in_array($migration, $discarded, true))
                ->sortDesc()
                ->values()
                ->all();
            $this->discardedMigrations = $pending
                ->filter(fn (string $migration): bool => in_array($migration, $discarded, true))
                ->sortDesc()
                ->values()
                ->all();
            $this->selectedMigrations = array_values(array_intersect(
                $this->selectedMigrations,
                $this->pendingMigrations,
            ));
        } catch (Throwable $exception) {
            $this->pendingMigrations = [];
            $this->selectedMigrations = [];
            $this->discardedMigrations = [];
            $this->migrationStatusError = $exception->getMessage();
        }
    }

    public function selectAllMigrations(): void
    {
        $this->authorizeMaintenance();

        $this->selectedMigrations = $this->pendingMigrations;
    }

    public function clearMigrationSelection(): void
    {
        $this->authorizeMaintenance();

        $this->selectedMigrations = [];
    }

    public function runSelectedMigrations(): void
    {
        $this->authorizeMaintenance();
        $this->refreshMigrationStatus();

        if ($this->selectedMigrations === []) {
            Notification::make()
                ->title('Selecciona al menos una migración')
                ->warning()
                ->send();

            return;
        }

        /** @var Migrator $migrator */
        $migrator = app(Migrator::class);
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $selected = collect($this->selectedMigrations)
            ->filter(fn (string $migration): bool => isset($files[$migration]))
            ->sort()
            ->values();

        if ($selected->count() !== count($this->selectedMigrations)) {
            Notification::make()
                ->title('La selección contiene una migración no disponible')
                ->danger()
                ->send();

            return;
        }

        $this->executeArtisan(
            label: 'Ejecutar migraciones seleccionadas',
            command: 'migrate',
            arguments: [
                '--force' => true,
                '--path' => $selected->map(fn (string $migration): string => $files[$migration])->all(),
                '--realpath' => true,
            ],
            auditMetadata: ['migrations' => $selected->all()],
        );
        $this->refreshMigrationStatus();
    }

    public function discardMigration(string $migration): void
    {
        $this->authorizeMaintenance();
        $this->refreshMigrationStatus();
        abort_unless(in_array($migration, $this->pendingMigrations, true), 404);

        if (! Schema::hasTable('platform_ignored_migrations')) {
            Notification::make()
                ->title('Primero ejecuta la migración de soporte de esta herramienta')
                ->body('Selecciona y ejecuta create_platform_ignored_migrations_table.')
                ->warning()
                ->send();

            return;
        }

        DB::table('platform_ignored_migrations')->updateOrInsert(
            ['migration' => $migration],
            [
                'ignored_by_user_id' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->recordMigrationDecision('platform.migration.discarded', $migration);
        $this->refreshMigrationStatus();

        Notification::make()
            ->title('Migración descartada en esta herramienta')
            ->success()
            ->send();
    }

    public function restoreMigration(string $migration): void
    {
        $this->authorizeMaintenance();
        abort_unless(in_array($migration, $this->discardedMigrations, true), 404);

        if (Schema::hasTable('platform_ignored_migrations')) {
            DB::table('platform_ignored_migrations')->where('migration', $migration)->delete();
        }

        $this->recordMigrationDecision('platform.migration.restored', $migration);
        $this->refreshMigrationStatus();

        Notification::make()
            ->title('Migración restaurada')
            ->success()
            ->send();
    }

    public function runCommand(string $key): void
    {
        $this->authorizeMaintenance();
        abort_unless(array_key_exists($key, self::COMMANDS), 404);

        $task = self::COMMANDS[$key];
        $this->executeArtisan($task['label'], $task['command'], $task['arguments']);
        $this->refreshMigrationStatus();
    }

    /** @return Collection<int, AuditLog> */
    public function recentExecutions(): Collection
    {
        return AuditLog::query()
            ->with('user')
            ->where('action', 'platform.maintenance_command.executed')
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $auditMetadata
     */
    private function executeArtisan(string $label, string $command, array $arguments, array $auditMetadata = []): void
    {
        $lock = Cache::lock('platform-maintenance-artisan-command', 300);

        if (! $lock->get()) {
            Notification::make()
                ->title('Ya hay una tarea de mantenimiento en ejecución')
                ->warning()
                ->send();

            return;
        }

        $exitCode = 1;
        $output = '';

        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            $output = $exception->getMessage();
        } finally {
            $lock->release();
        }

        $this->lastCommandLabel = $label;
        $this->lastExitCode = $exitCode;
        $this->lastOutput = Str::limit($output !== '' ? $output : 'El comando terminó sin mensajes.', 50000);

        app(RecordAuditLog::class)->handle(
            auth()->user(),
            'platform.maintenance_command.executed',
            auth()->user(),
            [
                'command' => $command,
                'arguments' => array_keys($arguments),
                'exit_code' => $exitCode,
                'successful' => $exitCode === 0,
                ...$auditMetadata,
            ],
        );

        Notification::make()
            ->title($exitCode === 0 ? 'Tarea completada' : 'La tarea terminó con errores')
            ->body($label)
            ->color($exitCode === 0 ? 'success' : 'danger')
            ->send();
    }

    private function authorizeMaintenance(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return array<int, string> */
    private function discardedMigrationNames(): array
    {
        if (! Schema::hasTable('platform_ignored_migrations')) {
            return [];
        }

        return DB::table('platform_ignored_migrations')
            ->pluck('migration')
            ->all();
    }

    private function recordMigrationDecision(string $action, string $migration): void
    {
        app(RecordAuditLog::class)->handle(
            auth()->user(),
            $action,
            auth()->user(),
            ['migration' => $migration],
        );
    }
}
