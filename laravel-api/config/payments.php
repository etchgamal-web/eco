<?php

return [
    'refund_reconciliation_retry_minutes' => (int) env('REFUND_RECONCILIATION_RETRY_MINUTES', 15),
    'refund_ambiguous_alert_minutes' => (int) env('REFUND_AMBIGUOUS_ALERT_MINUTES', 60),
];
