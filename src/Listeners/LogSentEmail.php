<?php

namespace MrDebug\LaravelWatchdog\Listeners;

use Illuminate\Mail\Events\MessageSent;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use Throwable;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        if (! config('laravel-watchdog.features.sent_emails')) {
            return;
        }

        $message = $event->message;

        try {
            SentEmail::create([
                'to'      => collect($message->getTo())->map->getAddress()->implode(', '),
                'subject' => $message->getSubject(),
                'html'    => $message->getHtmlBody(),
            ]);
        } catch (Throwable $t) {
            logger()->info($t->getMessage());
        }
    }
}
