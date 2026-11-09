<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Authenticatable model without casts, used to exercise the raw-string branch
 * of RoleAdminChecker (the regular User model casts "type" to an enum).
 */
class PlainUser extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'type',
    ];
}
