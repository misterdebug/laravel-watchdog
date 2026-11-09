<?php

namespace MrDebug\LaravelWatchdog\Http\Controllers\Backoffice;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use MrDebug\LaravelWatchdog\Models\SecurityLog;

class SecurityLogController
{
    public function index(): View
    {
        $logs = SecurityLog::query()
            ->with('user')
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('laravel-watchdog::backoffice.security-logs.index', compact('logs'));
    }

    public function destroy(int $id): RedirectResponse
    {
        SecurityLog::findOrFail($id)->delete();

        return back()->with('status', __('laravel-watchdog::backoffice.messages.row_deleted'));
    }

    protected function perPage(): int
    {
        return max(1, (int) config('laravel-watchdog.backoffice.per_page', 50));
    }
}
