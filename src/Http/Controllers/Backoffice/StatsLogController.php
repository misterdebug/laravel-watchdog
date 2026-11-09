<?php

namespace MrDebug\LaravelWatchdog\Http\Controllers\Backoffice;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use MrDebug\LaravelWatchdog\Models\StatsLog;

class StatsLogController
{
    public function index(): View
    {
        $logs = StatsLog::query()
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('laravel-watchdog::backoffice.stats-logs.index', compact('logs'));
    }

    public function destroy(int $id): RedirectResponse
    {
        StatsLog::findOrFail($id)->delete();

        return back()->with('status', 'Row deleted.');
    }

    protected function perPage(): int
    {
        return max(1, (int) config('laravel-watchdog.backoffice.per_page', 50));
    }
}
