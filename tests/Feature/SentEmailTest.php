<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class SentEmailTest extends TestCase
{
    public function test_it_logs_a_sent_text_email(): void
    {
        Mail::raw('Hello from the watchdog test suite', function ($message) {
            $message->from('sender@example.com')
                ->to('recipient@example.com')
                ->cc('copy@example.com')
                ->subject('Watchdog subject');
        });

        $email = SentEmail::sole();

        $this->assertSame('sender@example.com', $email->from);
        $this->assertSame([
            'cc' => ['copy@example.com'],
            'to' => ['recipient@example.com'],
            'bcc' => [],
        ], $email->recipients);
        $this->assertSame('Watchdog subject', $email->subject);
        $this->assertSame('Hello from the watchdog test suite', $email->text);
        $this->assertNull($email->html);
        $this->assertSame([], $email->attachments);
        $this->assertIsArray($email->headers);
        $this->assertNotEmpty($email->headers);
    }

    public function test_it_logs_the_html_body(): void
    {
        Mail::html('<h1>Hello</h1>', function ($message) {
            $message->from('sender@example.com')
                ->to('recipient@example.com')
                ->subject('HTML subject');
        });

        $email = SentEmail::sole();

        $this->assertSame('<h1>Hello</h1>', $email->html);
        $this->assertNull($email->text);
    }

    public function test_it_logs_attachments_metadata(): void
    {
        Mail::raw('Email with an attachment', function ($message) {
            $message->from('sender@example.com')
                ->to('recipient@example.com')
                ->subject('Attachment subject')
                ->attachData('file contents', 'report.txt', ['mime' => 'text/plain']);
        });

        $attachments = SentEmail::sole()->attachments;

        $this->assertCount(1, $attachments);
        $this->assertSame('report.txt', $attachments[0]['name']);
        $this->assertSame('text/plain', $attachments[0]['mime']);
        $this->assertSame(strlen('file contents'), $attachments[0]['size']);
    }

    public function test_it_logs_emails_sent_to_multiple_recipients(): void
    {
        Mail::raw('Broadcast message', function ($message) {
            $message->from('sender@example.com')
                ->to(['first@example.com', 'second@example.com'])
                ->bcc('hidden@example.com')
                ->subject('Broadcast');
        });

        $recipients = SentEmail::sole()->recipients;

        $this->assertSame(['first@example.com', 'second@example.com'], $recipients['to']);
        $this->assertSame([], $recipients['cc']);
        $this->assertSame(['hidden@example.com'], $recipients['bcc']);
    }

    #[DefineEnvironment('disableSentEmails')]
    public function test_it_does_not_log_emails_when_the_feature_is_disabled(): void
    {
        Mail::raw('Not logged', function ($message) {
            $message->from('sender@example.com')
                ->to('recipient@example.com')
                ->subject('Not logged');
        });

        $this->assertSame(0, SentEmail::count());
    }
}
