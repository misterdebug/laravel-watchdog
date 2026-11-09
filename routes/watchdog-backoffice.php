<?php

use Illuminate\Support\Facades\Route;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\ActivityLogController;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\LaravelLogController;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\LoginAttemptController;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\SecurityLogController;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\SentEmailController;
use MrDebug\LaravelWatchdog\Http\Controllers\Backoffice\StatsLogController;

/*
|--------------------------------------------------------------------------
| Laravel Watchdog backoffice routes
|--------------------------------------------------------------------------
|
| This file is meant to be included from routes/web.php:
|
|     require base_path('routes/watchdog-backoffice.php');
|
| It inherits the "web" middleware group from web.php (sessions, CSRF).
| The package does NOT provide any authorization middleware: secure these
| routes with your own middlewares. Add your admin middleware to the group
| below, for example ['auth', 'admin'] instead of ['auth'].
|
| Every page is optional: comment out or delete the routes you do not want.
|
*/

Route::middleware(['auth'])
    ->prefix('watchdog')
    ->name('watchdog.')
    ->group(function () {
        Route::get('stats-logs', [StatsLogController::class, 'index'])->name('stats-logs.index');
        Route::delete('stats-logs/{id}', [StatsLogController::class, 'destroy'])->whereNumber('id')->name('stats-logs.destroy');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::delete('activity-logs/{id}', [ActivityLogController::class, 'destroy'])->whereNumber('id')->name('activity-logs.destroy');

        Route::get('security-logs', [SecurityLogController::class, 'index'])->name('security-logs.index');
        Route::delete('security-logs/{id}', [SecurityLogController::class, 'destroy'])->whereNumber('id')->name('security-logs.destroy');

        Route::get('login-attempts', [LoginAttemptController::class, 'index'])->name('login-attempts.index');
        Route::delete('login-attempts/{id}', [LoginAttemptController::class, 'destroy'])->whereNumber('id')->name('login-attempts.destroy');

        Route::get('sent-emails', [SentEmailController::class, 'index'])->name('sent-emails.index');
        Route::delete('sent-emails/{id}', [SentEmailController::class, 'destroy'])->whereNumber('id')->name('sent-emails.destroy');

        Route::get('laravel-logs', [LaravelLogController::class, 'index'])->name('laravel-logs.index');
        Route::delete('laravel-logs', [LaravelLogController::class, 'destroy'])->name('laravel-logs.destroy');
    });
