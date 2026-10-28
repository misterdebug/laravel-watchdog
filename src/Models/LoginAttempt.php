<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use MassPrunable;

    protected $table = 'watchdog_login_attempts';

    protected $fillable = [
        'email',
        'ip',
        'user_agent',
        'success',
    ];

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
