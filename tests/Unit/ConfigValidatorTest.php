<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Unit;

use InvalidArgumentException;
use MrDebug\LaravelWatchdog\Support\ConfigValidator;
use MrDebug\LaravelWatchdog\Support\RoleAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\AbstractAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\CustomAdminChecker;
use MrDebug\LaravelWatchdog\Tests\Fixtures\NotificationWithoutDiscord;
use MrDebug\LaravelWatchdog\Tests\Fixtures\User;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class ConfigValidatorTest extends TestCase
{
    public function test_the_package_default_configuration_is_valid(): void
    {
        ConfigValidator::validate();

        $this->assertSame('role', config('laravel-watchdog.admin_check.field'));
        $this->assertSame(['admin', 'super-admin'], config('laravel-watchdog.admin_check.values'));
    }

    public function test_a_full_published_configuration_is_valid(): void
    {
        config()->set('laravel-watchdog', require __DIR__.'/../Fixtures/config/base.php');

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    public function test_the_configuration_must_be_an_array(): void
    {
        config()->set('laravel-watchdog', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('laravel-watchdog: configuration must be an array.');

        ConfigValidator::validate();
    }

    public function test_features_must_contain_every_flag(): void
    {
        config()->set('laravel-watchdog.features', ['stats' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[features.activity] must be a boolean.');

        ConfigValidator::validate();
    }

    public function test_features_flags_must_be_boolean(): void
    {
        config()->set('laravel-watchdog.features.stats', 'yes');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[features.stats] must be a boolean.');

        ConfigValidator::validate();
    }

    public function test_discord_only_production_must_be_boolean(): void
    {
        config()->set('laravel-watchdog.discord.only_production', 'true');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[discord.only_production] must be a boolean.');

        ConfigValidator::validate();
    }

    public function test_discord_webhooks_must_use_https(): void
    {
        config()->set('laravel-watchdog.discord.webhooks.logins', 'http://insecure.test/webhook');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[discord.webhooks.logins] must be null, an empty string, or a valid https URL.'
        );

        ConfigValidator::validate();
    }

    public function test_discord_webhooks_may_be_null_or_empty(): void
    {
        config()->set('laravel-watchdog.discord.webhooks', [
            'logins' => null,
            'errors500' => '',
            'security-logs' => 'https://discord.test/security',
        ]);

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    public function test_notifications_must_be_notification_classes(): void
    {
        config()->set('laravel-watchdog.notifications.login', User::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[notifications.login] must be a class extending');

        ConfigValidator::validate();
    }

    public function test_notifications_must_define_to_discord(): void
    {
        config()->set('laravel-watchdog.notifications.login', NotificationWithoutDiscord::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[notifications.login] must be a class extending');

        ConfigValidator::validate();
    }

    public function test_the_admin_checker_must_implement_the_contract(): void
    {
        config()->set('laravel-watchdog.admin_check.checker', User::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.checker] must be a class implementing');

        ConfigValidator::validate();
    }

    public function test_the_admin_checker_must_be_instantiable(): void
    {
        config()->set('laravel-watchdog.admin_check.checker', AbstractAdminChecker::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.checker] must be an instantiable class.');

        ConfigValidator::validate();
    }

    public function test_the_role_checker_requires_a_non_empty_field(): void
    {
        config()->set('laravel-watchdog.admin_check', [
            'checker' => RoleAdminChecker::class,
            'field' => '',
            'values' => ['admin'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.field] must be a non-empty string.');

        ConfigValidator::validate();
    }

    public function test_the_role_checker_requires_a_non_empty_values_array(): void
    {
        config()->set('laravel-watchdog.admin_check', [
            'checker' => RoleAdminChecker::class,
            'field' => 'role',
            'values' => [],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.values] must be a non-empty array.');

        ConfigValidator::validate();
    }

    public function test_the_role_checker_values_must_be_scalars(): void
    {
        config()->set('laravel-watchdog.admin_check', [
            'checker' => RoleAdminChecker::class,
            'field' => 'role',
            'values' => [['admin']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.values] must only contain scalar values.');

        ConfigValidator::validate();
    }

    public function test_a_published_admin_check_section_without_values_is_invalid(): void
    {
        // mergeConfigFrom() is a shallow array_merge(): a published
        // admin_check section replaces the whole default section.
        config()->set('laravel-watchdog.admin_check', [
            'checker' => RoleAdminChecker::class,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[admin_check.field] must be a non-empty string.');

        ConfigValidator::validate();
    }

    public function test_a_custom_checker_does_not_require_field_or_values(): void
    {
        config()->set('laravel-watchdog.admin_check', [
            'checker' => CustomAdminChecker::class,
        ]);

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    public function test_a_custom_checker_ignores_field_and_values(): void
    {
        config()->set('laravel-watchdog.admin_check', [
            'checker' => CustomAdminChecker::class,
            'field' => 123,
            'values' => 'not-an-array',
        ]);

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    public function test_suspicious_activity_thresholds_must_be_positive_integers(): void
    {
        config()->set('laravel-watchdog.suspicious_activity.window_minutes', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[suspicious_activity.window_minutes] must be a positive integer.');

        ConfigValidator::validate();
    }

    public function test_rate_limit_values_must_be_positive_integers(): void
    {
        config()->set('laravel-watchdog.rate_limit.error500.max_attempts', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[rate_limit.error500.max_attempts] must be a positive integer.');

        ConfigValidator::validate();
    }

    public function test_prune_values_must_be_positive_integers(): void
    {
        config()->set('laravel-watchdog.prune.stats_logs', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[prune.stats_logs] must be a positive integer.');

        ConfigValidator::validate();
    }

    public function test_traffic_excluded_params_must_only_contain_strings(): void
    {
        config()->set('laravel-watchdog.traffic_excluded_params', ['password', 123]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[traffic_excluded_params] must only contain strings.');

        ConfigValidator::validate();
    }

    public function test_security_excluded_params_must_be_an_array(): void
    {
        config()->set('laravel-watchdog.security_excluded_params', 'password');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[security_excluded_params] must be an array.');

        ConfigValidator::validate();
    }
}
