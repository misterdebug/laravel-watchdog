<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use MassPrunable;

    protected $table = 'watchdog_activity_logs';

    protected $fillable = [
        'user_id',
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
            config('laravel-watchdog.prune.activity_logs', 90)
        ));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }
}
