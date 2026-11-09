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

    public function getDescription(): string
    {
        return __('laravel-watchdog::commands.prune.description');
    }

    public function handle(): void
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

            $this->info(trans_choice('laravel-watchdog::commands.prune.model_deleted', $deleted, [
                'model' => class_basename($model),
                'count' => $deleted,
            ]));
        }

        $this->info(trans_choice('laravel-watchdog::commands.prune.total_deleted', $total, ['count' => $total]));
    }
}
