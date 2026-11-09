<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Fixtures;

use MrDebug\LaravelWatchdog\Contracts\AdminChecker;

/**
 * Non-instantiable implementation, used to test ConfigValidator.
 */
abstract class AbstractAdminChecker implements AdminChecker {}
