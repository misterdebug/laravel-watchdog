<?php

namespace MrDebug\LaravelWatchdog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use MrDebug\LaravelWatchdog\Models\SecurityLog;

class SendDailySecurityReport extends Command
{
    protected $signature = 'watchdog:daily-security-report';

    protected $description = 'Sends a daily report of 404 and 422 counts to Discord';

    public function handle(): void
    {
        $security404Enabled = (bool) config('laravel-watchdog.features.security_404', true);
        $security422Enabled = (bool) config('laravel-watchdog.features.security_422', true);

        if (! $security404Enabled && ! $security422Enabled) {
            $this->info('Security monitoring is disabled.');

            return;
        }

        $yesterday = now()->yesterday();

        $count404 = $security404Enabled
            ? SecurityLog::where('response_code', 404)
                ->whereDate('created_at', $yesterday)
                ->count()
            : 0;

        $count422 = $security422Enabled
            ? SecurityLog::where('response_code', 422)
                ->whereDate('created_at', $yesterday)
                ->count()
            : 0;

        if ($count404 === 0 && $count422 === 0) {
            $this->info('No activity to report.');

            return;
        }

        $notificationClass = config('laravel-watchdog.notifications.daily_security_report');

        Notification::route('watchdog-discord', config('laravel-watchdog.discord.webhooks.security-logs'))
            ->notify(new $notificationClass($count404, $count422));

        $this->info('Report sent to Discord.');
    }
}
