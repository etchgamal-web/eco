<?php

namespace App\Console\Commands;

use App\Modules\Backup\Infrastructure\Configuration\BackupSettings;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--force : Run even when scheduled backups are disabled}
        {--dry-run : Validate configuration without creating a backup}';

    protected $aliases = ['backup:postgres'];

    protected $description = 'Create a backup for the configured SQLite, MySQL, or PostgreSQL database';

    public function handle(BackupSettings $settings): int
    {
        if (! $this->option('force') && ! $settings->enabled()) {
            $this->components->warn('Backup is disabled. Enable backup.enabled in settings or use --force.');

            return self::SUCCESS;
        }

        $driver = (string) config('database.default');
        if (! in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            $this->components->error("Unsupported database driver: {$driver}. Use sqlite, mysql, or pgsql.");

            return self::FAILURE;
        }

        $script = (string) config("backup.scripts.{$driver}");
        if (! is_file($script) || ! is_executable($script)) {
            $this->components->error("Backup script is missing or not executable: {$script}");

            return self::FAILURE;
        }

        $required = match ($driver) {
            'sqlite' => ['BACKUP_DIR', 'DB_DATABASE'],
            default => ['BACKUP_DIR', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'],
        };
        foreach ($required as $name) {
            if (trim((string) env($name, '')) === '') {
                $this->components->error("{$name} is required for {$driver} backup.");

                return self::FAILURE;
            }
        }

        $environment = array_merge(getenv() ?: [], [
            'BACKUP_RETENTION_DAYS' => (string) $settings->retentionDays(),
            'DB_CONNECTION' => $driver,
        ]);

        if ($this->option('dry-run')) {
            $this->components->info("{$driver} backup configuration is valid. No backup was created.");

            return self::SUCCESS;
        }

        $process = new Process(['bash', $script], base_path(), $environment);
        $process->setTimeout(3600);
        $process->run(function (string $type, string $buffer): void {
            $type === Process::ERR ? $this->output->writeError($buffer) : $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->components->error("{$driver} backup failed. Check the command output and application logs.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
