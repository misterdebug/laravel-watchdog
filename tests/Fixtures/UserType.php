<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

enum UserType: string
{
    case Admin = 'admin';
    case Member = 'member';
}
