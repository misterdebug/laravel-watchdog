<?php

return [

    // Enable or disable each feature independently
    'features' => [
        'stats' => true,
        'activity' => true,
        'security_404' => true,
        'security_422' => true,
        'security_500' => true,
        'login_attempts' => true,
        'sent_emails' => true,
    ],

    'discord' => [
        // Blocks sending outside the production environment
        'only_production' => true,

        'webhooks' => [
            'logins' => env('DISCORD_WEBHOOK_LOGINS'),
            'errors500' => env('DISCORD_WEBHOOK_ERRORS'),
            'security-logs' => env('DISCORD_WEBHOOK_SECURITY_LOGS'),
        ],
    ],

    // Notification classes used by the package.
    // You can replace them with your own classes here.
    'notifications' => [
        'login' => \MrDebug\LaravelWatchdog\Notifications\LoginNotification::class,
        'error500' => \MrDebug\LaravelWatchdog\Notifications\Error500Notification::class,
        'suspicious_activity' => \MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification::class,
        'daily_security_report' => \MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification::class,
    ],

    // Determines how to identify an admin user
    'admin_check' => [
        'field' => 'role',
        'value' => 'admin',
    ],

    // Thresholds for suspicious activity detection (login_attempts)
    'suspicious_activity' => [
        'window_minutes' => 60,
        'threshold_per_email' => 5,
        'threshold_per_ip' => 5,
    ],

    // Rate limit on notifications, to avoid flooding Discord
    'rate_limit' => [
        'error500' => [
            'max_attempts' => 5,
            'decay_seconds' => 3600,
        ],
    ],

    // Log retention in days, used by the Prunable models
    'prune' => [
        'stats_logs' => 30,
        'activity_logs' => 90,
        'security_logs' => 90,
        'login_attempts' => 90,
        'sent_emails' => 30,
    ],

    // Fields excluded from traffic logs (stats_logs / activity_logs)
    'traffic_excluded_params' => ['password', 'password_confirmation', '_token'],

    // Fields excluded from security logs (404 / 422). Empty by default, on purpose.
    'security_excluded_params' => [],

    'sent_emails' => [
        'mode' => 'all',
        'excluded_mailables' => [],
    ],

];