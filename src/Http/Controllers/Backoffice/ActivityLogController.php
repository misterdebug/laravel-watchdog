<?php

namespace MrDebug\LaravelWatchdog\Http\Controllers\Backoffice;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use MrDebug\LaravelWatchdog\Models\ActivityLog;

class ActivityLogController
{
    public function index(): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('laravel-watchdog::backoffice.activity-logs.index', compact('logs'));
    }

    public function destroy(int $id): RedirectResponse
    {
        ActivityLog::findOrFail($id)->delete();

        return back()->with('status', __('laravel-watchdog::backoffice.messages.row_deleted'));
    }

    protected function perPage(): int
    {
        return max(1, (int) config('laravel-watchdog.backoffice.per_page', 50));
    }
}
