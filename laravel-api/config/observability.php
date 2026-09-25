<?php

return [
    'metrics_enabled' => (bool) env('METRICS_ENABLED', false),
    'metrics_token' => (string) env('METRICS_TOKEN', ''),
    'metrics_connection' => (string) env('METRICS_REDIS_CONNECTION', 'default'),
];
