<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\AdminCheck;

use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\Fixtures\UserType;

/**
 * Scenario 1: the configuration has not been published.
 *
 * The package defaults apply: field = "role", values = ["admin", "super-admin"].
 */
final class AdminCheckDefaultConfigTest extends AdminCheckScenarioTestCase
{
    public function test_the_package_defaults_are_used(): void
    {
        $this->assertSame(RoleAdminChecker::class, config('laravel-watchdog.admin_check.checker'));
        $this->assertSame('role', config('laravel-watchdog.admin_check.field'));
        $this->assertSame(['admin', 'super-admin'], config('laravel-watchdog.admin_check.values'));
    }

    public function test_a_user_admin_through_the_type_column_is_not_recognised(): void
    {
        // Reproduces the host application bug: it uses a "type" column
        // (UserType::Admin) while the package defaults look at "role".
        $user = $this->createUser(['type' => UserType::Admin, 'role' => null]);

        $this->assertFalse($this->adminChecker()->isAdmin($user));
    }

    protected function adminUser(): User
    {
        return $this->createUser(['role' => 'admin']);
    }

    protected function regularUser(): User
    {
        // A production admin, invisible to the default configuration.
        return $this->createUser(['type' => UserType::Admin, 'role' => null]);
    }
}
