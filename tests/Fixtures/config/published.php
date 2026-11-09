<?php

declare(strict_types=1);

/*
 * Scenario: the config file has been published.
 *
 * The contents mirror the package defaults for admin_check. A distinct value
 * (prune.stats_logs) proves that the published file is really the one that is
 * loaded, while the admin_check section keeps the default behaviour.
 */
$config = require __DIR__.'/base.php';

$config['prune']['stats_logs'] = 15;

return $config;
