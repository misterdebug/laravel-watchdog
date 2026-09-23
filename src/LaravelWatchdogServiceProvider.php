<?php

namespace MrDebug\LaravelWatchdog;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use MrDebug\LaravelWatchdog\Console\AnalyzeSuspiciousLogins;
use MrDebug\LaravelWatchdog\Console\SendDailySecurityReport;
use MrDebug\LaravelWatchdog\Listeners\LogLoginAttempt;
use MrDebug\LaravelWatchdog\Listeners\LogSentEmail;
use MrDebug\LaravelWatchdog\Middleware\LogActivity;
use MrDebug\LaravelWatchdog\Middleware\LogStats;
use MrDebug\LaravelWatchdog\Notifications\Channels\DiscordChannel;

class LaravelWatchdogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-watchdog.php',
            'laravel-watchdog'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-watchdog.php' => config_path('laravel-watchdog.php'),
            ], 'laravel-watchdog-config');

            $this->commands([
                AnalyzeSuspiciousLogins::class,
                SendDailySecurityReport::class,
            ]);
        }

        $this->registerDiscordChannel();
        $this->registerEventListeners();
        $this->registerMiddlewareAliases();
    }

    protected function registerDiscordChannel(): void
    {
        $this->app->make(ChannelManager::class)
            ->extend('discord', fn () => new DiscordChannel());
    }

    protected function registerEventListeners(): void
    {
        if (config('laravel-watchdog.features.login_attempts')) {
            Event::listen(Login::class, LogLoginAttempt::class);
            Event::listen(Failed::class, LogLoginAttempt::class);
        }

        if (config('laravel-watchdog.features.sent_emails')) {
            Event::listen(MessageSent::class, LogSentEmail::class);
        }
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('log-stats', LogStats::class);
        $router->aliasMiddleware('log-activity', LogActivity::class);
    }
}
