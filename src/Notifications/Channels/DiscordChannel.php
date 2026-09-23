<?php

namespace MrDebug\LaravelWatchdog\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (config('laravel-watchdog.discord.only_production', true) && ! app()->isProduction()) {
            return;
        }

        $url = $notifiable->routeNotificationFor('discord');

        if (empty($url)) {
            Log::warning('[laravel-watchdog] Discord webhook URL not configured', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        try {
            Http::throw()->post($url, $notification->toDiscord());
        } catch (\Exception $e) {
            Log::error('[laravel-watchdog] Discord webhook failed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
