@extends('admin.layout')
@section('title','My Activity')
@section('page-title','My Activity Log')

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Target</th>
                <th>IP Address</th>
                <th>Location</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>
                    <span class="cms-badge {{ $log->actionBadgeClass() }}">
                        {{ $log->actionIcon() }} {{ $log->action }}
                    </span>
                </td>
                <td style="font-size:.8rem;color:var(--text-2);">
                    {{ $log->target_label ? Str::limit($log->target_label, 50) : '—' }}
                </td>
                <td style="font-family:monospace;font-size:.78rem;color:var(--text-3);">{{ $log->ip_address ?? '—' }}</td>
                <td style="font-size:.78rem;color:var(--text-3);">
                    {{ $log->country ? $log->country . ($log->city ? ', '.$log->city : '') : '—' }}
                </td>
                <td style="font-size:.78rem;color:var(--text-3);" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                    {{ $log->created_at->diffForHumans() }}
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-3);">No activity yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links() }}
@endsection
