# Laravel Watchdog

Laravel Watchdog is a lightweight, production oriented monitoring package. It tracks the handful of things that actually matter for the day to day health and security of an application (404s, 422s, 500s, public visits, authenticated activity, login attempts, sent emails) and pushes real time alerts to Discord when something needs attention.

It is not a general purpose debugger. It is a small, opinionated set of tools you leave running in production at all times.

## Why not just use Telescope

Telescope is a great tool, but it solves a different problem. It is built to give you a full picture of what happens during local development or short debugging sessions: every query, every job, every cache hit, every mail, every request, with a full dashboard to browse it all.

Watchdog exists for a narrower, more specific need:

- **Built for production, not for debugging sessions.** Telescope is generally not recommended to run permanently in production without pruning and careful configuration, because it records everything by default. Watchdog only records a small, deliberate set of events (errors, visits, logins, emails) and prunes itself automatically.
- **Alerting first, browsing second.** Telescope gives you a dashboard to look at after the fact. Watchdog is built around pushing alerts to Discord the moment something happens (a 500 error, a suspicious login pattern, an admin connecting), so you find out without having to go look.
- **Security oriented by default.** Watchdog separates public traffic from authenticated activity, tracks failed and successful login attempts, and can detect brute force patterns automatically. This is not something Telescope tracks out of the box.
- **Minimal footprint.** Five simple tables, no UI to maintain, no extra JavaScript assets, no dashboard route to protect. Configurable retention (via Laravel's native `Prunable` trait) keeps storage under control without any extra setup.
- **You keep your own Discord notifications.** Watchdog does not try to own how your alerts look. It ships default notification classes but expects you to plug in your own if you already have a Discord notification channel configured in your app.

In short: Telescope answers "what happened in this app in general", Watchdog answers "is anything wrong right now, and can someone tell me on Discord".

## What it tracks

| Table | Content |
|---|---|
| `stats_logs` | Every request on your public, non authenticated routes |
| `activity_logs` | Every request on your authenticated routes, tied to a `user_id` |
| `security_logs` | Every 404, 422 and 500 response |
| `login_attempts` | Every successful and failed login attempt |
| `sent_emails` | Every email sent by the application |

## Installation

```bash
composer require mrdebug/laravel-watchdog
php artisan vendor:publish --tag=laravel-watchdog-config
php artisan migrate
```

This registers the service provider automatically, which in turn:

- registers the migrations
- registers a `discord` notification channel
- registers the `Login`, `Failed` and `MessageSent` event listeners (if enabled in config)
- registers the `log-stats` and `log-activity` middleware aliases

## Middlewares

Two middleware aliases are registered automatically. Apply them to the relevant route groups in your app:

```php
Route::middleware('log-stats')->group(function () {
    // public routes, guests included
});

Route::middleware(['auth', 'log-activity'])->group(function () {
    // authenticated routes
});
```

`log-stats` records into `stats_logs` and skips admin users. `log-activity` records into `activity_logs`, requires an authenticated user, and also skips admins.

## Exception handler (404 / 422 / 500)

Laravel 11/12 configure exception handling directly in `bootstrap/app.php`, which runs before service providers are booted. Because of this, Watchdog cannot hook into it automatically, you need one explicit call:

```php
use MrDebug\LaravelWatchdog\Support\ExceptionHandler as WatchdogExceptionHandler;

->withExceptions(function (Exceptions $exceptions): void {
    WatchdogExceptionHandler::register($exceptions);

    // your own exceptions->report / render calls can go here too
})
```

This single call wires up 404 and 422 logging into `security_logs`, and 500 reporting to Discord (rate limited, production only).

## Artisan commands

```bash
php artisan watchdog:analyze-logins
php artisan watchdog:daily-security-report
```

Suggested scheduling, in `routes/console.php` or your scheduler of choice:

```php
$schedule->command('watchdog:analyze-logins')->hourly();
$schedule->command('watchdog:daily-security-report')->dailyAt('08:00');
$schedule->command('model:prune')->daily();
```

`model:prune` is Laravel's own command, it will automatically call `prunable()` on every Watchdog model and delete old rows based on the retention values in your config.

## Configuration reference

After publishing, the config lives at `config/laravel-watchdog.php`.

### `features`

Enables or disables each tracked event independently. Turning a feature off skips both the recording in the database and any related notification.

```php
'features' => [
    'stats' => true,            // stats_logs (public traffic)
    'activity' => true,         // activity_logs (authenticated traffic)
    'security_404' => true,     // 404 recording
    'security_422' => true,     // 422 recording
    'security_500' => true,     // 500 recording and Discord alert
    'login_attempts' => true,   // login_attempts table + admin login alert
    'sent_emails' => true,      // sent_emails table
],
```

### `discord`

```php
'discord' => [
    'only_production' => true,
    'webhooks' => [
        'beta' => env('DISCORD_WEBHOOK_BETA'),
        'logins' => env('DISCORD_WEBHOOK_LOGINS'),
        'errors500' => env('DISCORD_WEBHOOK_ERRORS'),
        'security-logs' => env('DISCORD_WEBHOOK_SECURITY_LOGS'),
        'contacts' => env('DISCORD_WEBHOOK_CONTACTS'),
    ],
],
```

- `only_production`: when `true`, the Discord channel silently drops notifications unless `app()->isProduction()` is true. Set to `false` if you want to test alerts on staging or locally.
- `webhooks.*`: one webhook URL per Discord thread/channel. Add or remove keys as needed, as long as the code referencing them (custom notifications, custom commands) points to the right key. `beta` and `contacts` are not used by the package's default listeners, they are provided as free slots for your own custom notifications.

### `notifications`

```php
'notifications' => [
    'login' => \MrDebug\LaravelWatchdog\Notifications\LoginNotification::class,
    'error500' => \MrDebug\LaravelWatchdog\Notifications\Error500Notification::class,
    'suspicious_activity' => \MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification::class,
    'daily_security_report' => \MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification::class,
],
```

Each key points to the notification class used for that event. The package ships a default, generic implementation for all four. Replace any of these with your own class (for example, one you already use in your app) to change the Discord message content without touching the package itself. Your custom class only needs to implement `via()` returning `['discord']` and a `toDiscord()` method returning an array with a `content` key.

### `admin_check`

```php
'admin_check' => [
    'field' => 'role',
    'value' => 'admin',
],
```

Used everywhere the package needs to know if a user is an admin (skipping them from `stats_logs`/`activity_logs`, sending a direct Discord alert instead of a plain database row on login). By default it checks `$user->role === 'admin'`. Adjust `field` and `value` to match your own user model.

### `suspicious_activity`

```php
'suspicious_activity' => [
    'window_minutes' => 60,
    'threshold_per_email' => 5,
    'threshold_per_ip' => 5,
],
```

Used by `watchdog:analyze-logins`. Within the last `window_minutes` minutes, if a single email or a single IP accumulates at least `threshold_per_email` / `threshold_per_ip` failed login attempts, a Discord alert is sent listing the offending emails and IPs.

### `rate_limit`

```php
'rate_limit' => [
    'error500' => [
        'max_attempts' => 5,
        'decay_seconds' => 3600,
    ],
],
```

Prevents a single recurring 500 error from flooding Discord. At most `max_attempts` notifications will be sent for the same exception class within `decay_seconds` seconds. The exception still gets logged through Laravel's normal reporting either way, this only limits the Discord notification.

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

Number of days each table keeps its rows. Every model uses Laravel's native `Prunable` trait, so running `php artisan model:prune` (ideally on a daily schedule) deletes anything older than the configured number of days. Set a higher value for tables you want to keep longer for audit purposes, and a lower value for high volume, low value tables like `stats_logs`.

### `traffic_excluded_params`

```php
'traffic_excluded_params' => ['password', 'password_confirmation', '_token'],
```

Request fields excluded from the `params` column when recording into `stats_logs` and `activity_logs`. Add any other sensitive field name here (API tokens, credit card fields, etc) to keep them out of the database.

### `security_excluded_params`

```php
'security_excluded_params' => [],
```

Same idea, but for `security_logs` (404 and 422 events). It is empty by default, on purpose: the intent is to capture the full request payload when something goes wrong, including form fields, so you can reproduce and debug the issue. If you would rather never store certain fields even in error logs, add them here.

### `sent_emails`

```php
'sent_emails' => [
    'mode' => 'all',
    'excluded_mailables' => [],
],
```

- `mode`: currently only `all` is implemented, every email sent through Laravel's mailer is recorded. `excluded_mailables` is reserved for a future per class exclusion list.

## Default notifications

The package ships four ready to use Discord notifications. Each one implements `via() => ['discord']` and a `toDiscord()` method returning `['content' => string]`, so they work directly with the `discord` channel registered by the package. You can use them as is, or replace any of them through the `notifications` config key (see above).

### `LoginNotification`

Triggered when a user matching `admin_check` logs in successfully. Sent immediately, no rate limit, since admin logins are expected to be rare.

```php
new LoginNotification(string $ip, string $email);
```

Example message:

```
🔑 New admin login
Email: admin@example.com
IP: 203.0.113.10
```

### `Error500Notification`

Triggered by the exception handler for any non HTTP exception (an actual server error, not a 404/422/etc), in production only, and rate limited per exception class (see `rate_limit.error500`).

```php
new Error500Notification(string $message, string $file, int $line);
```

Example message:

```
🔴 500 Error
`Call to a member function on null`
/app/Services/InvoiceService.php:42
https://example.com/invoices/12
```

The URL is read from the current request when available, and falls back to `n/a` when the exception happens outside an HTTP context (a queued job or an artisan command, for example).

### `SuspiciousActivityNotification`

Triggered by the `watchdog:analyze-logins` command when either the per email or per IP failed login threshold is reached within the configured time window.

```php
new SuspiciousActivityNotification(Collection $byEmail, Collection $byIp);
```

`$byEmail` and `$byIp` are the query results from the command, each row exposing `email` or `ip` plus a `total` count of failed attempts. Either collection can be empty if only one of the two thresholds was crossed.

Example message:

```
🚨 Suspicious activity detected

By email:
• john@example.com : 6 failures

By IP:
• 203.0.113.44 : 8 failures
```

### `DailySecurityReportNotification`

Triggered by the `watchdog:daily-security-report` command, once a day if scheduled as suggested above. Skipped entirely if both counts are zero, so it never sends an empty report.

```php
new DailySecurityReportNotification(int $count404, int $count422);
```

Example message:

```
📊 Daily security report

• 404: 12
• 422: 3
```

## Overriding notifications entirely

If you prefer to keep using your own existing notification classes instead of the package's defaults, just point the relevant `notifications.*` config key to your own class. As long as it returns `['discord']` from `via()` and implements `toDiscord()`, it will work with the Discord channel registered by the package, no other change required.
