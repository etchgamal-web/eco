<?php

return [
    // Must stay below the queue retry_after window so a timed-out job is not duplicated.
    'job_timeout_seconds' => (int) env('OUTBOX_JOB_TIMEOUT_SECONDS', 120),
    'lease_minutes' => (int) env('OUTBOX_LEASE_MINUTES', 5),
    'max_attempts' => (int) env('OUTBOX_MAX_ATTEMPTS', 5),
    'retry_delay_minutes' => (int) env('OUTBOX_RETRY_DELAY_MINUTES', 5),
];
