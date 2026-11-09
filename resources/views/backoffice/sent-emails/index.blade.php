<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('laravel-watchdog::backoffice.partials.nav')
        @include('laravel-watchdog::backoffice.partials.flashes')

        <div class="flex flex-col gap-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('laravel-watchdog::backoffice.sent_emails.title') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('laravel-watchdog::backoffice.sent_emails.description') }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/50">
                    <thead class="bg-gray-50 dark:bg-gray-800/20">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.from') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.recipients') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.subject') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.content') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.attachments') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
                        @forelse ($logs as $email)
                            @php $recipients = $email->recipients ?? []; @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $email->from ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ implode(', ', $recipients['to'] ?? []) ?: '—' }}</div>
                                    @if (! empty($recipients['cc']))
                                        <div class="text-xs">{{ __('laravel-watchdog::backoffice.sent_emails.cc') }} : {{ implode(', ', $recipients['cc']) }}</div>
                                    @endif
                                    @if (! empty($recipients['bcc']))
                                        <div class="text-xs">{{ __('laravel-watchdog::backoffice.sent_emails.bcc') }} : {{ implode(', ', $recipients['bcc']) }}</div>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $email->subject ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ __('laravel-watchdog::backoffice.actions.view_content') }}</summary>

                                        @if ($email->text)
                                            <div class="mt-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.sent_emails.text') }}</div>
                                                <pre class="mt-1 max-h-64 max-w-xl overflow-auto whitespace-pre-wrap break-words rounded bg-gray-50 p-3 text-xs dark:bg-gray-900">{{ $email->text }}</pre>
                                            </div>
                                        @endif

                                        @if ($email->html)
                                            <div class="email-preview mt-3">
                                                <textarea id="email-html-{{ $email->id }}" class="hidden">{{ $email->html }}</textarea>
                                                <button
                                                    type="button"
                                                    class="js-email-preview rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                                                    data-source="email-html-{{ $email->id }}"
                                                    data-target="email-preview-{{ $email->id }}"
                                                    data-images="0"
                                                    data-label-show-images="{{ __('laravel-watchdog::backoffice.actions.show_images') }}"
                                                    data-label-hide-images="{{ __('laravel-watchdog::backoffice.actions.hide_images') }}"
                                                    data-iframe-title="{{ __('laravel-watchdog::backoffice.sent_emails.iframe_title') }}"
                                                >
                                                    {{ __('laravel-watchdog::backoffice.actions.html_preview') }}
                                                </button>
                                                <div id="email-preview-{{ $email->id }}" class="mt-2"></div>
                                            </div>
                                        @endif

                                        @if ($email->headers)
                                            <div class="mt-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.sent_emails.headers') }}</div>
                                                <pre class="mt-1 max-h-64 max-w-xl overflow-auto whitespace-pre-wrap break-words rounded bg-gray-50 p-3 text-xs dark:bg-gray-900">{{ json_encode($email->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif

                                        @if (! $email->text && ! $email->html)
                                            <p class="mt-2 text-xs">{{ __('laravel-watchdog::backoffice.sent_emails.no_content') }}</p>
                                        @endif
                                    </details>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @forelse ($email->attachments ?? [] as $attachment)
                                        <div class="text-xs">
                                            {{ $attachment['name'] ?? __('laravel-watchdog::backoffice.sent_emails.unnamed_attachment') }}
                                            @if (! empty($attachment['mime']))
                                                ({{ $attachment['mime'] }})
                                            @endif
                                            @if (isset($attachment['size']))
                                                — {{ number_format(((int) $attachment['size']) / 1024, 1, __('laravel-watchdog::backoffice.formats.decimal_separator'), __('laravel-watchdog::backoffice.formats.thousands_separator')) }} {{ __('laravel-watchdog::backoffice.laravel_logs.unit_kb') }}
                                            @endif
                                        </div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ \MrDebug\LaravelWatchdog\Support\DateFormatter::format($email->created_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                    <form action="{{ route('watchdog.sent-emails.destroy', $email->id) }}" method="POST" onsubmit="return confirm('{{ __('laravel-watchdog::backoffice.actions.confirm_delete_row') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('laravel-watchdog::backoffice.actions.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('laravel-watchdog::backoffice.messages.no_records') }}</td>
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

    <script>
        (function () {
            function buildDocument(html, allowImages) {
                var csp = "default-src 'none'; style-src 'unsafe-inline'; font-src data:;";
                csp += allowImages ? " img-src * data:;" : " img-src 'none';";

                return '<!DOCTYPE html><html><head><meta charset="utf-8">'
                    + '<meta http-equiv="Content-Security-Policy" content="' + csp + '">'
                    + '<base target="_blank"></head><body>' + html + '</body></html>';
            }

            function render(button) {
                var source = document.getElementById(button.getAttribute('data-source'));
                var target = document.getElementById(button.getAttribute('data-target'));

                if (!source || !target) {
                    return;
                }

                var allowImages = button.getAttribute('data-images') === '1';
                var iframe = target.querySelector('iframe');

                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.setAttribute('sandbox', '');
                    iframe.setAttribute('referrerpolicy', 'no-referrer');
                    iframe.setAttribute('title', button.getAttribute('data-iframe-title') || 'Email HTML preview');
                    iframe.className = 'h-96 w-full rounded border border-gray-200 bg-white';
                    target.appendChild(iframe);
                }

                iframe.srcdoc = buildDocument(source.value, allowImages);
                button.setAttribute('data-images', allowImages ? '0' : '1');
                button.textContent = allowImages
                    ? button.getAttribute('data-label-show-images')
                    : button.getAttribute('data-label-hide-images');
            }

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.js-email-preview');

                if (!button) {
                    return;
                }

                event.preventDefault();
                render(button);
            });
        })();
    </script>
</x-app-layout>
