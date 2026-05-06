@extends('admin.layout')

@section('title', request('suspicious') ? 'Anomaly Detection' : 'Audit Logs')
@section('page-title', request('suspicious') ? '🚨 Anomaly Detection' : '📋 Audit Logs')

@section('topbar-actions')
    @if(request('suspicious'))
        <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary btn-sm">← All Logs</a>
    @else
        <a href="{{ route('admin.audit.index', ['suspicious' => 1]) }}" class="btn btn-sm" style="background:var(--red-bg);color:var(--red);border:1px solid #fecaca;">
            🚨 Anomalies
            @if($stats['suspicious'] > 0)
                <span style="background:#dc2626;color:#fff;border-radius:999px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;">{{ $stats['suspicious'] }}</span>
            @endif
        </a>
    @endif
@endsection

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['audit/style.css']) }}">
@endpush

@section('content')

{{-- ── Stats ── --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Logs</div>
        <div class="stat-val">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="stat-card {{ $stats['suspicious'] > 0 ? 'danger' : '' }}">
        <div class="stat-label">Suspicious Events</div>
        <div class="stat-val">{{ $stats['suspicious'] }}</div>
    </div>
    <div class="stat-card {{ $stats['failed_logins_today'] >= 5 ? 'danger' : '' }}">
        <div class="stat-label">Failed Logins Today</div>
        <div class="stat-val">{{ $stats['failed_logins_today'] }}</div>
    </div>
    <div class="stat-card {{ $stats['blocked_ips'] > 0 ? 'danger' : '' }}">
        <div class="stat-label">Blocked IPs</div>
        <div class="stat-val">{{ $stats['blocked_ips'] }}</div>
    </div>
</div>

{{-- ── Blocked IPs panel ── --}}
<div class="block-panel">
    <h3>🚫 IP Block Manager</h3>

    @if($blockedIps->isEmpty())
        <p style="font-size:.85rem;color:var(--red);opacity:.7;">No IPs currently blocked.</p>
    @else
    @foreach($blockedIps as $bip)
    <div class="blocked-ip-row">
        <code style="font-weight:700;color:var(--red);">{{ $bip->ip_address }}</code>
        <span style="color:var(--text-3);">{{ $bip->reason }}</span>
        @if($bip->expires_at)
            <span style="font-size:.75rem;color:var(--text-3);">Expires: {{ $bip->expires_at->format('d M Y H:i') }}</span>
        @else
            <span style="font-size:.75rem;color:var(--text-3);">Permanent</span>
        @endif
        <span style="font-size:.75rem;color:var(--text-3);">by {{ $bip->blocker?->name ?? '—' }}</span>
        <form action="{{ route('admin.audit.unblock-ip', $bip) }}" method="POST" style="margin-left:auto;">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green-border);">Unblock</button>
        </form>
    </div>
    @endforeach
    @endif

    {{-- Block new IP form --}}
    <form action="{{ route('admin.audit.block-ip') }}" method="POST" class="block-form">
        @csrf
        <div>
            <label style="font-size:.78rem;font-weight:600;color:var(--red);display:block;margin-bottom:.25rem;">IP Address</label>
            <input type="text" name="ip_address" class="filter-input" placeholder="e.g. 192.168.1.100" required pattern="^[\d\.:a-fA-F]+$" style="min-width:180px;" />
        </div>
        <div>
            <label style="font-size:.78rem;font-weight:600;color:var(--red);display:block;margin-bottom:.25rem;">Reason</label>
            <input type="text" name="reason" class="filter-input" placeholder="Reason (optional)" style="min-width:200px;" />
        </div>
        <div>
            <label style="font-size:.78rem;font-weight:600;color:var(--red);display:block;margin-bottom:.25rem;">Expires (optional)</label>
            <input type="datetime-local" name="expires_at" class="filter-input" />
        </div>
        <div style="padding-top:1.3rem;">
            <button type="submit" class="btn btn-sm" style="background:#dc2626;color:#fff;border:none;">Block IP</button>
        </div>
    </form>
</div>

{{-- ── Clear bar ── --}}
<div class="clear-bar">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2">
        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
    </svg>
    <span style="color:var(--amber);font-weight:500;">Purge old entries:</span>
    <form action="{{ route('admin.audit.clear') }}" method="POST" style="display:flex;gap:.5rem;align-items:center;">
        @csrf @method('DELETE')
        <select name="days" class="filter-input" style="padding:.3rem .5rem;font-size:.8rem;">
            <option value="30">Older than 30 days</option>
            <option value="60">Older than 60 days</option>
            <option value="90">Older than 90 days</option>
            <option value="180">Older than 180 days</option>
        </select>
        <button type="button" class="btn btn-sm" style="background:var(--amber-border);border:1px solid var(--amber);color:var(--amber);"
            onclick="cmsConfirm('Clear Logs', 'Clear old audit log entries? This cannot be undone.', 'Clear logs').then(ok => { if(ok) this.closest('form').submit(); })">
            Clear
        </button>
    </form>
</div>

{{-- ── Filters ── --}}
<form method="GET" action="{{ route('admin.audit.index') }}" class="filters-bar">
    @if(request('suspicious'))
        <input type="hidden" name="suspicious" value="1">
    @endif
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user, IP, action…" class="filter-input" style="min-width:210px;" />
    <select name="action" class="filter-input">
        <option value="">All Actions</option>
        <optgroup label="Auth">
            <option value="login"        @selected(request('action')==='login')>✅ Login</option>
            <option value="login.failed" @selected(request('action')==='login.failed')>❌ Failed Login</option>
            <option value="logout"       @selected(request('action')==='logout')>🚪 Logout</option>
        </optgroup>
        <optgroup label="Security">
            <option value="access.denied" @selected(request('action')==='access.denied')>🛑 Access Denied</option>
            <option value="ip.blocked"    @selected(request('action')==='ip.blocked')>🚫 IP Blocked</option>
        </optgroup>
        <optgroup label="Users">
            <option value="user.created"     @selected(request('action')==='user.created')>User Created</option>
            <option value="user.updated"     @selected(request('action')==='user.updated')>User Updated</option>
            <option value="user.deleted"     @selected(request('action')==='user.deleted')>User Deleted</option>
            <option value="user.activated"   @selected(request('action')==='user.activated')>User Activated</option>
            <option value="user.deactivated" @selected(request('action')==='user.deactivated')>User Deactivated</option>
        </optgroup>
    </select>
    <select name="user_id" class="filter-input">
        <option value="">All Users</option>
        @foreach($users as $u)
        <option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="filter-input" title="From date" />
    <input type="date" name="to"   value="{{ request('to') }}"   class="filter-input" title="To date" />
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    @if(request()->hasAny(['search','action','user_id','from','to']))
        <a href="{{ route('admin.audit.index', request('suspicious') ? ['suspicious'=>1] : []) }}" class="btn btn-secondary btn-sm">Clear</a>
    @endif
</form>

{{-- ── Table ── --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Location</th>
                    <th>IP</th>
                    <th>Target / Note</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="{{ $log->is_suspicious ? 'suspicious' : '' }}">
                    <td style="white-space:nowrap;font-size:.77rem;color:var(--text-3);">
                        <div>{{ $log->created_at->format('d M Y') }}</div>
                        <div>{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                    <td>
                        @if($log->user_name)
                        <div class="user-mini">
                            <div class="mini-avatar">{{ strtoupper(substr($log->user_name,0,1)) }}</div>
                            <div>
                                <div style="font-size:.8rem;font-weight:500;">{{ $log->user_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-3);">{{ $log->user_role }}</div>
                            </div>
                        </div>
                        @elseif($log->user_email)
                        <span style="font-size:.78rem;color:var(--text-3);">{{ $log->user_email }}</span>
                        @else
                        <span style="font-size:.75rem;color:var(--text-3);font-style:italic;">guest</span>
                        @endif
                    </td>
                    <td>
                        @php $bc = $log->actionBadgeClass(); @endphp
                        <span class="log-action {{ $bc }}">
                            {{ $log->actionIcon() }} {{ $log->action }}
                        </span>
                        @if($log->is_suspicious)
                        <div style="font-size:.68rem;color:var(--red);margin-top:.2rem;" title="{{ $log->suspicious_reason }}">
                            ⚠ {{ Str::limit($log->suspicious_reason,40) }}
                        </div>
                        @endif
                    </td>
                    <td class="geo-tag">
                        @if($log->country)
                            {{ $log->city ? $log->city.', ' : '' }}{{ $log->country }}
                        @else
                            <span style="color:var(--text-4);">—</span>
                        @endif
                    </td>
                    <td style="font-size:.77rem;font-family:monospace;">
                        @if($log->ip_address)
                        <span style="color:var(--text);">{{ $log->ip_address }}</span>
                        <button
                            onclick="prefillBlock('{{ $log->ip_address }}')"
                            title="Block this IP"
                            style="background:none;border:none;cursor:pointer;font-size:.7rem;color:var(--red);padding:0 .2rem;"
                        >🚫</button>
                        @else
                        <span style="color:var(--text-4);">—</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        @if($log->target_type)
                            <span style="color:var(--text-3);">{{ $log->target_type }}:</span>
                        @endif
                        {{ $log->target_label ?? $log->target_id ?? '—' }}
                    </td>
                    <td>
                        @if($log->old_values || $log->new_values)
                        <span class="diff-cell" onclick='showDetail(@json($log))'>View diff →</span>
                        @else
                        <span style="color:var(--text-4);font-size:.75rem;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-3);padding:2.5rem;">
                        {{ request('suspicious') ? 'No suspicious events detected. 🎉' : 'No audit log entries found.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Custom pagination --}}
@if($logs->hasPages())
<div class="pager">
    <span>
        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
    </span>
    <div class="pager-links">
        @if($logs->onFirstPage())
            <span class="pager-btn disabled">‹ Prev</span>
        @else
            <a class="pager-btn" href="{{ $logs->previousPageUrl() }}">‹ Prev</a>
        @endif

        @foreach(range(max(1,$logs->currentPage()-2), min($logs->lastPage(),$logs->currentPage()+2)) as $page)
            @if($page==$logs->currentPage())
                <span class="pager-btn active">{{ $page }}</span>
            @else
                <a class="pager-btn" href="{{ $logs->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if($logs->hasMorePages())
            <a class="pager-btn" href="{{ $logs->nextPageUrl() }}">Next ›</a>
        @else
            <span class="pager-btn disabled">Next ›</span>
        @endif
    </div>
</div>
@else
<div class="pager">
    <span>{{ $logs->total() }} entr{{ $logs->total()==1?'y':'ies' }}</span>
</div>
@endif

{{-- ── Detail modal ── --}}
<div class="dlg-back" id="det-back">
    <div class="dlg-box">
        <div class="dlg-head">
            <strong style="font-size:.95rem;" id="det-title">Log Entry</strong>
            <button onclick="closeDet()" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:1.2rem;">✕</button>
        </div>
        <div class="dlg-body" id="det-body"></div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ route('admin.page-asset', ['audit/script.js']) }}" defer></script>
@endpush
