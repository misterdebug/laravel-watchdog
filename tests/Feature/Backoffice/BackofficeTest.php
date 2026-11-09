<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\Backoffice;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MrDebug\LaravelWatchdog\Models\ActivityLog;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Models\SecurityLog;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use MrDebug\LaravelWatchdog\Models\StatsLog;
use MrDebug\LaravelWatchdog\Support\LaravelLogParser;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class BackofficeTest extends TestCase
{
    private string $logDirectory;

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->get('/login')->name('login');

        $router->middleware('web')->group(function () {
            require __DIR__.'/../../../routes/watchdog-backoffice.php';
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Blade::anonymousComponentPath(__DIR__.'/../../Fixtures/views/components');

        $this->logDirectory = storage_path('framework/testing/watchdog-backoffice-'.Str::random(8));
        File::ensureDirectoryExists($this->logDirectory);
        config()->set('laravel-watchdog.backoffice.laravel_logs.directory', $this->logDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_the_backoffice_routes_require_authentication(): void
    {
        $this->get(route('watchdog.stats-logs.index'))->assertRedirect('/login');
    }

    public function test_an_authenticated_user_can_read_every_backoffice_page(): void
    {
        $user = $this->createUser();

        StatsLog::create([
            'route_name' => 'tracked.stats',
            'method' => 'GET',
            'url' => 'https://example.test/stats',
            'response_code' => 200,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'route_name' => 'tracked.activity',
            'method' => 'GET',
            'url' => 'https://example.test/activity',
            'response_code' => 200,
        ]);

        SecurityLog::create([
            'user_id' => $user->id,
            'route_name' => 'tracked.security',
            'method' => 'GET',
            'url' => 'https://example.test/missing',
            'response_code' => 404,
        ]);

        LoginAttempt::create([
            'email' => $user->email,
            'ip' => '127.0.0.1',
            'success' => true,
        ]);

        SentEmail::create([
            'from' => 'sender@example.test',
            'recipients' => ['to' => [$user->email], 'cc' => [], 'bcc' => []],
            'subject' => 'Test email',
            'html' => '<p>Hello</p>',
        ]);

        File::put($this->logDirectory.'/laravel.log', '[2026-01-01 10:00:00] production.INFO: Hello from the log');

        $this->actingAs($user)
            ->get(route('watchdog.stats-logs.index'))
            ->assertOk()
            ->assertSee('Stats logs');

        $this->actingAs($user)
            ->get(route('watchdog.activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity logs');

        $this->actingAs($user)
            ->get(route('watchdog.security-logs.index'))
            ->assertOk()
            ->assertSee('Security logs');

        $this->actingAs($user)
            ->get(route('watchdog.login-attempts.index'))
            ->assertOk()
            ->assertSee($user->email);

        $this->actingAs($user)
            ->get(route('watchdog.sent-emails.index'))
            ->assertOk()
            ->assertSee('Test email');

        $this->actingAs($user)
            ->get(route('watchdog.laravel-logs.index', ['file' => 'laravel.log']))
            ->assertOk()
            ->assertSee('Hello from the log');
    }

    public function test_a_database_row_can_be_deleted(): void
    {
        $user = $this->createUser();

        $log = StatsLog::create([
            'method' => 'GET',
            'url' => 'https://example.test/stats',
            'response_code' => 200,
        ]);

        $this->actingAs($user)
            ->delete(route('watchdog.stats-logs.destroy', $log->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('watchdog_stats_logs', ['id' => $log->id]);
    }

    public function test_a_complete_log_entry_can_be_deleted(): void
    {
        $user = $this->createUser();

        File::put($this->logDirectory.'/laravel.log', <<<'LOG'
[2026-01-01 10:00:00] production.ERROR: First
#0 trace of the first entry
[2026-01-01 10:01:00] production.INFO: Second
LOG);

        $parser = new LaravelLogParser();
        $entries = $parser->entries($this->logDirectory, 'laravel.log');

        $this->actingAs($user)
            ->delete(route('watchdog.laravel-logs.destroy'), [
                'file' => 'laravel.log',
                'entry' => $entries[0]['id'],
            ])
            ->assertRedirect();

        $content = File::get($this->logDirectory.'/laravel.log');

        $this->assertStringNotContainsString('First', $content);
        $this->assertStringNotContainsString('#0 trace', $content);
        $this->assertStringContainsString('Second', $content);
    }

    public function test_an_invalid_log_file_is_not_listed(): void
    {
        $user = $this->createUser();

        File::put($this->logDirectory.'/not-a-laravel-log.log', '[2026-01-01 10:00:00] production.INFO: Secret');

        $this->actingAs($user)
            ->get(route('watchdog.laravel-logs.index', ['file' => 'not-a-laravel-log.log']))
            ->assertOk()
            ->assertDontSee('Secret');
    }
}
