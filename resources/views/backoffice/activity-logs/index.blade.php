<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('laravel-watchdog::backoffice.partials.nav')
        @include('laravel-watchdog::backoffice.partials.flashes')

        <div class="flex flex-col gap-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('laravel-watchdog::backoffice.activity.title') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {!! __('laravel-watchdog::backoffice.activity.description', ['middleware' => 'log-activity']) !!}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/50">
                    <thead class="bg-gray-50 dark:bg-gray-800/20">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.user') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.route') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.method') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.url') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.code') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.ip') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.params') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $log->user?->email ?? $log->user_id ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->route_name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->method }}</td>
                                <td class="max-w-xs break-all px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->url }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->response_code }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->duration_ms }} ms</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->ip }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @if ($log->params)
                                        <details>
                                            <summary class="cursor-pointer text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ __('laravel-watchdog::backoffice.actions.view') }}</summary>
                                            <pre class="mt-2 max-w-md whitespace-pre-wrap break-words text-xs">{{ json_encode($log->params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ \MrDebug\LaravelWatchdog\Support\DateFormatter::format($log->created_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                    <form action="{{ route('watchdog.activity-logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('{{ __('laravel-watchdog::backoffice.actions.confirm_delete_row') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('laravel-watchdog::backoffice.actions.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.messages.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div>{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
