<?php

namespace App\Console\Commands;

use App\Modules\Backup\Infrastructure\Configuration\BackupSettings;
use Illuminate\Console\Command;

final class VerifyBackup extends Command
{
    protected $signature = 'backup:verify
        {--max-age= : Maximum allowed age in hours; defaults to twice the scheduled interval or 48 hours}';

    protected $description = 'Verify that a recent database backup and checksum are available';

    public function handle(BackupSettings $settings): int
    {
        $directory = trim((string) env('BACKUP_DIR', ''));
        if ($directory === '' || ! is_dir($directory)) {
            $this->components->error('BACKUP_DIR is missing or does not exist.');

            return self::FAILURE;
        }

        $files = glob($directory.'/*.{dump,sql.gz,sqlite}', GLOB_BRACE) ?: [];
        if ($files === []) {
            $this->components->error('No database backup files were found.');

            return self::FAILURE;
        }
        usort($files, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
        $latest = $files[0];
        $maxAge = max(1, (int) ($this->option('max-age') ?: 48));
        $ageHours = (time() - filemtime($latest)) / 3600;
        $checksum = $latest.'.sha256';

        if (! is_file($checksum)) {
            $this->components->error("Checksum is missing for {$latest}.");

            return self::FAILURE;
        }
        if (! hash_equals(trim((string) file_get_contents($checksum)), trim(hash_file('sha256', $latest).'  '.$latest))) {
            $this->components->error("Checksum verification failed for {$latest}.");

            return self::FAILURE;
        }
        if ($ageHours > $maxAge) {
            $this->components->error(sprintf('Latest backup is %.1f hours old; maximum is %d hours.', $ageHours, $maxAge));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Backup is healthy: %s (%.1f hours old).', $latest, $ageHours));

        return self::SUCCESS;
    }
}
