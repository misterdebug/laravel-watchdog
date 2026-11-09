<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use MrDebug\LaravelWatchdog\Models\ActivityLog;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Models\SecurityLog;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use MrDebug\LaravelWatchdog\Models\StatsLog;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class ConsoleCommandsTest extends TestCase
{
    public function test_the_prune_command_deletes_records_older_than_the_retention_window(): void
    {
        $this->createRecord(StatsLog::class, 31, [
            'method' => 'GET', 'url' => 'http://localhost/old', 'response_code' => 200,
        ]);
        $this->createRecord(StatsLog::class, 0, [
            'method' => 'GET', 'url' => 'http://localhost/new', 'response_code' => 200,
        ]);

        $this->createRecord(ActivityLog::class, 91, [
            'method' => 'GET', 'url' => 'http://localhost/old',
        ]);
        $this->createRecord(ActivityLog::class, 0, [
            'method' => 'GET', 'url' => 'http://localhost/new',
        ]);

        $this->createRecord(SecurityLog::class, 91, [
            'method' => 'GET', 'url' => 'http://localhost/old', 'response_code' => 404,
        ]);
        $this->createRecord(SecurityLog::class, 0, [
            'method' => 'GET', 'url' => 'http://localhost/new', 'response_code' => 404,
        ]);

        $this->createRecord(LoginAttempt::class, 91, [
            'email' => 'old@example.com', 'success' => false,
        ]);
        $this->createRecord(LoginAttempt::class, 0, [
            'email' => 'new@example.com', 'success' => false,
        ]);

        $this->createRecord(SentEmail::class, 31, [
            'from' => 'sender@example.com', 'subject' => 'Old',
        ]);
        $this->createRecord(SentEmail::class, 0, [
            'from' => 'sender@example.com', 'subject' => 'New',
        ]);

        $this->artisan('watchdog:prune')
            ->expectsOutputToContain('StatsLog: 1 deleted.')
            ->expectsOutputToContain('ActivityLog: 1 deleted.')
            ->expectsOutputToContain('SecurityLog: 1 deleted.')
            ->expectsOutputToContain('LoginAttempt: 1 deleted.')
            ->expectsOutputToContain('SentEmail: 1 deleted.')
            ->expectsOutput('5 Watchdog records deleted.')
            ->assertSuccessful();

        $this->assertDatabaseCount('watchdog_stats_logs', 1);
        $this->assertDatabaseCount('watchdog_activity_logs', 1);
        $this->assertDatabaseCount('watchdog_security_logs', 1);
        $this->assertDatabaseCount('watchdog_login_attempts', 1);
        $this->assertDatabaseCount('watchdog_sent_emails', 1);
    }

    public function test_the_prune_command_uses_the_configured_retention_per_model(): void
    {
        config()->set('laravel-watchdog.prune.stats_logs', 1);

        $this->createRecord(StatsLog::class, 2, [
            'method' => 'GET', 'url' => 'http://localhost/old', 'response_code' => 200,
        ]);

        $this->artisan('watchdog:prune')->assertSuccessful();

        $this->assertDatabaseCount('watchdog_stats_logs', 0);
    }

    public function test_the_analyze_logins_command_alerts_on_suspicious_activity(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->createRecord(LoginAttempt::class, 0, [
                'email' => 'attacker@example.com',
                'ip' => '10.0.0.66',
                'success' => false,
            ]);
        }

        $this->artisan('watchdog:analyze-logins')
            ->expectsOutput('Alert sent to Discord.')
            ->assertSuccessful();

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === self::LOGIN_WEBHOOK
                && str_contains($request['content'], 'attacker@example.com')
                && str_contains($request['content'], '10.0.0.66');
        });
    }

    public function test_the_analyze_logins_command_ignores_activity_below_the_threshold(): void
    {
        foreach (range(1, 4) as $ignored) {
            $this->createRecord(LoginAttempt::class, 0, [
                'email' => 'attacker@example.com',
                'ip' => '10.0.0.66',
                'success' => false,
            ]);
        }

        $this->artisan('watchdog:analyze-logins')
            ->expectsOutput('No suspicious activity detected.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_analyze_logins_command_ignores_attempts_outside_the_window(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->createRecordAt(LoginAttempt::class, now()->subMinutes(90), [
                'email' => 'attacker@example.com',
                'ip' => '10.0.0.66',
                'success' => false,
            ]);
        }

        $this->artisan('watchdog:analyze-logins')
            ->expectsOutput('No suspicious activity detected.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_analyze_logins_command_can_be_disabled(): void
    {
        config()->set('laravel-watchdog.features.login_attempts', false);

        $this->artisan('watchdog:analyze-logins')
            ->expectsOutput('Login attempt monitoring is disabled.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_daily_security_report_counts_yesterdays_404_and_422(): void
    {
        $this->createRecordAt(SecurityLog::class, now()->subDay(), [
            'method' => 'GET', 'url' => 'http://localhost/missing', 'response_code' => 404,
        ]);
        $this->createRecordAt(SecurityLog::class, now()->subDay(), [
            'method' => 'POST', 'url' => 'http://localhost/invalid', 'response_code' => 422,
        ]);
        $this->createRecordAt(SecurityLog::class, now(), [
            'method' => 'GET', 'url' => 'http://localhost/today', 'response_code' => 404,
        ]);
        $this->createRecordAt(SecurityLog::class, now()->subDays(2), [
            'method' => 'GET', 'url' => 'http://localhost/two-days-ago', 'response_code' => 404,
        ]);

        $this->artisan('watchdog:daily-security-report')
            ->expectsOutput('Report sent to Discord.')
            ->assertSuccessful();

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === self::SECURITY_WEBHOOK
                && str_contains($request['content'], '• 404 : 1')
                && str_contains($request['content'], '• 422 : 1');
        });
    }

    public function test_the_daily_security_report_respects_disabled_features(): void
    {
        config()->set('laravel-watchdog.features.security_404', false);

        $this->createRecordAt(SecurityLog::class, now()->subDay(), [
            'method' => 'GET', 'url' => 'http://localhost/missing', 'response_code' => 404,
        ]);
        $this->createRecordAt(SecurityLog::class, now()->subDay(), [
            'method' => 'POST', 'url' => 'http://localhost/invalid', 'response_code' => 422,
        ]);

        $this->artisan('watchdog:daily-security-report')
            ->expectsOutput('Report sent to Discord.')
            ->assertSuccessful();

        Http::assertSent(function (ClientRequest $request) {
            return str_contains($request['content'], '• 404 : 0')
                && str_contains($request['content'], '• 422 : 1');
        });
    }

    public function test_the_daily_security_report_says_when_security_monitoring_is_disabled(): void
    {
        config()->set('laravel-watchdog.features.security_404', false);
        config()->set('laravel-watchdog.features.security_422', false);

        $this->artisan('watchdog:daily-security-report')
            ->expectsOutput('Security monitoring is disabled.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_daily_security_report_says_when_there_is_nothing_to_report(): void
    {
        $this->artisan('watchdog:daily-security-report')
            ->expectsOutput('No activity to report.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function createRecord(string $model, int $daysAgo, array $attributes = []): void
    {
        $this->createRecordAt($model, now()->subDays($daysAgo), $attributes);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function createRecordAt(string $model, Carbon $createdAt, array $attributes = []): void
    {
        /** @var Model $record */
        $record = new $model($attributes);
        $record->created_at = $createdAt;
        $record->save();
    }
}
