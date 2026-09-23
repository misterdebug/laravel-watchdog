<?php

namespace MrDebug\LaravelWatchdog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;

class AnalyzeSuspiciousLogins extends Command
{
    protected $signature = 'watchdog:analyze-logins';

    protected $description = 'Analyzes suspicious login attempts and alerts on Discord';

    public function handle(): void
    {
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
            $this->info('Aucune activité suspecte détectée.');

            return;
        }

        $notificationClass = config('laravel-watchdog.notifications.suspicious_activity');

        Notification::route('discord', config('laravel-watchdog.discord.webhooks.logins'))
            ->notify(new $notificationClass($byEmail, $byIp));

        $this->info('Alert sent to Discord.');
    }
}
