<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Unit;

use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\PlainUser;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\Fixtures\UserType;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class RoleAdminCheckerTest extends TestCase
{
    private RoleAdminChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = new RoleAdminChecker();
    }

    public function test_it_rejects_a_null_user(): void
    {
        $this->assertFalse($this->checker->isAdmin(null));
    }

    public function test_it_accepts_every_default_admin_role(): void
    {
        $this->assertTrue($this->checker->isAdmin(new User(['role' => 'admin'])));
        $this->assertTrue($this->checker->isAdmin(new User(['role' => 'super-admin'])));
    }

    public function test_it_rejects_other_roles(): void
    {
        $this->assertFalse($this->checker->isAdmin(new User(['role' => 'member'])));
        $this->assertFalse($this->checker->isAdmin(new User(['role' => 'administrator'])));
        $this->assertFalse($this->checker->isAdmin(new User(['role' => null])));
        $this->assertFalse($this->checker->isAdmin(new User([])));
    }

    public function test_it_uses_the_configured_field(): void
    {
        config()->set('laravel-watchdog.admin_check.field', 'type');
        config()->set('laravel-watchdog.admin_check.values', ['admin']);

        $this->assertTrue($this->checker->isAdmin(new User(['type' => UserType::Admin])));
        $this->assertTrue($this->checker->isAdmin(new User(['type' => 'admin'])));
        $this->assertFalse($this->checker->isAdmin(new User(['type' => UserType::Member])));
        $this->assertFalse($this->checker->isAdmin(new User(['type' => 'member'])));

        // The "role" column is now ignored.
        $this->assertFalse($this->checker->isAdmin(new User(['role' => 'admin'])));
    }

    public function test_it_normalizes_backed_enums(): void
    {
        config()->set('laravel-watchdog.admin_check.field', 'type');
        config()->set('laravel-watchdog.admin_check.values', ['admin']);

        $user = new User();
        $user->type = UserType::Admin;

        $this->assertSame(UserType::Admin, $user->type);
        $this->assertTrue($this->checker->isAdmin($user));
    }

    public function test_it_compares_raw_string_values_without_casts(): void
    {
        config()->set('laravel-watchdog.admin_check.field', 'type');
        config()->set('laravel-watchdog.admin_check.values', ['admin']);

        $this->assertTrue($this->checker->isAdmin(new PlainUser(['type' => 'admin'])));
        $this->assertFalse($this->checker->isAdmin(new PlainUser(['type' => 'member'])));
    }

    public function test_it_uses_the_configured_values(): void
    {
        config()->set('laravel-watchdog.admin_check.values', ['owner']);

        $this->assertTrue($this->checker->isAdmin(new User(['role' => 'owner'])));
        $this->assertFalse($this->checker->isAdmin(new User(['role' => 'admin'])));
    }
}
