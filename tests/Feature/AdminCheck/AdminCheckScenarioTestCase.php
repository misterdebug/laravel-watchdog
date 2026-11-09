<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\AdminCheck;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Http;
use MrDebug\LaravelWatchdog\Models\ActivityLog;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Models\StatsLog;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\TestCase;

/**
 * Behaviour shared by every admin_check scenario.
 *
 * Each child class only has to provide the admin user and a regular user for
 * its own configuration, then these tests assert that every consumer of the
 * AdminChecker contract behaves consistently:
 *
 *   - the log-stats middleware
 *   - the log-activity middleware
 *   - the login listener
 */
abstract class AdminCheckScenarioTestCase extends TestCase
{
    abstract protected function adminUser(): User;

    abstract protected function regularUser(): User;

    public function test_the_admin_checker_recognises_the_admin_user(): void
    {
        $this->assertTrue($this->adminChecker()->isAdmin($this->adminUser()));
    }

    public function test_the_admin_checker_rejects_the_regular_user(): void
    {
        $this->assertFalse($this->adminChecker()->isAdmin($this->regularUser()));
    }

    public function test_the_stats_middleware_skips_the_admin_user(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/tracked-stats')->assertOk();

        $this->assertSame(0, StatsLog::count());
    }

    public function test_the_stats_middleware_logs_the_regular_user(): void
    {
        $regular = $this->regularUser();

        $this->actingAs($regular)->get('/tracked-stats')->assertOk();

        $this->assertSame(1, StatsLog::count());
    }

    public function test_the_activity_middleware_skips_the_admin_user(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/tracked-activity')->assertOk();

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_the_activity_middleware_logs_the_regular_user(): void
    {
        $regular = $this->regularUser();

        $this->actingAs($regular)->get('/tracked-activity')->assertOk();

        $this->assertSame(1, ActivityLog::count());
    }

    public function test_an_admin_login_is_notified_and_not_recorded(): void
    {
        $admin = $this->adminUser();

        event(new Login('web', $admin, false));

        $this->assertSame(0, LoginAttempt::count());

        Http::assertSent(function ($request) use ($admin) {
            return $request->url() === self::LOGIN_WEBHOOK
                && str_contains($request['content'], $admin->email);
        });
    }

    public function test_a_regular_login_is_recorded_without_a_notification(): void
    {
        $regular = $this->regularUser();

        event(new Login('web', $regular, false));

        $this->assertSame(1, LoginAttempt::count());
        $this->assertSame($regular->email, LoginAttempt::sole()->email);
        Http::assertNothingSent();
    }
}
