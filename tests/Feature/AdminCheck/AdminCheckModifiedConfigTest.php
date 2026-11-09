<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\AdminCheck;

use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\Fixtures\UserType;

/**
 * Scenario 3: the published config file has been modified.
 *
 * admin_check.field points to the "type" column used by the host application,
 * whose value is cast to a BackedEnum (UserType).
 */
final class AdminCheckModifiedConfigTest extends AdminCheckScenarioTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set(
            'laravel-watchdog',
            require __DIR__.'/../../Fixtures/config/modified.php'
        );
    }

    public function test_the_modified_configuration_is_loaded(): void
    {
        $this->assertSame(RoleAdminChecker::class, config('laravel-watchdog.admin_check.checker'));
        $this->assertSame('type', config('laravel-watchdog.admin_check.field'));
        $this->assertSame(['admin'], config('laravel-watchdog.admin_check.values'));
    }

    public function test_the_role_column_is_ignored_when_the_field_is_type(): void
    {
        $user = $this->createUser(['role' => 'admin', 'type' => UserType::Member]);

        $this->assertFalse($this->adminChecker()->isAdmin($user));
    }

    public function test_a_backed_enum_admin_value_is_recognised(): void
    {
        $user = $this->createUser(['type' => UserType::Admin]);

        $this->assertSame(UserType::Admin, $user->type);
        $this->assertTrue($this->adminChecker()->isAdmin($user));
    }

    protected function adminUser(): User
    {
        return $this->createUser(['type' => UserType::Admin]);
    }

    protected function regularUser(): User
    {
        return $this->createUser(['type' => UserType::Member]);
    }
}
