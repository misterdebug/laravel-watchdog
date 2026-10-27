<?php

namespace MrDebug\LaravelWatchdog\Listeners;

use Illuminate\Mail\Events\MessageSent;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use Symfony\Component\Mime\Address;
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
                'from'        => $this->addresses($message->getFrom())[0] ?? null,
                'recipients'  => [
                    'to'  => $this->addresses($message->getTo()),
                    'cc'  => $this->addresses($message->getCc()),
                    'bcc' => $this->addresses($message->getBcc()),
                ],
                'subject'     => $message->getSubject(),
                'html'        => $message->getHtmlBody(),
                'text'        => $message->getTextBody(),
                'attachments' => $this->attachments($message),
                'headers'     => $message->getHeaders()->toArray(),
            ]);
        } catch (Throwable $t) {
            logger()->warning('[laravel-watchdog] failed to log sent email: '.$t->getMessage());
        }
    }

    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $a->getAddress(), $addresses);
    }

    private function attachments($message): array
    {
        return collect($message->getAttachments())->map(fn ($part) => [
            'name' => $part->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'),
            'mime' => $part->getMediaType().'/'.$part->getMediaSubtype(),
            'size' => strlen($part->getBody()),
        ])->all();
    }
}
