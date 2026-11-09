<?php

declare(strict_types=1);

/*
 * Scenario: the published config file has been modified to match an
 * application whose admin flag lives in a "type" column (a BackedEnum cast).
 */
$config = require __DIR__.'/base.php';

$config['admin_check']['field'] = 'type';
$config['admin_check']['values'] = ['admin'];

return $config;
