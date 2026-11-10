<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use MrDebug\LaravelWatchdog\Notifications\Channels\WatchdogDiscordChannel;
use MrDebug\LaravelWatchdog\Notifications\LoginNotification;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class DiscordNotificationTest extends TestCase
{
    public function test_the_channel_is_disabled_outside_production_when_only_production_is_enabled(): void
    {
        config()->set('laravel-watchdog.discord.only_production', true);

        $this->assertFalse(WatchdogDiscordChannel::isEnabled());

        Notification::route('watchdog-discord', self::LOGIN_WEBHOOK)
            ->notify(new LoginNotification('10.0.0.1', 'admin@example.com'));

        Http::assertNothingSent();
    }

    public function test_the_channel_is_enabled_when_only_production_is_disabled(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);

        $this->assertTrue(WatchdogDiscordChannel::isEnabled());

        Notification::route('watchdog-discord', self::LOGIN_WEBHOOK)
            ->notify(new LoginNotification('10.0.0.1', 'admin@example.com'));

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === self::LOGIN_WEBHOOK
                && $request->method() === 'POST'
                && $request['content'] === "**[Watchdog Test]** 🔑 **New admin login**\nEmail: admin@example.com\nIP: 10.0.0.1";
        });
    }

    public function test_no_request_is_sent_without_a_webhook_url(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);

        Notification::route('watchdog-discord', null)
            ->notify(new LoginNotification('10.0.0.1', 'admin@example.com'));

        Http::assertNothingSent();
    }

    public function test_http_failures_are_caught_and_logged(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);

        // Replace the default wildcard fake so the URL-specific 500 response is the first matching stub.
        Http::swap(new Factory());
        Http::fake([
            self::LOGIN_WEBHOOK => Http::response('server error', 500),
        ]);

        Log::spy();

        Notification::route('watchdog-discord', self::LOGIN_WEBHOOK)
            ->notify(new LoginNotification('10.0.0.1', 'admin@example.com'));

        Http::assertSentCount(1);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_every_discord_payload_is_prefixed_with_the_app_name(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);

        $custom = new class extends \Illuminate\Notifications\Notification
        {
            public function via($notifiable): array
            {
                return ['watchdog-discord'];
            }

            public function toDiscord(): array
            {
                return ['content' => 'Custom message'];
            }
        };

        Notification::route('watchdog-discord', self::LOGIN_WEBHOOK)->notify($custom);

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === self::LOGIN_WEBHOOK
                && $request['content'] === '**[Watchdog Test]** Custom message';
        });
    }

    public function test_no_prefix_is_added_when_the_app_name_is_empty(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);
        config()->set('app.name', '');

        Notification::route('watchdog-discord', self::LOGIN_WEBHOOK)
            ->notify(new LoginNotification('10.0.0.1', 'admin@example.com'));

        Http::assertSent(function (ClientRequest $request) {
            return $request['content'] === "🔑 **New admin login**\nEmail: admin@example.com\nIP: 10.0.0.1";
        });
    }

    public function test_the_default_notifiable_route_is_resolved_by_the_channel(): void
    {
        config()->set('laravel-watchdog.discord.only_production', false);

        $notifiable = (new AnonymousNotifiable())->route('watchdog-discord', self::LOGIN_WEBHOOK);

        $this->assertSame(self::LOGIN_WEBHOOK, $notifiable->routeNotificationFor('watchdog-discord'));
    }
}
