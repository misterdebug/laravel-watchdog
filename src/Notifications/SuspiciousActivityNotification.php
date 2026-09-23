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
        return ['discord'];
    }

    public function toDiscord(): array
    {
        $lines = ['🚨 **Suspicious activity detected**'];

        if ($this->byEmail->isNotEmpty()) {
            $lines[] = "\n**By email :**";
            foreach ($this->byEmail as $row) {
                $lines[] = "• {$row->email} : {$row->total} failures";
            }
        }

        if ($this->byIp->isNotEmpty()) {
            $lines[] = "\n**By IP :**";
            foreach ($this->byIp as $row) {
                $lines[] = "• {$row->ip} : {$row->total} failures";
            }
        }

        return ['content' => implode("\n", $lines)];
    }
}
