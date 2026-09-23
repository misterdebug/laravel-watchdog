<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class SentEmail extends Model
{
    use Prunable;

    protected $table = 'sent_emails';

    protected $guarded = [];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(
            config('laravel-watchdog.prune.sent_emails', 30)
        ));
    }
}
