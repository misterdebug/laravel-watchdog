@php
    $tabs = [
        'watchdog.stats-logs.index' => 'Stats',
        'watchdog.activity-logs.index' => 'Activity',
        'watchdog.security-logs.index' => 'Security',
        'watchdog.login-attempts.index' => 'Login attempts',
        'watchdog.sent-emails.index' => 'Sent emails',
        'watchdog.laravel-logs.index' => 'Laravel logs',
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
