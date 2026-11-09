<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;

/**
 * Constructor dependency of CustomAdminChecker, used to prove that the custom
 * checker is resolved through the container (with autowiring).
 */
final class AdminPolicy
{
    public function __construct(private readonly Repository $config) {}

    public function allows(Authenticatable $user): bool
    {
        return $user->email === $this->config->get('test.custom_admin_email');
    }
}
