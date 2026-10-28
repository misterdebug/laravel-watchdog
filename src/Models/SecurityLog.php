<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use MassPrunable;

    protected $table = 'watchdog_security_logs';

    protected $fillable = [
        'user_id',
        'route_name',
        'method',
        'url',
        'params',
        'ip',
        'user_agent',
        'referer',
        'response_code',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(
            config('laravel-watchdog.prune.security_logs', 90)
        ));
    }
}
