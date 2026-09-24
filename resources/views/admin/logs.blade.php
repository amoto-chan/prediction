@extends('layouts.app')
@section('content')
<div class="mb-8">
    <p class="eyebrow">Administration</p>
    <h1 class="page-title">System logs</h1>
    <p class="mt-2 text-slate-500">Every login, logout, update and import — with exact timestamps and IP addresses.</p>
</div>

<section class="panel overflow-hidden">
    <form method="GET" action="{{ route('admin.logs') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <label class="field min-w-52">Search
            <input name="q" value="{{ request('q') }}" placeholder="action, description or IP…">
        </label>
        <label class="field min-w-48">Action type
            <select name="action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected($actionFilter === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button class="btn-primary !py-2.5">Filter</button>
            <a href="{{ route('admin.logs') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 transition hover:bg-slate-100 dark:border-slate-700">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Description</th><th>IP address</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php($badge = str_contains($log->action, 'Deleted') ? 'badge-failed' : (str_contains($log->action, 'Logged in') ? 'badge-passed' : (str_contains($log->action, 'Created') ? 'badge-admin' : (str_contains($log->action, 'Updated') ? 'badge-instructor' : 'badge-at-risk'))))
                    <tr class="table-row">
                        <td class="whitespace-nowrap"><b>{{ $log->created_at?->format('M d, Y') }}</b><small>{{ $log->created_at?->format('g:i:s A') }} · {{ $log->created_at?->diffForHumans() }}</small></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="timeline-dot"></span>
                                <span><b>{{ $log->user?->name ?? 'System' }}</b><small class="capitalize">{{ $log->user?->role ?? 'guest' }}</small></span>
                            </div>
                        </td>
                        <td><span class="badge {{ $badge }}">{{ $log->action }}</span></td>
                        <td class="max-w-md text-slate-500">{{ $log->description }}</td>
                        <td class="font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-slate-500">No log entries match your filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $logs->links() }}</div>
</section>
@endsection