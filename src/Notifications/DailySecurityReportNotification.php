<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Notifications\Notification;

class DailySecurityReportNotification extends Notification
{
    public function __construct(
        private int $count404,
        private int $count422
    ) {}

    public function via($notifiable): array
    {
        return ['watchdog-discord'];
    }

    public function toDiscord(): array
    {
        return [
            'content' => __('laravel-watchdog::notifications.daily_security_report.content', [
                'count404' => $this->count404,
                'count422' => $this->count422,
            ]),
        ];
    }
}
