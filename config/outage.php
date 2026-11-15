<?php

return [
    'rate_limit_per_hour' => (int) env('OUTAGE_RATE_LIMIT_PER_HOUR', 5),
    'max_active_per_user' => (int) env('OUTAGE_MAX_ACTIVE_PER_USER', 3),
    'polling_interval_ms' => (int) env('MAP_POLLING_INTERVAL_MS', 30000),

    // Hard safety cap for reports with until_closed duration type.
    'until_closed_hard_cap_hours' => 72,
];
