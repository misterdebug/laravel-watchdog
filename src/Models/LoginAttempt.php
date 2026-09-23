<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class LoginAttempt extends Model
{
    use Prunable;

    protected $table = 'login_attempts';

    protected $guarded = [];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(
            config('laravel-watchdog.prune.login_attempts', 90)
        ));
    }
}
