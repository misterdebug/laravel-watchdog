<?php

namespace MrDebug\LaravelWatchdog\Support;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use MrDebug\LaravelWatchdog\Models\SecurityLog;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionHandler
{
    /**
     * A appeler dans bootstrap/app.php, a l'interieur de withExceptions().
     */
    public static function register(Exceptions $exceptions): void
    {
        if (config('laravel-watchdog.features.security_500')) {
            $exceptions->report(function (Throwable $e) {
                if ($e instanceof HttpExceptionInterface) {
                    return;
                }

                if (! app()->isProduction()) {
                    return;
                }

                static::notify500($e);
            });
        }

        if (config('laravel-watchdog.features.security_404')) {
            $exceptions->render(function (NotFoundHttpException $e, Request $request) {
                static::logSecurityEvent($request, 404);
            });
        }

        if (config('laravel-watchdog.features.security_422')) {
            $exceptions->render(function (ValidationException $e, Request $request) {
                static::logSecurityEvent($request, 422);
            });
        }
    }

    protected static function notify500(Throwable $e): void
    {
        $limit = config('laravel-watchdog.rate_limit.error500', [
            'max_attempts' => 5,
            'decay_seconds' => 3600,
        ]);

        RateLimiter::attempt(
            'watchdog-error500-'.get_class($e),
            $limit['max_attempts'],
            function () use ($e) {
                try {
                    $notificationClass = config('laravel-watchdog.notifications.error500');

                    Notification::route(
                        'discord',
                        config('laravel-watchdog.discord.webhooks.errors500')
                    )->notify(new $notificationClass(
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                    ));
                } catch (Throwable) {

                }
            },
            $limit['decay_seconds']
        );
    }

    protected static function logSecurityEvent(Request $request, int $responseCode): void
    {
        try {
            $excluded = config('laravel-watchdog.security_excluded_params', []);

            SecurityLog::create([
                'user_id'       => $request->user()?->id,
                'route_name'    => $request->route()?->getName(),
                'method'        => $request->method(),
                'url'           => $request->fullUrl(),
                'params'        => $request->except($excluded) ?: null,
                'ip'            => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'referer'       => $request->headers->get('referer'),
                'response_code' => $responseCode,
            ]);
        } catch (Throwable $t) {
            logger()->info($t->getMessage());
        }
    }
}
