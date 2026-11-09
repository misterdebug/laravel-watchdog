<?php

declare(strict_types=1);

use MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification;
use MrDebug\LaravelWatchdog\Notifications\Error500Notification;
use MrDebug\LaravelWatchdog\Notifications\LoginNotification;
use MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification;
use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;

/*
 * Complete configuration file, as it would look after:
 *
 *     php artisan vendor:publish --tag=laravel-watchdog-config
 *
 * Test values are used for the Discord webhooks so notifications can be
 * asserted with Http::fake().
 */
return [
    'features' => [
        'stats' => true,
        'activity' => true,
        'security_404' => true,
        'security_422' => true,
        'error500_alert' => true,
        'login_attempts' => true,
        'sent_emails' => true,
    ],

    'discord' => [
        'only_production' => false,
        'webhooks' => [
            'logins' => 'https://discord.test/webhooks/logins',
            'errors500' => 'https://discord.test/webhooks/errors500',
            'security-logs' => 'https://discord.test/webhooks/security-logs',
        ],
    ],

    'notifications' => [
        'login' => LoginNotification::class,
        'error500' => Error500Notification::class,
        'suspicious_activity' => SuspiciousActivityNotification::class,
        'daily_security_report' => DailySecurityReportNotification::class,
    ],

    'admin_check' => [
        'checker' => RoleAdminChecker::class,
        'field' => 'role',
        'values' => ['admin', 'super-admin'],
    ],

    'suspicious_activity' => [
        'window_minutes' => 60,
        'threshold_per_email' => 5,
        'threshold_per_ip' => 5,
    ],

    'rate_limit' => [
        'error500' => [
            'max_attempts' => 5,
            'decay_seconds' => 3600,
        ],
    ],

    'prune' => [
        'stats_logs' => 30,
        'activity_logs' => 90,
        'security_logs' => 90,
        'login_attempts' => 90,
        'sent_emails' => 30,
    ],

    'backoffice' => [
        'per_page' => 50,
        'laravel_logs' => [
            'directory' => storage_path('logs'),
            'per_page' => 20,
        ],
    ],

    'traffic_excluded_params' => ['password', 'password_confirmation', '_token'],

    'security_excluded_params' => [],
];
