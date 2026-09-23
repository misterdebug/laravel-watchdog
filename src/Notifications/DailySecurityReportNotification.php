<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailySecurityReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private int $count404,
        private int $count422
    ) {}

    public function via($notifiable): array
    {
        return ['discord'];
    }

    public function toDiscord(): array
    {
        $lines = [
            '📊 **Daily security report**',
            "\n• 404 : {$this->count404}",
            "• 422 : {$this->count422}",
        ];

        return ['content' => implode("\n", $lines)];
    }
}
