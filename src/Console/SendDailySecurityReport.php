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
        $yesterday = now()->yesterday();

        $count404 = SecurityLog::where('response_code', 404)
            ->whereDate('created_at', $yesterday)
            ->count();

        $count422 = SecurityLog::where('response_code', 422)
            ->whereDate('created_at', $yesterday)
            ->count();

        if ($count404 === 0 && $count422 === 0) {
            $this->info('Aucune activité à signaler.');

            return;
        }

        $notificationClass = config('laravel-watchdog.notifications.daily_security_report');

        Notification::route('discord', config('laravel-watchdog.discord.webhooks.security-logs'))
            ->notify(new $notificationClass($count404, $count422));

        $this->info('Report sent to Discord.');
    }
}
