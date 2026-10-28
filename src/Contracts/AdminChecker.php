<?php

namespace MrDebug\LaravelWatchdog\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AdminChecker
{
    public function isAdmin(?Authenticatable $user): bool;
}
