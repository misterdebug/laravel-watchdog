<?php

namespace MrDebug\LaravelWatchdog\Support;

use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use MrDebug\LaravelWatchdog\Contracts\AdminChecker;
use ReflectionClass;

final class ConfigValidator
{
    public static function validate(): void
    {
        $config = config('laravel-watchdog');

        if (! is_array($config)) {
            throw new InvalidArgumentException(__('laravel-watchdog::validation.configuration'));
        }

        self::validateFeatures($config['features'] ?? null);
        self::validateDiscord($config['discord'] ?? null);
        self::validateNotifications($config['notifications'] ?? null);
        self::validateAdminCheck($config['admin_check'] ?? null);
        self::validateSuspiciousActivity($config['suspicious_activity'] ?? null);
        self::validateRateLimit($config['rate_limit'] ?? null);
        self::validatePrune($config['prune'] ?? null);
        self::validateBackoffice($config['backoffice'] ?? null);

        self::assertStringArray(
            $config['traffic_excluded_params'] ?? null,
            'traffic_excluded_params'
        );

        self::assertStringArray(
            $config['security_excluded_params'] ?? null,
            'security_excluded_params'
        );
    }

    private static function validateFeatures(mixed $features): void
    {
        if (! is_array($features)) {
            self::fail('features', 'must_be_array');
        }

        foreach ([
            'stats',
            'activity',
            'security_404',
            'security_422',
            'error500_alert',
            'login_attempts',
            'sent_emails',
        ] as $key) {
            if (! array_key_exists($key, $features) || ! is_bool($features[$key])) {
                self::fail("features.$key", 'must_be_boolean');
            }
        }
    }

    private static function validateDiscord(mixed $discord): void
    {
        if (! is_array($discord)) {
            self::fail('discord', 'must_be_array');
        }

        if (! array_key_exists('only_production', $discord) || ! is_bool($discord['only_production'])) {
            self::fail('discord.only_production', 'must_be_boolean');
        }

        if (! array_key_exists('webhooks', $discord) || ! is_array($discord['webhooks'])) {
            self::fail('discord.webhooks', 'must_be_array');
        }

        foreach ($discord['webhooks'] as $key => $url) {
            if ($url === null || $url === '') {
                continue;
            }

            if (
                ! is_string($url)
                || filter_var($url, FILTER_VALIDATE_URL) === false
                || parse_url($url, PHP_URL_SCHEME) !== 'https'
            ) {
                self::fail("discord.webhooks.$key", 'must_be_https_url');
            }
        }
    }

    private static function validateNotifications(mixed $notifications): void
    {
        if (! is_array($notifications)) {
            self::fail('notifications', 'must_be_array');
        }

        foreach ([
            'login',
            'error500',
            'suspicious_activity',
            'daily_security_report',
        ] as $key) {
            self::assertNotificationClass($notifications[$key] ?? null, "notifications.$key");
        }
    }

    private static function validateAdminCheck(mixed $adminCheck): void
    {
        if (! is_array($adminCheck)) {
            self::fail('admin_check', 'must_be_array');
        }

        $checker = $adminCheck['checker'] ?? null;

        if (
            ! is_string($checker)
            || ! class_exists($checker)
            || ! is_subclass_of($checker, AdminChecker::class)
        ) {
            self::fail('admin_check.checker', 'must_implement', ['class' => AdminChecker::class]);
        }

        if (! (new ReflectionClass($checker))->isInstantiable()) {
            self::fail('admin_check.checker', 'must_be_instantiable');
        }

        if (is_a($checker, RoleAdminChecker::class, true)) {
            $field = $adminCheck['field'] ?? null;

            if (! is_string($field) || $field === '') {
                self::fail('admin_check.field', 'must_be_non_empty_string');
            }

            $values = $adminCheck['values'] ?? null;

            if (! is_array($values) || $values === []) {
                self::fail('admin_check.values', 'must_be_non_empty_array');
            }

            foreach ($values as $value) {
                if (! is_scalar($value)) {
                    self::fail('admin_check.values', 'must_contain_scalars');
                }
            }
        }
    }

    private static function validateSuspiciousActivity(mixed $suspiciousActivity): void
    {
        if (! is_array($suspiciousActivity)) {
            self::fail('suspicious_activity', 'must_be_array');
        }

        foreach ([
            'window_minutes',
            'threshold_per_email',
            'threshold_per_ip',
        ] as $key) {
            self::assertPositiveInt($suspiciousActivity[$key] ?? null, "suspicious_activity.$key");
        }
    }

    private static function validateRateLimit(mixed $rateLimit): void
    {
        if (
            ! is_array($rateLimit)
            || ! isset($rateLimit['error500'])
            || ! is_array($rateLimit['error500'])
        ) {
            self::fail('rate_limit.error500', 'must_be_array');
        }

        foreach ([
            'max_attempts',
            'decay_seconds',
        ] as $key) {
            self::assertPositiveInt($rateLimit['error500'][$key] ?? null, "rate_limit.error500.$key");
        }
    }

    private static function validatePrune(mixed $prune): void
    {
        if (! is_array($prune)) {
            self::fail('prune', 'must_be_array');
        }

        foreach ([
            'stats_logs',
            'activity_logs',
            'security_logs',
            'login_attempts',
            'sent_emails',
        ] as $key) {
            self::assertPositiveInt($prune[$key] ?? null, "prune.$key");
        }
    }

    private static function validateBackoffice(mixed $backoffice): void
    {
        if (! is_array($backoffice)) {
            self::fail('backoffice', 'must_be_array');
        }

        self::assertPositiveInt($backoffice['per_page'] ?? null, 'backoffice.per_page');

        if (! isset($backoffice['laravel_logs']) || ! is_array($backoffice['laravel_logs'])) {
            self::fail('backoffice.laravel_logs', 'must_be_array');
        }

        $directory = $backoffice['laravel_logs']['directory'] ?? null;

        if (! is_string($directory) || $directory === '') {
            self::fail('backoffice.laravel_logs.directory', 'must_be_non_empty_string');
        }

        self::assertPositiveInt(
            $backoffice['laravel_logs']['per_page'] ?? null,
            'backoffice.laravel_logs.per_page'
        );
    }

    private static function assertNotificationClass(mixed $class, string $key): void
    {
        if (
            ! is_string($class)
            || ! class_exists($class)
            || ! is_subclass_of($class, Notification::class)
            || ! method_exists($class, 'toDiscord')
        ) {
            self::fail($key, 'must_extend_notification', ['class' => Notification::class]);
        }

        if (! (new ReflectionClass($class))->isInstantiable()) {
            self::fail($key, 'must_be_instantiable');
        }
    }

    private static function assertStringArray(mixed $value, string $key): void
    {
        if (! is_array($value)) {
            self::fail($key, 'must_be_array');
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                self::fail($key, 'must_contain_strings');
            }
        }
    }

    private static function assertPositiveInt(mixed $value, string $key): void
    {
        if (! is_int($value) || $value < 1) {
            self::fail($key, 'must_be_positive_integer');
        }
    }

    private static function fail(string $key, string $messageKey, array $replace = []): never
    {
        throw new InvalidArgumentException(__('laravel-watchdog::validation.message', [
            'key' => $key,
            'message' => __('laravel-watchdog::validation.'.$messageKey, $replace),
        ]));
    }
}
