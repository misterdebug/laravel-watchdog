<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use MrDebug\LaravelWatchdog\Contracts\AdminChecker;
use MrDebug\LaravelWatchdog\LaravelWatchdogServiceProvider;
use MrDebug\LaravelWatchdog\Support\ExceptionHandler;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RuntimeException;

use function Orchestra\Testbench\load_migration_paths;

#[WithMigration('laravel')]
abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    public const LOGIN_WEBHOOK = 'https://discord.test/webhooks/logins';

    public const ERROR_WEBHOOK = 'https://discord.test/webhooks/errors500';

    public const SECURITY_WEBHOOK = 'https://discord.test/webhooks/security-logs';

    protected function getPackageProviders($app): array
    {
        return [
            LaravelWatchdogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $connection = $this->environmentValue('TEST_DB_CONNECTION', 'mysql');
        $database = $this->environmentValue('TEST_DB_DATABASE', 'watchdog_test');

        if (! str_contains(strtolower($database), 'test')) {
            throw new RuntimeException(sprintf(
                'Refusing to run the Laravel Watchdog test suite against the "%s" database: '
                .'the test database name must contain "test".',
                $database
            ));
        }

        $app['config']->set('database.default', $connection);
        $app['config']->set("database.connections.{$connection}", [
            'driver' => 'mysql',
            'host' => $this->environmentValue('TEST_DB_HOST', 'mysql'),
            'port' => $this->environmentValue('TEST_DB_PORT', '3306'),
            'database' => $database,
            'username' => $this->environmentValue('TEST_DB_USERNAME', 'root'),
            'password' => $this->environmentValue('TEST_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('app.name', 'Watchdog Test');

        // Notifications must actually reach the fake HTTP client during tests.
        $app['config']->set('laravel-watchdog.discord.only_production', false);
        $app['config']->set('laravel-watchdog.discord.webhooks', [
            'logins' => self::LOGIN_WEBHOOK,
            'errors500' => self::ERROR_WEBHOOK,
            'security-logs' => self::SECURITY_WEBHOOK,
        ]);

        $app['config']->set('test.custom_admin_email', 'custom-admin@example.com');
    }

    protected function defineDatabaseMigrations(): void
    {
        load_migration_paths($this->app, __DIR__.'/migrations');
    }

    protected function defineRoutes($router): void
    {
        $router->middleware(['web', 'log-stats'])
            ->get('/tracked-stats', fn () => 'stats')
            ->name('tracked.stats');

        $router->middleware(['web', 'log-stats'])
            ->post('/tracked-stats', fn () => 'stats')
            ->name('tracked.stats.store');

        $router->middleware(['web', 'log-activity'])
            ->get('/tracked-activity', fn () => 'activity')
            ->name('tracked.activity');

        $router->middleware(['web', 'log-activity'])
            ->post('/tracked-activity', fn () => 'activity')
            ->name('tracked.activity.store');

        $router->get('/boom', function () {
            throw new RuntimeException('Boom from the watchdog test suite');
        })->name('boom');

        $router->get('/abort-500', fn () => abort(500, 'Explicit HTTP 500'))->name('abort.500');

        $router->post('/validate-me', function (Request $request) {
            $request->validate([
                'name' => ['required'],
                'email' => ['required', 'email'],
            ]);

            return 'valid';
        })->name('validate.me');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed prefix keeps the compiled Blade component names stable
        // across test classes, so cached views never reference a namespace
        // that was registered by another test.
        Blade::anonymousComponentPath(
            __DIR__.'/Fixtures/views/components',
            'watchdog-test-components'
        );

        Http::fake();

        ExceptionHandler::register(new Exceptions(
            $this->app->make(ExceptionHandlerContract::class)
        ));
    }

    protected function adminChecker(): AdminChecker
    {
        return $this->app->make(AdminChecker::class);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user-'.Str::random(10).'@example.com',
            'password' => 'password',
        ], $attributes));
    }

    protected function disableStats($app): void
    {
        $app['config']->set('laravel-watchdog.features.stats', false);
    }

    protected function disableActivity($app): void
    {
        $app['config']->set('laravel-watchdog.features.activity', false);
    }

    protected function disableSecurity404($app): void
    {
        $app['config']->set('laravel-watchdog.features.security_404', false);
    }

    protected function disableSecurity422($app): void
    {
        $app['config']->set('laravel-watchdog.features.security_422', false);
    }

    protected function disableError500($app): void
    {
        $app['config']->set('laravel-watchdog.features.error500_alert', false);
    }

    protected function disableLoginAttempts($app): void
    {
        $app['config']->set('laravel-watchdog.features.login_attempts', false);
    }

    protected function disableSentEmails($app): void
    {
        $app['config']->set('laravel-watchdog.features.sent_emails', false);
    }

    private function environmentValue(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
