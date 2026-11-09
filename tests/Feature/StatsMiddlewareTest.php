<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use MrDebug\LaravelWatchdog\Models\StatsLog;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class StatsMiddlewareTest extends TestCase
{
    public function test_it_logs_a_guest_visit(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->withHeaders([
                'User-Agent' => 'watchdog-test-agent/1.0',
                'Referer' => 'https://example.test/previous-page',
            ])
            ->get('/tracked-stats?utm_source=newsletter')
            ->assertOk();

        $log = StatsLog::sole();

        $this->assertSame('tracked.stats', $log->route_name);
        $this->assertSame('GET', $log->method);
        $this->assertSame('http://localhost/tracked-stats?utm_source=newsletter', $log->url);
        $this->assertSame(['utm_source' => 'newsletter'], $log->params);
        $this->assertSame('10.0.0.10', $log->ip);
        $this->assertNotNull($log->session_id);
        $this->assertSame('watchdog-test-agent/1.0', $log->user_agent);
        $this->assertSame('https://example.test/previous-page', $log->referer);
        $this->assertSame(200, $log->response_code);
        $this->assertIsInt($log->duration_ms);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_it_excludes_sensitive_body_parameters(): void
    {
        $this->post('/tracked-stats', [
            'name' => 'John',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            '_token' => 'csrf-token',
        ])->assertOk();

        $log = StatsLog::sole();

        $this->assertSame(['name' => 'John'], $log->params);
        $this->assertSame('POST', $log->method);
        $this->assertSame('tracked.stats.store', $log->route_name);
    }

    public function test_it_logs_visits_from_regular_users(): void
    {
        $user = $this->createUser(['role' => 'member']);

        $this->actingAs($user)->get('/tracked-stats')->assertOk();

        $this->assertSame(1, StatsLog::count());
    }

    public function test_it_does_not_log_admin_visits(): void
    {
        $admin = $this->createUser(['role' => 'admin']);

        $this->actingAs($admin)->get('/tracked-stats')->assertOk();

        $this->assertSame(0, StatsLog::count());
    }

    #[DefineEnvironment('disableStats')]
    public function test_it_does_not_log_when_the_feature_is_disabled(): void
    {
        $this->get('/tracked-stats')->assertOk();

        $this->assertSame(0, StatsLog::count());
    }
}
