<?php

namespace MrDebug\LaravelWatchdog\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class SuspiciousActivityNotification extends Notification
{
    public function __construct(
        private Collection $byEmail,
        private Collection $byIp
    ) {}

    public function via($notifiable): array
    {
        return ['watchdog-discord'];
    }

    public function toDiscord(): array
    {
        $lines = [__('laravel-watchdog::notifications.suspicious_activity.header')];

        if ($this->byEmail->isNotEmpty()) {
            $lines[] = "\n".__('laravel-watchdog::notifications.suspicious_activity.by_email');
            foreach ($this->byEmail as $row) {
                $lines[] = $this->line($row->email, (int) $row->total);
            }
        }

        if ($this->byIp->isNotEmpty()) {
            $lines[] = "\n".__('laravel-watchdog::notifications.suspicious_activity.by_ip');
            foreach ($this->byIp as $row) {
                $lines[] = $this->line($row->ip, (int) $row->total);
            }
        }

        return ['content' => implode("\n", $lines)];
    }

    private function line(string $identifier, int $count): string
    {
        return __('laravel-watchdog::notifications.suspicious_activity.line', [
            'identifier' => $identifier,
            'failures' => trans_choice(
                'laravel-watchdog::notifications.suspicious_activity.failure',
                $count,
                ['count' => $count]
            ),
        ]);
    }
}
