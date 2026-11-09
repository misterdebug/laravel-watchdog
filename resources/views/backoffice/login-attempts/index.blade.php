<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('laravel-watchdog::backoffice.partials.nav')
        @include('laravel-watchdog::backoffice.partials.flashes')

        <div class="flex flex-col gap-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('laravel-watchdog::backoffice.login_attempts.title') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('laravel-watchdog::backoffice.login_attempts.description') }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/50">
                    <thead class="bg-gray-50 dark:bg-gray-800/20">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.email') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.result') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.ip') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.user_agent') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $log->email ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm">
                                    @if ($log->success)
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-950/40 dark:text-green-200">{{ __('laravel-watchdog::backoffice.login_attempts.success') }}</span>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-200">{{ __('laravel-watchdog::backoffice.login_attempts.failure') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->ip }}</td>
                                <td class="max-w-md break-all px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->user_agent }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ \MrDebug\LaravelWatchdog\Support\DateFormatter::format($log->created_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                    <form action="{{ route('watchdog.login-attempts.destroy', $log->id) }}" method="POST" onsubmit="return confirm('{{ __('laravel-watchdog::backoffice.actions.confirm_delete_row') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('laravel-watchdog::backoffice.actions.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.messages.no_records') }}</td>
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
