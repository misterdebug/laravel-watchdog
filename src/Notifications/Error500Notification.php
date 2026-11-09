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
            'content' => __('laravel-watchdog::notifications.error500.content', [
                'message' => $this->message,
                'file' => $this->file,
                'line' => $this->line,
                'url' => $this->url,
            ]),
        ];
    }
}
