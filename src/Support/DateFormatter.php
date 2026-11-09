<?php

namespace MrDebug\LaravelWatchdog\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

final class DateFormatter
{
    public static function format(?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        return $date->format(self::formatString());
    }

    public static function formatTimestamp(?string $timestamp): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '—';
        }

        try {
            $date = Carbon::parse($timestamp);
        } catch (Throwable) {
            return $timestamp;
        }

        return self::format($date);
    }

    public static function formatString(): string
    {
        return (string) __('laravel-watchdog::backoffice.formats.datetime');
    }
}
