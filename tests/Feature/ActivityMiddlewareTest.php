<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use MrDebug\LaravelWatchdog\Models\ActivityLog;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class ActivityMiddlewareTest extends TestCase
{
    public function test_it_does_not_log_guests(): void
    {
        $this->get('/tracked-activity')->assertOk();

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_it_logs_authenticated_regular_users(): void
    {
        $user = $this->createUser(['role' => 'member']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->actingAs($user)
            ->get('/tracked-activity?tab=profile')
            ->assertOk();

        $log = ActivityLog::sole();

        $this->assertSame($user->getAuthIdentifier(), $log->user_id);
        $this->assertSame('tracked.activity', $log->route_name);
        $this->assertSame('GET', $log->method);
        $this->assertSame(['tab' => 'profile'], $log->params);
        $this->assertSame('10.0.0.20', $log->ip);
        $this->assertSame(200, $log->response_code);
    }

    public function test_it_does_not_log_admin_activity(): void
    {
        $admin = $this->createUser(['role' => 'admin']);

        $this->actingAs($admin)->get('/tracked-activity')->assertOk();

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_it_excludes_sensitive_body_parameters(): void
    {
        $user = $this->createUser(['role' => 'member']);

        $this->actingAs($user)->post('/tracked-activity', [
            'note' => 'updated',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            '_token' => 'csrf-token',
        ])->assertOk();

        $this->assertSame(['note' => 'updated'], ActivityLog::sole()->params);
    }

    #[DefineEnvironment('disableActivity')]
    public function test_it_does_not_log_when_the_feature_is_disabled(): void
    {
        $user = $this->createUser(['role' => 'member']);

        $this->actingAs($user)->get('/tracked-activity')->assertOk();

        $this->assertSame(0, ActivityLog::count());
    }
}
