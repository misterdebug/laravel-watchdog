<?php

declare(strict_types=1);

use MrDebug\LaravelWatchdog\Tests\Fixtures\CustomAdminChecker;

/*
 * Scenario: the application provides its own AdminChecker implementation.
 *
 * The field/values entries are deliberately nonsensical: they must be ignored
 * because the configured checker is not the default RoleAdminChecker.
 */
$config = require __DIR__.'/base.php';

$config['admin_check']['checker'] = CustomAdminChecker::class;
$config['admin_check']['field'] = 'does_not_exist';
$config['admin_check']['values'] = ['nobody'];

return $config;
