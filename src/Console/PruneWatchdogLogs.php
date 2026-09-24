<?php

namespace MrDebug\LaravelWatchdog\Console;

use Illuminate\Console\Command;
use MrDebug\LaravelWatchdog\Models\ActivityLog;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Models\SecurityLog;
use MrDebug\LaravelWatchdog\Models\SentEmail;
use MrDebug\LaravelWatchdog\Models\StatsLog;

class PruneWatchdogLogs extends Command
{
    protected $signature = 'watchdog:prune';

    protected $description = 'Prunes old Laravel Watchdog records';

    public function handle(): int
    {
        $models = [
            StatsLog::class,
            ActivityLog::class,
            SecurityLog::class,
            LoginAttempt::class,
            SentEmail::class,
        ];

        $total = 0;

        foreach ($models as $model) {
            $deleted = (new $model)->pruneAll();
            $total += $deleted;

            $this->info(sprintf('%s: %d deleted.', class_basename($model), $deleted));
        }

        $this->info(sprintf('%d Watchdog records deleted.', $total));

        return self::SUCCESS;
    }
}
