<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use MrDebug\LaravelWatchdog\Models\SecurityLog;
use MrDebug\LaravelWatchdog\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class SecurityLogTest extends TestCase
{
    public function test_it_logs_404_errors(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.30'])
            ->withHeaders([
                'User-Agent' => 'missing-page-agent',
                'Referer' => 'https://example.test/dashboard',
            ])
            ->get('/definitely-missing?from=test')
            ->assertNotFound();

        $log = SecurityLog::sole();

        $this->assertSame(404, $log->response_code);
        $this->assertSame('GET', $log->method);
        $this->assertSame('http://localhost/definitely-missing?from=test', $log->url);
        $this->assertNull($log->route_name);
        $this->assertNull($log->user_id);
        $this->assertSame('10.0.0.30', $log->ip);
        $this->assertSame('missing-page-agent', $log->user_agent);
        $this->assertSame('https://example.test/dashboard', $log->referer);
        $this->assertSame(['input' => ['from' => 'test']], $log->params);
    }

    public function test_it_attaches_the_authenticated_user_to_a_404_log(): void
    {
        $user = $this->createUser(['role' => 'member']);

        $this->actingAs($user)->get('/definitely-missing')->assertNotFound();

        $this->assertSame($user->getAuthIdentifier(), SecurityLog::sole()->user_id);
    }

    public function test_it_excludes_sensitive_parameters_from_404_logs(): void
    {
        config()->set('laravel-watchdog.security_excluded_params', ['secret']);

        $this->get('/definitely-missing?secret=hidden&visible=shown')->assertNotFound();

        $this->assertSame(
            ['input' => ['visible' => 'shown']],
            SecurityLog::sole()->params
        );
    }

    public function test_it_logs_422_validation_errors(): void
    {
        $this->postJson('/validate-me', ['email' => 'not-an-email'])
            ->assertUnprocessable();

        $log = SecurityLog::sole();

        $this->assertSame(422, $log->response_code);
        $this->assertSame('POST', $log->method);
        $this->assertSame('validate.me', $log->route_name);
        $this->assertIsArray($log->params['errors']);
        $this->assertArrayHasKey('name', $log->params['errors']);
        $this->assertArrayHasKey('email', $log->params['errors']);
        $this->assertSame(['email' => 'not-an-email'], $log->params['input']);
    }

    #[DefineEnvironment('disableSecurity404')]
    public function test_it_does_not_log_404_when_the_feature_is_disabled(): void
    {
        $this->get('/definitely-missing')->assertNotFound();

        $this->assertSame(0, SecurityLog::count());
    }

    #[DefineEnvironment('disableSecurity422')]
    public function test_it_does_not_log_422_when_the_feature_is_disabled(): void
    {
        $this->postJson('/validate-me', [])->assertUnprocessable();

        $this->assertSame(0, SecurityLog::count());
    }
}
