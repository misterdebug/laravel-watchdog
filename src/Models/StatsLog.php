<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class StatsLog extends Model
{
    use MassPrunable;

    protected $table = 'watchdog_stats_logs';

    protected $fillable = [
        'route_name',
        'method',
        'url',
        'params',
        'ip',
        'session_id',
        'user_agent',
        'referer',
        'response_code',
        'duration_ms',
    ];

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
