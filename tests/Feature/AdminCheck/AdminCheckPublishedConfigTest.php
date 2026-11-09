<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\AdminCheck;

use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;

/**
 * Scenario 2: the config file has been published.
 *
 * The published file is loaded before the provider registers, exactly like a
 * real application's config/laravel-watchdog.php file. It mirrors the package
 * defaults for admin_check (behaviour must stay the same) while containing a
 * distinct prune value used to prove that the published file wins.
 */
final class AdminCheckPublishedConfigTest extends AdminCheckScenarioTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set(
            'laravel-watchdog',
            require __DIR__.'/../../Fixtures/config/published.php'
        );
    }

    public function test_the_published_configuration_is_loaded(): void
    {
        $this->assertSame(15, config('laravel-watchdog.prune.stats_logs'));
        $this->assertSame(RoleAdminChecker::class, config('laravel-watchdog.admin_check.checker'));
        $this->assertSame('role', config('laravel-watchdog.admin_check.field'));
        $this->assertSame(['admin', 'super-admin'], config('laravel-watchdog.admin_check.values'));
    }

    protected function adminUser(): User
    {
        return $this->createUser(['role' => 'super-admin']);
    }

    protected function regularUser(): User
    {
        return $this->createUser(['role' => 'member']);
    }
}
