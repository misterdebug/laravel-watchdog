# Laravel Watchdog

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrdebug/laravel-watchdog.svg)](https://packagist.org/packages/mrdebug/laravel-watchdog)
[![License](https://img.shields.io/packagist/l/mrdebug/laravel-watchdog.svg)](https://packagist.org/packages/mrdebug/laravel-watchdog)

Laravel Watchdog is a lightweight, production-oriented monitoring package. It tracks the handful of things that actually matter for the day-to-day health and security of an application (404s, 422s, 500s, public visits, authenticated activity, login attempts, sent emails) and pushes real-time alerts to Discord when something needs attention.

It is not a general-purpose debugger. It is a small, opinionated set of tools you leave running in production.

## Features

- Records public and authenticated traffic in dedicated tables.
- Records 404 and 422 responses, including the validation errors for 422.
- Sends Discord alerts for 500 errors, with rate limiting per exception class.
- Records successful and failed login attempts.
- Sends a Discord alert when an admin logs in.
- Detects brute-force patterns with `watchdog:analyze-logins`.
- Sends a daily 404/422 report with `watchdog:daily-security-report`.
- Records every email sent through Laravel's mailer, including recipients, bodies, attachments and headers.
- Provides a configurable and replaceable admin checker.
- Prunes old records with a dedicated command.

## What it tracks

| Table | Content |
|---|---|
| `watchdog_stats_logs` | Requests on routes using the `log-stats` middleware, excluding admins |
| `watchdog_activity_logs` | Authenticated requests on routes using the `log-activity` middleware, tied to a `user_id`, excluding admins |
| `watchdog_security_logs` | 404 and 422 responses |
| `watchdog_login_attempts` | Successful non-admin and failed login attempts |
| `watchdog_sent_emails` | Every email sent by the application |

`watchdog_security_logs.params` stores the request input for 404s and a `{input, errors}` payload for 422s. `watchdog_sent_emails` stores the sender, to/cc/bcc recipients, subject, HTML and text bodies, attachment metadata and headers.

Admin logins are alerted on Discord but are not written to `watchdog_login_attempts`.

## Recent changes

- Tables are prefixed with `watchdog_`.
- Sent emails now include `from`, to/cc/bcc recipients, the text body, attachment metadata and headers.
- 422 validation errors are stored under `params.errors`.
- Admin detection is now pluggable through `admin_check.checker`.
- `LoginNotification` and `Error500Notification` are queued notifications.
- Pruning uses Laravel's `MassPrunable` trait for bulk deletes.

## Requirements

- PHP 8.2 or newer.
- Laravel 11 or 12.
- A database supported by Laravel with JSON column support.
- A queue worker if your queue connection is not `sync` and you use the queued notifications.
- A persistent cache store for the 500 rate limiter.
- The Laravel scheduler if you want the recommended recurring commands.

## Installation

```bash
composer require mrdebug/laravel-watchdog
php artisan vendor:publish --tag=laravel-watchdog-config
php artisan migrate
```

In production, run migrations with `php artisan migrate --force`.

The service provider is discovered automatically. It registers:

- the package migrations;
- the `watchdog-discord` notification channel;
- the `Login`, `Failed` and `MessageSent` listeners when their feature is enabled;
- the `log-stats` and `log-activity` middleware aliases;
- the package Artisan commands.

### Environment

Add the Discord webhooks you want to use:

```dotenv
DISCORD_WEBHOOK_LOGINS=
DISCORD_WEBHOOK_ERRORS=
DISCORD_WEBHOOK_SECURITY_LOGS=
```

Webhook URLs are secrets. Do not commit them, and rotate them if they are exposed.

### Exception handler

Laravel 11/12 configure exception handling directly in `bootstrap/app.php`, which runs before service providers are booted. Watchdog therefore needs one explicit call:

```php
use MrDebug\LaravelWatchdog\Support\ExceptionHandler as WatchdogExceptionHandler;

->withExceptions(function (Exceptions $exceptions): void {
    WatchdogExceptionHandler::register($exceptions);

    // Your own report/render callbacks can go here.
})
```

Register Watchdog before your own `render()` callbacks. Laravel stops at the first callback that returns a response, so a custom callback registered first can prevent Watchdog from logging a 404 or 422.

This call wires up:

- 404 logging into `watchdog_security_logs`;
- 422 logging into `watchdog_security_logs`, including validation errors;
- 500 reporting to Discord, rate limited and without a database log.

### Middlewares

```php
Route::middleware('log-stats')->group(function () {
    // Public routes, guests included.
});

Route::middleware(['auth', 'log-activity'])->group(function () {
    // Authenticated routes.
});
```

- `log-stats` records non-admin requests into `watchdog_stats_logs`. If an authenticated non-admin reaches a route using this middleware, the request is recorded there too.
- `log-activity` requires an authenticated user and records non-admin requests into `watchdog_activity_logs`.

Both middlewares use the configured `AdminChecker` to skip admins.

### Scheduler

```php
$schedule->command('watchdog:analyze-logins')->hourly();
$schedule->command('watchdog:daily-security-report')->dailyAt('08:00');
$schedule->command('watchdog:prune')->daily();
```

### Queue worker

`LoginNotification` and `Error500Notification` implement `ShouldQueue`. With a non-synchronous queue connection, a queue worker must be running for these alerts to be delivered. With `QUEUE_CONNECTION=sync`, they are sent immediately.

## How it works

- **Requests** go through `log-stats` or `log-activity`, which write to `watchdog_stats_logs` or `watchdog_activity_logs`.
- **Exceptions** go through `WatchdogExceptionHandler::register()`: 404 and 422 are written to `watchdog_security_logs`, and 500-class errors are sent to Discord.
- **Authentication** fires `Login` and `Failed` events, handled by `LogLoginAttempt`: failed and successful non-admin attempts are recorded, admin logins send a Discord notification.
- **Mail** fires `MessageSent`, handled by `LogSentEmail`, which writes the message to `watchdog_sent_emails`.
- **Commands** analyze login attempts, send the daily report and prune old records.

## Configuration

After publishing, the config lives at `config/laravel-watchdog.php`.

### `features`

Enables or disables each tracked event independently. Turning a feature off skips the recording and any related notification.

```php
'features' => [
    'stats' => true,
    'activity' => true,
    'security_404' => true,
    'security_422' => true,
    'error500_alert' => true, // Discord alert only; no database log
    'login_attempts' => true,
    'sent_emails' => true,
],
```

The `login_attempts` and `sent_emails` flags are checked when the service provider boots. After changing them, clear the config cache and restart the application.

### `discord`

```php
'discord' => [
    'only_production' => true,
    'webhooks' => [
        'logins' => env('DISCORD_WEBHOOK_LOGINS'),
        'errors500' => env('DISCORD_WEBHOOK_ERRORS'),
        'security-logs' => env('DISCORD_WEBHOOK_SECURITY_LOGS'),
    ],
],
```

- `only_production`: when `true`, the Discord channel silently drops notifications unless `app()->isProduction()` is true. Set it to `false` to test alerts on staging or locally.
- `logins`: used by the admin login alert and the suspicious-activity alert.
- `errors500`: used by the 500 error alert.
- `security-logs`: used by the daily security report.

A missing or empty webhook URL logs a warning and skips the notification. HTTP failures are logged; notifications are not retried automatically.

### `notifications`

```php
'notifications' => [
    'login' => \MrDebug\LaravelWatchdog\Notifications\LoginNotification::class,
    'error500' => \MrDebug\LaravelWatchdog\Notifications\Error500Notification::class,
    'suspicious_activity' => \MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification::class,
    'daily_security_report' => \MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification::class,
],
```

Each key points to the notification class used for that event. You can replace any of them with your own class.

Your custom class must:

- extend `Illuminate\Notifications\Notification`;
- implement `via()` returning `['watchdog-discord']`;
- define `toDiscord()` returning an array with a `content` key;
- implement `ShouldQueue` if you want it queued.

### `admin_check`

Watchdog resolves an `AdminChecker` implementation to decide whether a user is an admin. The default implementation is `RoleAdminChecker`.

```php
'admin_check' => [
    'checker' => \MrDebug\LaravelWatchdog\Support\RoleAdminChecker::class,
    'field' => 'role',
    'values' => ['admin', 'super-admin'],
],
```

- `checker`: class implementing `MrDebug\LaravelWatchdog\Contracts\AdminChecker`.
- `field`: user attribute read with `data_get()`. Defaults to `role`.
- `values`: accepted values. Defaults to `['admin']`. `BackedEnum` values are normalized to their scalar value.

The default checker is equivalent to:

```php
in_array(data_get($user, 'role'), ['admin', 'super-admin'], true)
```

It is used by `log-stats`, `log-activity`, and the admin login notification.

#### Custom admin logic

Create a class implementing the contract:

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

Then point the config to it:

```php
'admin_check' => [
    'checker' => \App\Support\AppAdminChecker::class,
],
```

`field` and `values` are ignored when a custom checker is used. The checker is resolved from the Laravel container, so constructor dependencies are supported.

> The old `value` key is no longer supported. Use `values`.

### `suspicious_activity`

```php
'suspicious_activity' => [
    'window_minutes' => 60,
    'threshold_per_email' => 5,
    'threshold_per_ip' => 5,
],
```

Used by `watchdog:analyze-logins`. Within the last `window_minutes` minutes, if a single email or a single IP accumulates at least `threshold_per_email` / `threshold_per_ip` failed login attempts, a Discord alert is sent listing the offending emails and IPs.

The command sends an alert whenever the thresholds are met. If you schedule it hourly, the same offenders can trigger repeated alerts until they stop.

### `rate_limit`

```php
'rate_limit' => [
    'error500' => [
        'max_attempts' => 5,
        'decay_seconds' => 3600,
    ],
],
```

Prevents a single recurring 500 error from flooding Discord. At most `max_attempts` notifications are sent for the same exception class within `decay_seconds` seconds. The exception still goes through Laravel's normal reporting; this only limits the Discord notification.

The rate limiter uses Laravel's cache. Use a persistent cache store in production.

### `prune`

```php
'prune' => [
    'stats_logs' => 30,
    'activity_logs' => 90,
    'security_logs' => 90,
    'login_attempts' => 90,
    'sent_emails' => 30,
],
```

Number of days each table keeps its rows. Every model uses Laravel's `MassPrunable` trait, so `php artisan watchdog:prune` deletes old rows in bulk.

Set a higher value for tables you want to keep longer for audit purposes, and a lower value for high-volume, low-value tables such as `watchdog_stats_logs`.

### `traffic_excluded_params`

```php
'traffic_excluded_params' => ['password', 'password_confirmation', '_token'],
```

Passed to `$request->except()` when building the `params` payload for `watchdog_stats_logs` and `watchdog_activity_logs`. Add any other sensitive field name here (API tokens, credit card fields, etc.).

The complete URL, including its query string, is stored separately in the `url` column.

### `security_excluded_params`

```php
'security_excluded_params' => [],
```

Passed to `$request->except()` when building the payload for `watchdog_security_logs` (404 and 422 events). It is empty by default on purpose: the package stores the full request input so you can reproduce and debug the issue.

This means sensitive fields, including passwords on a 422 login validation error, can be stored in the database. If that is not acceptable for your application, list the fields to redact here.

### `sent_emails`

```php
'sent_emails' => [
    'mode' => 'all',
    'excluded_mailables' => [],
],
```

Every email sent through Laravel's mailer is recorded. The `mode` and `excluded_mailables` keys are reserved for future use and currently have no effect.

Stored fields:

- `from`;
- `recipients` with `to`, `cc` and `bcc` addresses;
- `subject`;
- `html` and `text` bodies;
- attachment metadata (`name`, `mime`, `size`);
- headers.

Email bodies and headers can contain personal data, password-reset links and tokens. Adjust the retention period and database access accordingly.

### Configuration validation

Watchdog validates its configuration when the service provider registers. Invalid configuration throws an `InvalidArgumentException` with the offending key, for example:

```text
laravel-watchdog: [admin_check.values] must be a non-empty array.
laravel-watchdog: [notifications.error500] must be a class extending Illuminate\Notifications\Notification and defining toDiscord().
```

Validated values include:

| Key | Rule |
|---|---|
| `features.*` | all feature keys exist and are booleans |
| `discord.only_production` | boolean |
| `discord.webhooks` | array; each value is null, empty, or a valid HTTPS URL |
| `notifications.*` | class exists, extends `Notification`, defines `toDiscord()`, is instantiable |
| `admin_check.checker` | class exists, implements `AdminChecker`, is instantiable |
| `admin_check.field` / `values` | validated when `RoleAdminChecker` is used |
| `suspicious_activity.*` | positive integers |
| `rate_limit.error500.*` | positive integers |
| `prune.*` | positive integers |
| `traffic_excluded_params` | array of strings |
| `security_excluded_params` | array of strings |
| `sent_emails.mode` | string, when present |
| `sent_emails.excluded_mailables` | array of strings, when present |

After changing the config, run:

```bash
php artisan config:clear
```

## Discord notifications

The package ships four ready-to-use Discord notifications. Each one implements `via() => ['watchdog-discord']` and `toDiscord()` returning `['content' => string]`.

| Event | Webhook key | Notification | Delivery |
|---|---|---|---|
| Admin login | `logins` | `LoginNotification` | queued |
| Suspicious activity | `logins` | `SuspiciousActivityNotification` | synchronous |
| 500 error | `errors500` | `Error500Notification` | queued |
| Daily security report | `security-logs` | `DailySecurityReportNotification` | synchronous |

### `LoginNotification`

Triggered when a user matching the configured `AdminChecker` logs in successfully. It is queued through Laravel's notification queue unless `QUEUE_CONNECTION=sync`. There is no rate limit, since admin logins are expected to be rare.

```php
new LoginNotification(?string $ip, string $email);
```

Example message:

```text
🔑 New admin login
Email: admin@example.com
IP: 203.0.113.10
```

### `Error500Notification`

Triggered by the exception handler for a non-HTTP exception that reaches the reporting callback. It is queued through Laravel's notification queue unless `QUEUE_CONNECTION=sync`, and it is rate limited per exception class (see `rate_limit.error500`). No database row is written for 500 errors.

```php
new Error500Notification(string $message, string $file, int $line);
```

Example message:

```text
🔴 500 Error
`Call to a member function on null`
/app/Services/InvoiceService.php:42
https://example.com/invoices/12
```

The URL is captured when the exception is reported, before the notification is queued.

### `SuspiciousActivityNotification`

Triggered by `watchdog:analyze-logins` when either the per-email or per-IP failed-login threshold is reached within the configured window. The command sends it synchronously.

```php
new SuspiciousActivityNotification(Collection $byEmail, Collection $byIp);
```

`$byEmail` and `$byIp` are query results, each row exposing `email` or `ip` plus a `total` count of failed attempts. Either collection can be empty if only one threshold was crossed.

Example message:

```text
🚨 Suspicious activity detected

By email :
• john@example.com : 6 failures

By IP :
• 203.0.113.44 : 8 failures
```

### `DailySecurityReportNotification`

Triggered by `watchdog:daily-security-report`, synchronously, once a day if scheduled as suggested. It is skipped entirely if both counts are zero.

```php
new DailySecurityReportNotification(int $count404, int $count422);
```

Example message:

```text
📊 Daily security report

• 404 : 12
• 422 : 3
```

### Custom notifications

To use your own notification classes, point the relevant `notifications.*` config key to your class. The class must extend `Notification`, return `['watchdog-discord']` from `via()` and implement `toDiscord()`.

## Artisan commands

```bash
php artisan watchdog:analyze-logins
php artisan watchdog:daily-security-report
php artisan watchdog:prune
```

- `watchdog:analyze-logins`: analyzes failed login attempts in the last `window_minutes` and sends an alert when a threshold is reached.
- `watchdog:daily-security-report`: counts 404 and 422 responses for yesterday and sends a report if either count is greater than zero.
- `watchdog:prune`: deletes records older than the configured retention using `MassPrunable`.

## Security & privacy

Watchdog is designed for production monitoring, but it stores sensitive data by design:

- **404/422 payloads** are stored in full by default. This includes request input and, for 422, validation errors. Sensitive fields such as passwords can therefore be stored. Use `security_excluded_params` to redact fields.
- **URLs** are stored with their complete query string in the `url` column.
- **Emails** store both HTML and text bodies, recipients, attachments metadata and headers. Password-reset links and tokens can be present.
- **IP addresses, emails and session IDs** are personal data. Use the retention settings and restrict database access.
- **Discord webhooks** are secrets. Do not commit them. Rotate them if they leak.
- **500 notifications** include the exception message, file and line. Exception messages can contain sensitive values depending on your application.

Adjust the exclusion lists, retention and access controls to match your privacy requirements.

## Performance

- `log-stats` and `log-activity` write synchronously after the response is generated. Apply them only to the route groups you care about.
- 404/422 logging also writes synchronously from the exception handler. A high volume of 404s increases the size of `watchdog_security_logs`.
- Emails store full bodies and headers; keep a short retention for `watchdog_sent_emails` if your mail volume is high.
- Pruning uses `MassPrunable`, which deletes rows in bulk rather than loading models one by one.
- Every table is indexed on `created_at`. `watchdog_security_logs` also has an index on `ip`, and `watchdog_login_attempts` has indexes on `email` and `ip`.
- Sampling is not built in. If you need to reduce traffic writes further, implement sampling in your own middleware or apply `log-stats` to fewer routes.

## Troubleshooting

### No Discord alert

- Check `discord.only_production`: outside production, alerts are dropped unless it is `false`.
- Check the corresponding webhook environment variable.
- Run `php artisan config:clear` after changing the config or `.env`.
- For `LoginNotification` and `Error500Notification`, make sure a queue worker is running.
- Verify which webhook key is used by the event (see the notification table above).

### 404/422 are not logged

- Make sure `WatchdogExceptionHandler::register($exceptions)` is present in `bootstrap/app.php`.
- Register it before your own `render()` callbacks.
- Check the `security_404` / `security_422` feature flags.
- Run `php artisan config:clear` after changing them.

### Admins are still logged

- Check `admin_check.checker`.
- For `RoleAdminChecker`, check `admin_check.field` and `admin_check.values`.
- Make sure the field exists on your user model and its value matches one of `values`.
- Run `php artisan config:clear`.

### Emails are not logged

- Check the `sent_emails` feature flag.
- The listener is registered at boot, so clear the config cache and restart the application after changing the flag.
- Only mail sent through Laravel's mailer triggers `MessageSent`.

### `watchdog:prune` deletes nothing

- Check the retention values in `prune.*`; they must be positive integers.
- Check that the rows are older than the configured number of days.
- The command uses each model's `prunable()` query.

### `watchdog:analyze-logins` sends no alert

- Check that failed login attempts are being recorded in `watchdog_login_attempts`.
- Check `window_minutes`, `threshold_per_email` and `threshold_per_ip`.
- Check the `logins` webhook.
- If your login form uses a field other than `email`, failed attempts are recorded with a null email and may not be grouped as expected.

### Invalid configuration at boot

Watchdog throws an `InvalidArgumentException` with the offending key. Fix the config, then run `php artisan config:clear`.

## Watchdog vs Telescope

Telescope is a development and debugging tool. Watchdog is a small production monitoring layer focused on a handful of events and Discord alerts. They solve different problems and can be used together.

| | Watchdog | Telescope |
|---|---|---|
| Purpose | Production monitoring and alerting | Development/debugging |
| Scope | Curated events | Everything |
| Storage | Small, pruned tables | Large, detailed records |
| Alerts | Discord webhooks | None |
| Overhead | Low | Higher if always enabled |

## License

MIT.
