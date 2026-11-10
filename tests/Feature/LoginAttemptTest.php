<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class LoginAttemptTest extends TestCase
{
    public function test_a_successful_regular_login_is_recorded(): void
    {
        $user = $this->createUser(['role' => 'member', 'email' => 'member@example.com']);

        event(new Login('web', $user, false));

        $attempt = LoginAttempt::sole();

        $this->assertSame('member@example.com', $attempt->email);
        $this->assertTrue($attempt->success);
        $this->assertSame('127.0.0.1', $attempt->ip);
        Http::assertNothingSent();
    }

    public function test_a_failed_login_is_recorded(): void
    {
        event(new Failed('web', null, [
            'email' => 'intruder@example.com',
            'password' => 'secret',
        ]));

        $attempt = LoginAttempt::sole();

        $this->assertSame('intruder@example.com', $attempt->email);
        $this->assertFalse($attempt->success);
        Http::assertNothingSent();
    }

    public function test_a_failed_login_without_an_email_is_recorded_with_a_null_email(): void
    {
        event(new Failed('web', null, ['password' => 'secret']));

        $this->assertNull(LoginAttempt::sole()->email);
    }

    public function test_an_admin_login_is_notified_and_not_recorded(): void
    {
        $admin = $this->createUser(['role' => 'admin', 'email' => 'admin@example.com']);

        event(new Login('web', $admin, false));

        $this->assertSame(0, LoginAttempt::count());

        Http::assertSent(function ($request) {
            return $request->url() === self::LOGIN_WEBHOOK
                && $request['content'] === "**[Watchdog Test]** 🔑 **New admin login**\nEmail: admin@example.com\nIP: 127.0.0.1";
        });
    }

    public function test_it_records_the_request_ip_and_user_agent(): void
    {
        $user = $this->createUser(['role' => 'member', 'email' => 'member@example.com']);

        $this->app->instance('request', Request::create(
            '/login',
            'POST',
            server: [
                'REMOTE_ADDR' => '10.10.10.10',
                'HTTP_USER_AGENT' => 'Login-Agent/1.0',
            ]
        ));

        event(new Login('web', $user, false));

        $attempt = LoginAttempt::sole();

        $this->assertSame('10.10.10.10', $attempt->ip);
        $this->assertSame('Login-Agent/1.0', $attempt->user_agent);
    }

    #[DefineEnvironment('disableLoginAttempts')]
    public function test_it_does_not_record_login_attempts_when_the_feature_is_disabled(): void
    {
        $user = $this->createUser(['role' => 'member']);

        event(new Login('web', $user, false));
        event(new Failed('web', null, ['email' => 'member@example.com']));

        $this->assertSame(0, LoginAttempt::count());
        Http::assertNothingSent();
    }
}
