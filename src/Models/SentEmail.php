<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class SentEmail extends Model
{
    use Prunable;

    protected $table = 'watchdog_sent_emails';

    protected $guarded = [];
    protected $casts = [
        'recipients'  => 'array',
        'attachments' => 'array',
        'headers'     => 'array',
    ];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(
            config('laravel-watchdog.prune.sent_emails', 30)
        ));
    }
}
