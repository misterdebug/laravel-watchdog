<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class Error500Notification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $message,
        protected string $file,
        protected int $line
    ) {}

    public function via($notifiable): array
    {
        return ['discord'];
    }

    public function toDiscord(): array
    {
        return [
            'content' => sprintf(
                "🔴 **500 Error**\n`%s`\n%s:%d\n%s",
                $this->message,
                $this->file,
                $this->line,
                request()?->fullUrl() ?? 'n/a'
            ),
        ];
    }
}
