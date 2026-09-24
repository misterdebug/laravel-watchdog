<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class Error500Notification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $url;

    public function __construct(
        protected string $message,
        protected string $file,
        protected int $line
    ) {
        $this->url = request()->fullUrl();
    }

    public function via($notifiable): array
    {
        return ['watchdog-discord'];
    }

    public function toDiscord(): array
    {
        return [
            'content' => sprintf(
                "🔴 **500 Error**\n`%s`\n%s:%d\n%s",
                $this->message,
                $this->file,
                $this->line,
                $this->url
            ),
        ];
    }
}
