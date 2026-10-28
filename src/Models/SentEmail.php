<?php

namespace MrDebug\LaravelWatchdog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    use MassPrunable;

    protected $table = 'watchdog_sent_emails';

    protected $fillable = [
        'from',
        'recipients',
        'subject',
        'html',
        'text',
        'attachments',
        'headers',
    ];

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
