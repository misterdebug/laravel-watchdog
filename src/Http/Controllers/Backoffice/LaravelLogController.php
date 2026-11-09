<?php

namespace MrDebug\LaravelWatchdog\Http\Controllers\Backoffice;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use MrDebug\LaravelWatchdog\Support\LaravelLogParser;

class LaravelLogController
{
    public function index(Request $request, LaravelLogParser $parser): View
    {
        $directory = $this->directory();
        $files = $parser->files($directory);
        $selected = $this->selectedFile($request->query('file'), $files);
        $entries = $selected === null ? [] : array_reverse($parser->entries($directory, $selected));
        $perPage = $this->perPage();
        $page = Paginator::resolveCurrentPage();

        $logs = new LengthAwarePaginator(
            array_slice($entries, ($page - 1) * $perPage, $perPage),
            count($entries),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('laravel-watchdog::backoffice.laravel-logs.index', compact('files', 'selected', 'logs'));
    }

    public function destroy(Request $request, LaravelLogParser $parser): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'entry' => ['required', 'string'],
        ]);

        $deleted = $parser->deleteEntry($this->directory(), $data['file'], $data['entry']);

        if (! $deleted) {
            return back()->with('error', 'Unable to delete this entry. Check that the log file exists and is writable.');
        }

        return back()->with('status', 'Entry deleted.');
    }

    /**
     * @param array<int, array{name: string, size: int, modified_at: int|null}> $files
     */
    protected function selectedFile(mixed $requested, array $files): ?string
    {
        $names = array_column($files, 'name');

        if (is_string($requested) && in_array($requested, $names, true)) {
            return $requested;
        }

        return $names[0] ?? null;
    }

    protected function directory(): string
    {
        return (string) config('laravel-watchdog.backoffice.laravel_logs.directory', storage_path('logs'));
    }

    protected function perPage(): int
    {
        return max(1, (int) config('laravel-watchdog.backoffice.laravel_logs.per_page', 20));
    }
}
