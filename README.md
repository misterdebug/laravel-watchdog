# Laravel Watchdog

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrdebug/laravel-watchdog.svg)](https://packagist.org/packages/mrdebug/laravel-watchdog)
[![License](https://img.shields.io/packagist/l/mrdebug/laravel-watchdog.svg)](https://packagist.org/packages/mrdebug/laravel-watchdog)

Laravel Watchdog is a lightweight, production-oriented monitoring package. It tracks the few things that matter for the health and security of an application (404s, 422s, 500s, public visits, authenticated activity, login attempts, sent emails) and pushes real-time alerts to Discord when something needs attention.

It is not a general-purpose debugger like Telescope. It is a small, opinionated set of tools you leave running in production, and the two can be used together.

## What it tracks

All tables are prefixed with `watchdog_`.

| Table | Content |
|---|---|
| `watchdog_stats_logs` | Requests on routes using `log-stats`, excluding admins |
| `watchdog_activity_logs` | Authenticated requests on routes using `log-activity`, tied to a `user_id`, excluding admins |
| `watchdog_security_logs` | 404 and 422 responses. `params` holds the request input for 404s and `{input, errors}` for 422s |
| `watchdog_login_attempts` | Successful non-admin and failed login attempts |
| `watchdog_sent_emails` | Every email sent: sender, to/cc/bcc recipients, subject, HTML and text bodies, attachment metadata (`name`, `mime`, `size`) and headers |

Admin logins and 500 errors are alerted on Discord but not written to the database.

## Installation

Requires PHP 8.2+, Laravel 11+, and a database with JSON column support.

```bash
composer require mrdebug/laravel-watchdog
php artisan vendor:publish --tag=laravel-watchdog-config
php artisan migrate
```

In production, use `php artisan migrate --force`. The service provider is discovered automatically.

### Environment

```dotenv
DISCORD_WEBHOOK_LOGINS=
DISCORD_WEBHOOK_ERRORS=
DISCORD_WEBHOOK_SECURITY_LOGS=
```

Webhook URLs are secrets. Do not commit them, and rotate them if they are exposed.

### Exception handler

Laravel 11/12 configure exception handling in `bootstrap/app.php`, which runs before service providers are booted, so Watchdog needs one explicit call:

```php
use MrDebug\LaravelWatchdog\Support\ExceptionHandler as WatchdogExceptionHandler;

->withExceptions(function (Exceptions $exceptions): void {
    WatchdogExceptionHandler::register($exceptions);

    // Your own report/render callbacks can go here.
})
```

Register it before your own `render()` callbacks. Laravel stops at the first callback that returns a response, so a custom callback registered first can prevent Watchdog from logging a 404 or 422.

### Middlewares

```php
Route::middleware('log-stats')->group(function () {
    // Public routes, guests included.
});

Route::middleware(['auth', 'log-activity'])->group(function () {
    // Authenticated routes.
});
```

- `log-stats` records non-admin requests into `watchdog_stats_logs`, including authenticated non-admins.
- `log-activity` requires an authenticated user and records non-admin requests into `watchdog_activity_logs`.

Both use the configured `AdminChecker` to skip admins.

### Scheduler and queue

```php
$schedule->command('watchdog:analyze-logins')->hourly();       // alerts when failed-login thresholds are reached
$schedule->command('watchdog:daily-security-report')->dailyAt('08:00'); // yesterday's 404/422 counts, skipped if both are zero
$schedule->command('watchdog:prune')->daily();                 // bulk-deletes records past their retention
```

`LoginNotification` and `Error500Notification` are queued, so a queue worker must be running unless `QUEUE_CONNECTION=sync`. The 500 rate limiter uses Laravel's cache, so use a persistent store in production.

## Configuration

The config lives at `config/laravel-watchdog.php` and is validated when the service provider registers: an invalid value throws an `InvalidArgumentException` naming the offending key. Run `php artisan config:clear` after any change.

```php
return [
    // Enable or disable each tracked event. Off means no recording and no related notification.
    // `login_attempts` and `sent_emails` are read at boot: restart the app after changing them.
    'features' => [
        'stats' => true,
        'activity' => true,
        'security_404' => true,
        'security_422' => true,
        'error500_alert' => true, // Discord alert only, no database log
        'login_attempts' => true,
        'sent_emails' => true,
    ],

    'discord' => [
        // When true, notifications are silently dropped outside production.
        // Set to false to test on staging or locally.
        'only_production' => true,
        // Each value must be null, empty or a valid HTTPS URL.
        // A missing webhook logs a warning and skips the notification. HTTP failures are logged, not retried.
        'webhooks' => [
            'logins' => env('DISCORD_WEBHOOK_LOGINS'),           // admin login, suspicious activity
            'errors500' => env('DISCORD_WEBHOOK_ERRORS'),        // 500 errors
            'security-logs' => env('DISCORD_WEBHOOK_SECURITY_LOGS'), // daily report
        ],
    ],

    // Replace any class with your own (see "Custom notifications" below).
    'notifications' => [
        'login' => \MrDebug\LaravelWatchdog\Notifications\LoginNotification::class,
        'error500' => \MrDebug\LaravelWatchdog\Notifications\Error500Notification::class,
        'suspicious_activity' => \MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification::class,
        'daily_security_report' => \MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification::class,
    ],

    // Decides who is an admin (see "Admin checker" below).
    'admin_check' => [
        'checker' => \MrDebug\LaravelWatchdog\Support\RoleAdminChecker::class,
        'field' => 'role',       // user attribute read with data_get()
        'values' => ['admin'],   // accepted values, BackedEnum values are normalized
    ],

    // watchdog:analyze-logins alerts when one email or one IP reaches a threshold
    // of failed logins within the window.
    'suspicious_activity' => [
        'window_minutes' => 60,
        'threshold_per_email' => 5,
        'threshold_per_ip' => 5,
    ],

    // Max Discord notifications per exception class within the decay period.
    // The exception still goes through Laravel's normal reporting.
    'rate_limit' => [
        'error500' => ['max_attempts' => 5, 'decay_seconds' => 3600],
    ],

    // Days of retention per table. Keep more for audit tables, less for high-volume ones.
    'prune' => [
        'stats_logs' => 30,
        'activity_logs' => 90,
        'security_logs' => 90,
        'login_attempts' => 90,
        'sent_emails' => 30,
    ],

    // Fields removed from the params of stats and activity logs.
    // The full URL, query string included, is stored separately in `url`.
    'traffic_excluded_params' => ['password', 'password_confirmation', '_token'],

    // Fields removed from the payload of 404/422 logs. Empty by default.
    // See "Security and privacy" before leaving it empty.
    'security_excluded_params' => [],
];
```

### Admin checker

The default `RoleAdminChecker` is equivalent to `in_array(data_get($user, 'role'), ['admin'], true)`. It is used by `log-stats`, `log-activity` and the admin login notification.

For custom logic, implement the contract and point `checker` to your class. It is resolved from the container, so constructor dependencies work. `field` and `values` are then ignored.

```php
namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use MrDebug\LaravelWatchdog\Contracts\AdminChecker;

final class AppAdminChecker implements AdminChecker
{
    public function isAdmin(?Authenticatable $user): bool
    {
        return $user?->is_admin === true;
    }
}
```

```php
'admin_check' => [
    'checker' => \App\Support\AppAdminChecker::class,
],
```

### Custom notifications

A replacement class must extend `Illuminate\Notifications\Notification`, return `['watchdog-discord']` from `via()`, define `toDiscord()` returning an array with a `content` key, and implement `ShouldQueue` if you want it queued.

## Discord notifications

| Event | Notification | Delivery |
|---|---|---|
| Admin login | `LoginNotification` | queued |
| Suspicious activity | `SuspiciousActivityNotification` | synchronous |
| 500 error | `Error500Notification` | queued, rate limited |
| Daily security report | `DailySecurityReportNotification` | synchronous |

```text
🔑 New admin login
Email: admin@example.com
IP: 203.0.113.10
```

```text
🔴 500 Error
`Call to a member function on null`
/app/Services/InvoiceService.php:42
https://example.com/invoices/12
```

The 500 alert captures the request URL when the exception is reported, before queuing.

## Security and privacy

Watchdog stores sensitive data by design:

- **404/422 payloads** are stored in full by default, including request input and validation errors. Passwords can therefore end up in `watchdog_security_logs`, for example on a failed login validation. Use `security_excluded_params` to redact them, for instance `['password', 'password_confirmation', 'current_password', '_token']`.
- **URLs** are stored with their complete query string.
- **Emails** store bodies, recipients, attachment metadata and headers. Password-reset links and tokens can be present.
- **IP addresses, emails and session IDs** are personal data.
- **500 notifications** include the exception message, file and line, which can contain sensitive values.

Adjust exclusion lists, retention and database access to match your privacy requirements.

Performance notes:

- `log-stats`, `log-activity` and the 404/422 logging write synchronously. Apply the middlewares only to the route groups you care about. Sampling is not built in, so implement it in your own middleware if needed.
- A high volume of 404s grows `watchdog_security_logs`, and full email bodies grow `watchdog_sent_emails`. Use short retention for both if volume is high.
- Pruning uses `MassPrunable`, which deletes in bulk. Every table is indexed on `created_at`, `watchdog_security_logs` on `ip`, and `watchdog_login_attempts` on `email` and `ip`.

## Troubleshooting

After any fix below, run `php artisan config:clear`.

**No Discord alert**
- Check `discord.only_production` and the matching webhook variable.
- For queued notifications, make sure a queue worker is running.

**404/422 are not logged**
- Make sure `WatchdogExceptionHandler::register($exceptions)` is in `bootstrap/app.php`, before your own `render()` callbacks.
- Check the `security_404` / `security_422` flags.

**Admins are still logged**
- Check `admin_check.checker`. For `RoleAdminChecker`, make sure `field` exists on your user model and its value matches one of `values`.

**`watchdog:analyze-logins` does not group attempts as expected**
- If your login form uses a field other than `email`, attempts are recorded with a null email and cannot be grouped by email.

## License

MIT.
