<?php

namespace MrDebug\LaravelWatchdog\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use MrDebug\LaravelWatchdog\Contracts\AdminChecker;

final class RoleAdminChecker implements AdminChecker
{
    public function isAdmin(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        $field = config('laravel-watchdog.admin_check.field', 'role');
        $values = config('laravel-watchdog.admin_check.values', ['admin']);

        $value = data_get($user, $field);

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return in_array($value, $values, true);
    }
}
