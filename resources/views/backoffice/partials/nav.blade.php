@php
    $tabs = [
        'watchdog.stats-logs.index' => __('laravel-watchdog::backoffice.nav.stats'),
        'watchdog.activity-logs.index' => __('laravel-watchdog::backoffice.nav.activity'),
        'watchdog.security-logs.index' => __('laravel-watchdog::backoffice.nav.security'),
        'watchdog.login-attempts.index' => __('laravel-watchdog::backoffice.nav.login_attempts'),
        'watchdog.sent-emails.index' => __('laravel-watchdog::backoffice.nav.sent_emails'),
        'watchdog.laravel-logs.index' => __('laravel-watchdog::backoffice.nav.laravel_logs'),
    ];

    $availableTabs = array_filter(
        $tabs,
        fn (string $route) => \Illuminate\Support\Facades\Route::has($route),
        ARRAY_FILTER_USE_KEY
    );
@endphp

@if ($availableTabs !== [])
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800/50">
        <nav class="-mb-px flex flex-wrap items-center gap-x-6">
            @foreach ($availableTabs as $route => $label)
                <a href="{{ route($route) }}"
                    class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium {{ request()->routeIs($route) ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
@endif
