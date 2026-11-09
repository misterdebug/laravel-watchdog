<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

use Illuminate\Notifications\Notification;

/**
 * Valid notification class, but without the toDiscord() method required by the
 * package configuration.
 */
class NotificationWithoutDiscord extends Notification
{
    public function via($notifiable): array
    {
        return [];
    }
}
