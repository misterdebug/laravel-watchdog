<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class SecurityLog extends Model
{
    use Prunable;

    protected $table = 'security_logs';

    protected $guarded = [];

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
