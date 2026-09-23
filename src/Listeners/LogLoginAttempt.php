<?php

namespace MrDebug\LaravelWatchdog\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use Throwable;

class LogLoginAttempt
{
    public function __construct(private Request $request) {}

    public function handle($event): void
    {
        if (! config('laravel-watchdog.features.login_attempts')) {
            return;
        }

        $ip = $this->request->ip();

        if ($event instanceof Login) {
            if ($this->isAdmin($event->user)) {
                $this->notifyAdminLogin($ip, $event->user->email);

                return;
            }

            $this->record($event->user->email, $ip, true);

            return;
        }

        if ($event instanceof Failed) {
            $email = $event->credentials['email'] ?? null;

            $this->record($email, $ip, false);
        }
    }

    protected function record(?string $email, ?string $ip, bool $success): void
    {
        try {
            LoginAttempt::create([
                'email'      => $email,
                'ip'         => $ip,
                'user_agent' => $this->request->userAgent(),
                'success'    => $success,
            ]);
        } catch (Throwable $t) {
            // On ne remonte jamais une erreur interne du package sur le canal
            // Discord des vraies erreurs applicatives (500), on log seulement.
            logger()->error('[laravel-watchdog] failed to record login attempt: '.$t->getMessage());
        }
    }

    protected function notifyAdminLogin(?string $ip, string $email): void
    {
        try {
            $notificationClass = config('laravel-watchdog.notifications.login');

            Notification::route('discord', config('laravel-watchdog.discord.webhooks.logins'))
                ->notify(new $notificationClass($ip, $email));
        } catch (Throwable $t) {
            logger()->error('[laravel-watchdog] failed to send login notification: '.$t->getMessage());
        }
    }

    protected function isAdmin($user): bool
    {
        $field = config('laravel-watchdog.admin_check.field', 'role');
        $value = config('laravel-watchdog.admin_check.value', 'admin');

        return $user->{$field} === $value;
    }
}
