<?php

namespace MrDebug\LaravelWatchdog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;

class AnalyzeSuspiciousLogins extends Command
{
    protected $signature = 'watchdog:analyze-logins';

    public function getDescription(): string
    {
        return __('laravel-watchdog::commands.analyze.description');
    }

    public function handle(): void
    {
        if (! config('laravel-watchdog.features.login_attempts')) {
            $this->info(__('laravel-watchdog::commands.analyze.disabled'));

            return;
        }

        $windowMinutes = config('laravel-watchdog.suspicious_activity.window_minutes', 60);
        $thresholdEmail = config('laravel-watchdog.suspicious_activity.threshold_per_email', 5);
        $thresholdIp = config('laravel-watchdog.suspicious_activity.threshold_per_ip', 5);

        $since = now()->subMinutes($windowMinutes);

        $byEmail = LoginAttempt::where('success', false)
            ->where('created_at', '>=', $since)
            ->groupBy('email')
            ->selectRaw('email, count(*) as total')
            ->having('total', '>=', $thresholdEmail)
            ->get();

        $byIp = LoginAttempt::where('success', false)
            ->where('created_at', '>=', $since)
            ->groupBy('ip')
            ->selectRaw('ip, count(*) as total')
            ->having('total', '>=', $thresholdIp)
            ->get();

        if ($byEmail->isEmpty() && $byIp->isEmpty()) {
            $this->info(__('laravel-watchdog::commands.analyze.no_activity'));

            return;
        }

        $notificationClass = config('laravel-watchdog.notifications.suspicious_activity');

        Notification::route('watchdog-discord', config('laravel-watchdog.discord.webhooks.logins'))
            ->notify((new $notificationClass($byEmail, $byIp))->locale(app()->getLocale()));

        $this->info(__('laravel-watchdog::commands.analyze.sent'));
    }
}
