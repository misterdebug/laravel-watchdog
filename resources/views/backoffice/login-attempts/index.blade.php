<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('laravel-watchdog::backoffice.partials.nav')
        @include('laravel-watchdog::backoffice.partials.flashes')

        <div class="flex flex-col gap-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Login attempts</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Successful and failed logins recorded by the package.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/50">
                    <thead class="bg-gray-50 dark:bg-gray-800/20">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Result</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">IP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">User agent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $log->email ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm">
                                    @if ($log->success)
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-950/40 dark:text-green-200">Success</span>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-200">Failure</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->ip }}</td>
                                <td class="max-w-md break-all px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->user_agent }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                    <form action="{{ route('watchdog.login-attempts.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Delete this row?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No records.</td>
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
