<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class Error500Test extends TestCase
{
    public function test_it_sends_a_discord_alert_for_a_server_error(): void
    {
        $this->get('/boom')->assertStatus(500);

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === self::ERROR_WEBHOOK
                && str_contains($request['content'], 'Boom from the watchdog test suite')
                && str_contains($request['content'], '/boom');
        });
    }

    public function test_it_does_not_alert_on_http_exceptions(): void
    {
        $this->get('/abort-500')->assertStatus(500);
        $this->get('/definitely-missing')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_it_rate_limits_alerts_per_exception_class(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->get('/boom')->assertStatus(500);
        }

        Http::assertSentCount(5);
    }

    public function test_it_does_not_alert_outside_production_when_only_production_is_enabled(): void
    {
        config()->set('laravel-watchdog.discord.only_production', true);

        $this->get('/boom')->assertStatus(500);

        Http::assertNothingSent();
    }

    #[DefineEnvironment('disableError500')]
    public function test_it_does_not_alert_when_the_feature_is_disabled(): void
    {
        $this->get('/boom')->assertStatus(500);

        Http::assertNothingSent();
    }
}
