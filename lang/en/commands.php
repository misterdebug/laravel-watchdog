<?php

return [
    'analyze' => [
        'description' => 'Analyzes suspicious login attempts and alerts on Discord',
        'disabled' => 'Login attempt monitoring is disabled.',
        'no_activity' => 'No suspicious activity detected.',
        'sent' => 'Alert sent to Discord.',
    ],

    'prune' => [
        'description' => 'Prunes old Laravel Watchdog records',
        'model_deleted' => ':model: :count deleted.|:model: :count deleted.',
        'total_deleted' => ':count Watchdog record deleted.|:count Watchdog records deleted.',
    ],

    'daily_security_report' => [
        'description' => 'Sends a daily report of 404 and 422 counts to Discord',
        'disabled' => 'Security monitoring is disabled.',
        'no_activity' => 'No activity to report.',
        'sent' => 'Report sent to Discord.',
    ],
];
