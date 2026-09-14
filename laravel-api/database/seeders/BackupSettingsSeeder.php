<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class BackupSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'backup.enabled', 'value' => false, 'type' => 'boolean', 'description' => 'Enable scheduled database backups.'],
            ['key' => 'backup.schedule', 'value' => '02:00', 'type' => 'string', 'description' => 'Daily backup time in the server timezone (HH:MM).'],
            ['key' => 'backup.retention_days', 'value' => 14, 'type' => 'integer', 'description' => 'Number of days to retain backup files.'],
        ];

        foreach ($settings as $data) {
            $setting = Setting::query()->firstOrNew(['key' => $data['key']]);
            $setting->group = 'backup';
            $setting->type = $data['type'];
            $setting->description = $data['description'];
            $setting->is_secret = false;
            $setting->is_encrypted = false;
            if (! $setting->exists) {
                $setting->setTypedValue($data['value']);
            }
            $setting->save();
        }
    }
}
