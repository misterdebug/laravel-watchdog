<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use MrDebug\LaravelWatchdog\Contracts\AdminChecker;

final class CustomAdminChecker implements AdminChecker
{
    public function __construct(private readonly AdminPolicy $policy) {}

    public function isAdmin(?Authenticatable $user): bool
    {
        return $user !== null && $this->policy->allows($user);
    }
}
