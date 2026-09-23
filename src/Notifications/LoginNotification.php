<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ?string $ip,
        private string $email
    ) {}

    public function via($notifiable): array
    {
        return ['discord'];
    }

    public function toDiscord(): array
    {
        return [
            'content' => sprintf(
                "🔑 **New admin login**\nEmail: %s\nIP: %s",
                $this->email,
                $this->ip ?? 'unknown'
            ),
        ];
    }
}
