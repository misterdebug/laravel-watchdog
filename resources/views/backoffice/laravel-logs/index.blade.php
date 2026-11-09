<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('laravel-watchdog::backoffice.partials.nav')
        @include('laravel-watchdog::backoffice.partials.flashes')

        <div class="flex flex-col gap-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('laravel-watchdog::backoffice.laravel_logs.title') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {!! __('laravel-watchdog::backoffice.laravel_logs.description', ['files' => __('laravel-watchdog::backoffice.laravel_logs.files')]) !!}
                </p>
            </div>

            @if ($files !== [])
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($files as $file)
                        <a href="{{ route('watchdog.laravel-logs.index', ['file' => $file['name']]) }}"
                            class="rounded-md border px-3 py-2 text-xs font-medium {{ $selected === $file['name'] ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300' }}">
                            {{ $file['name'] }}
                            <span class="text-gray-400">
                                — {{ number_format($file['size'] / 1024, 1, __('laravel-watchdog::backoffice.formats.decimal_separator'), __('laravel-watchdog::backoffice.formats.thousands_separator')) }} {{ __('laravel-watchdog::backoffice.laravel_logs.unit_kb') }}
                                @if ($file['modified_at'])
                                    , {{ \MrDebug\LaravelWatchdog\Support\DateFormatter::format(\Illuminate\Support\Carbon::createFromTimestamp($file['modified_at'])) }}
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-200">
                    {!! __('laravel-watchdog::backoffice.laravel_logs.no_files', ['files' => __('laravel-watchdog::backoffice.laravel_logs.files')]) !!}
                </div>
            @endif

            @if ($selected)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/50">
                        <thead class="bg-gray-50 dark:bg-gray-800/20">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.level') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.timestamp') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.message') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                            @forelse ($logs as $entry)
                                @php
                                    $level = strtolower($entry['level']);
                                    $badge = match ($level) {
                                        'error', 'critical', 'alert', 'emergency' => 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-200',
                                        'info', 'notice' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ $entry['level'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div>{{ \MrDebug\LaravelWatchdog\Support\DateFormatter::formatTimestamp($entry['timestamp']) }}</div>
                                        @if ($entry['environment'])
                                            <div class="text-xs text-gray-400">{{ $entry['environment'] }}</div>
                                        @endif
                                    </td>
                                    <td class="max-w-2xl px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="break-words">{{ \Illuminate\Support\Str::limit($entry['message'], 300) }}</div>
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ __('laravel-watchdog::backoffice.actions.view_full_entry') }}</summary>
                                            <pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap break-words rounded bg-gray-950 p-3 text-xs text-gray-100">{{ $entry['raw'] }}</pre>
                                        </details>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                        <form action="{{ route('watchdog.laravel-logs.destroy') }}" method="POST" onsubmit="return confirm('{{ __('laravel-watchdog::backoffice.actions.confirm_delete_entry') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="file" value="{{ $selected }}">
                                            <input type="hidden" name="entry" value="{{ $entry['id'] }}">
                                            <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('laravel-watchdog::backoffice.actions.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.laravel_logs.no_entries') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div>{{ $logs->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
