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

            $this->pendingMigrations = collect(array_keys($files))
                ->reject(fn (string $migration): bool => in_array($migration, $ran, true))
                ->values()
                ->all();
        } catch (Throwable $exception) {
            $this->pendingMigrations = [];
            $this->migrationStatusError = $exception->getMessage();
        }
    }

    public function runMigrations(): void
    {
        $this->authorizeMaintenance();
        $this->executeArtisan(
            label: 'Ejecutar migraciones pendientes',
            command: 'migrate',
            arguments: ['--force' => true],
        );
        $this->refreshMigrationStatus();
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

    /** @param array<string, mixed> $arguments */
    private function executeArtisan(string $label, string $command, array $arguments): void
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
}
