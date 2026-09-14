<?php

return [
    'enabled' => (bool) env('BACKUP_ENABLED', false),
    'schedule' => env('BACKUP_SCHEDULE', '02:00'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'scripts' => [
        'pgsql' => base_path('scripts/backup_postgres.sh'),
        'mysql' => base_path('scripts/backup_mysql.sh'),
        'sqlite' => base_path('scripts/backup_sqlite.sh'),
    ],
];
