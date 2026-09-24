<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'order_monitoring.scan_interval_minutes'],
            [
                'group' => 'order_monitoring',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Automatic overdue-order scan interval in minutes. Allowed values: 5, 10, 15, 30, or 60.',
                'is_secret' => false,
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'order_monitoring.scan_interval_minutes')->delete();
    }
};
