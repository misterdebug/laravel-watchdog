<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class StatsLog extends Model
{
    use Prunable;

    protected $table = 'stats_logs';

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
    ];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(
            config('laravel-watchdog.prune.stats_logs', 30)
        ));
    }
}
