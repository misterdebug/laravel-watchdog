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
        return ['watchdog-discord'];
    }

    public function toDiscord(): array
    {
        return [
            'content' => __('laravel-watchdog::notifications.login.content', [
                'email' => $this->email,
                'ip' => $this->ip ?? __('laravel-watchdog::notifications.login.unknown_ip'),
            ]),
        ];
    }
}
