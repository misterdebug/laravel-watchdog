<?php

namespace MrDebug\LaravelWatchdog\Middleware;

use Closure;
use Illuminate\Http\Request;
use MrDebug\LaravelWatchdog\Models\StatsLog;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogStats
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        if (! config('laravel-watchdog.features.stats')) {
            return $response;
        }

        if ($this->isAdmin($request)) {
            return $response;
        }

        try {
            $excluded = config('laravel-watchdog.traffic_excluded_params', []);

            $params = array_merge(
                $request->query(),
                $request->except($excluded)
            );

            StatsLog::create([
                'route_name'    => $request->route()?->getName(),
                'method'        => $request->method(),
                'url'           => $request->fullUrl(),
                'params'        => $params ?: null,
                'ip'            => $request->ip(),
                'session_id'    => session()->getId(),
                'user_agent'    => $request->userAgent(),
                'referer'       => $request->headers->get('referer'),
                'response_code' => $response->getStatusCode(),
                'duration_ms'   => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (Throwable $t) {
            logger()->info($t->getMessage());
        }

        return $response;
    }

    protected function isAdmin(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $field = config('laravel-watchdog.admin_check.field', 'role');
        $value = config('laravel-watchdog.admin_check.value', 'admin');

        return $user->{$field} === $value;
    }
}
