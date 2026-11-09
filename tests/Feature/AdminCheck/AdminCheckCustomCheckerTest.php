<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature\AdminCheck;

use MrDebug\LaravelWatchdog\Tests\Fixtures\CustomAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\Fixtures\UserType;

/**
 * Scenario 4: the application provides its own AdminChecker implementation.
 *
 * The custom checker (and its constructor dependency) must be resolved through
 * the container, and admin_check.field / admin_check.values must be ignored.
 */
final class AdminCheckCustomCheckerTest extends AdminCheckScenarioTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set(
            'laravel-watchdog',
            require __DIR__.'/../../Fixtures/config/custom-checker.php'
        );
    }

    public function test_the_custom_checker_is_resolved_from_the_container(): void
    {
        $this->assertInstanceOf(CustomAdminChecker::class, $this->adminChecker());
    }

    public function test_the_configured_field_and_values_are_ignored(): void
    {
        // This user would be an admin with RoleAdminChecker, but the custom
        // checker only looks at the email address.
        $user = $this->createUser([
            'role' => 'super-admin',
            'type' => UserType::Admin,
        ]);

        $this->assertFalse($this->adminChecker()->isAdmin($user));
    }

    public function test_the_custom_checker_uses_its_own_admin_rule(): void
    {
        $user = $this->createUser(['email' => 'custom-admin@example.com']);

        $this->assertTrue($this->adminChecker()->isAdmin($user));
    }

    protected function adminUser(): User
    {
        return $this->createUser([
            'email' => 'custom-admin@example.com',
            'role' => null,
            'type' => UserType::Member,
        ]);
    }

    protected function regularUser(): User
    {
        return $this->createUser([
            'role' => 'super-admin',
            'type' => UserType::Admin,
        ]);
    }
}
