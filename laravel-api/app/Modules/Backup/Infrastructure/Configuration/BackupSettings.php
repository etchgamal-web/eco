<?php

namespace App\Modules\Backup\Infrastructure\Configuration;

use App\Models\Setting;

final class BackupSettings
{
    public function enabled(): bool
    {
        return (bool) $this->value('backup.enabled', config('backup.enabled'));
    }

    public function schedule(): string
    {
        $value = (string) $this->value('backup.schedule', config('backup.schedule'));

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1
            ? $value
            : (string) config('backup.schedule', '02:00');
    }

    public function retentionDays(): int
    {
        return max(1, min(3650, (int) $this->value('backup.retention_days', config('backup.retention_days', 14))));
    }

    private function value(string $key, mixed $fallback): mixed
    {
        try {
            $setting = Setting::query()->where('key', $key)->first();

            return $setting?->getTypedValue() ?? $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
