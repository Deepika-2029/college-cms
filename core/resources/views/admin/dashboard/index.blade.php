@extends('admin.layout')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('topbar-actions')
<a href="{{ route('admin.vbuilder3.page.new') }}?mode=page" class="btn-cms btn-cms-primary btn-cms-sm">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  New Page
</a>
@endsection

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['dashboard/style.css']) }}">
@endpush

@section('content')
@php $user = auth()->user(); @endphp

{{-- Welcome --}}
<div class="welcome-banner">
  @if($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;">
  @else
    <div class="welcome-av">{{ strtoupper(substr($user->name,0,1)) }}</div>
  @endif
  <div style="flex:1;min-width:0">
    <div class="welcome-name">Good {{ now()->hour<12?'morning':(now()->hour<17?'afternoon':'evening') }}, {{ explode(' ',$user->name)[0] }}</div>
    <div class="welcome-role">{{ $user->department ?: ($user->isSuperAdmin()?'Super Admin':'Admin') }}@if($lastLogin) &middot; Last login {{ $lastLogin }}@endif</div>
  </div>
  <div class="hide-mobile" style="font-size:.75rem;opacity:.75;text-align:right;flex-shrink:0">
    {{ now()->format('l, M j') }}<br>{{ now()->format('g:i A') }}
  </div>
</div>

@if($role === 'super_admin')
{{-- ══ Super Admin stats ══ --}}
<div class="stat-grid">
  <a href="{{ route('admin.vbuilder3.pages') }}" class="stat-card">
    <div class="stat-icon" style="background:var(--blue-bg)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="color:var(--blue)"><path d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
    </div>
    <div><div class="stat-value">{{ $stats['pages'] }}</div><div class="stat-label">Pages</div></div>
  </a>
  <a href="{{ route('admin.media.index') }}" class="stat-card">
    <div class="stat-icon" style="background:var(--accent-muted)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent)"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div><div class="stat-value">{{ $stats['media'] }}</div><div class="stat-label">Media</div></div>
  </a>
  <a href="{{ route('admin.users.index') }}" class="stat-card">
    <div class="stat-icon" style="background:var(--green-bg)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--green)"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div><div class="stat-value">{{ $stats['users'] }}</div><div class="stat-label">Users</div></div>
  </a>
  <a href="{{ route('admin.database.index') }}" class="stat-card">
    <div class="stat-icon" style="background:var(--amber-bg)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--amber)"><ellipse cx="12" cy="5" rx="9" ry="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 5v14c0 1.657 4.03 3 9 3s9-1.343 9-3V5" stroke="currentColor" stroke-width="1.5"/><path d="M3 12c0 1.657 4.03 3 9 3s9-1.343 9-3" stroke="currentColor" stroke-width="1.5"/></svg>
    </div>
    <div><div class="stat-value">{{ $stats['tables'] }}</div><div class="stat-label">Tables</div></div>
  </a>


  <a href="{{ route('admin.audit.index') }}" class="stat-card" style="{{ $stats['suspicious']>0?'border-color:var(--red-border)':'' }}">
    <div class="stat-icon" style="background:var(--red-bg)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--red)"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div><div class="stat-value" style="{{ $stats['suspicious']>0?'color:var(--red)':'' }}">{{ $stats['suspicious'] }}</div><div class="stat-label">Anomalies</div></div>
  </a>
</div>

<div class="dash-grid">
  {{-- Quick actions --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Quick Actions
      </div>
    </div>
    <div class="card-body">
      <div class="qa-grid">
        <a href="{{ route('admin.vbuilder3.page.new') }}?mode=page" class="qa-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          New Page
        </a>
        <a href="{{ route('admin.media.index') }}" class="qa-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Media
        </a>
        <a href="{{ route('admin.database.index') }}" class="qa-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="9" ry="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 5v14c0 1.657 4.03 3 9 3s9-1.343 9-3V5" stroke="currentColor" stroke-width="1.5"/></svg>
          Database
        </a>
        <a href="{{ route('admin.users.create') }}" class="qa-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          New User
        </a>

        <a href="{{ route('admin.settings.index') }}" class="qa-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
          Settings
        </a>

      </div>
    </div>
  </div>

  {{-- Recent activity --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Recent Activity
      </div>
      <a href="{{ route('admin.audit.index') }}" class="btn-cms btn-cms-ghost btn-cms-sm">All</a>
    </div>
    <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
      @forelse($recentAudit as $log)
      <div class="act-row">
        <span class="act-dot" style="background:var(--accent-l)"></span>
        <span class="act-badge">{{ $log->action }}</span>
        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-2);font-size:.78rem">
          {{ $log->user_name ?? 'System' }}@if($log->target_label) &mdash; {{ Str::limit($log->target_label,22) }}@endif
        </span>
        <span style="color:var(--text-4);font-size:.7rem;flex-shrink:0">{{ $log->created_at->diffForHumans(null,true) }}</span>
      </div>
      @empty
      <div class="empty-state" style="padding:1.5rem">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="empty-state-desc">No activity yet</div>
      </div>
      @endforelse
    </div>
  </div>

  @if($tables->count())
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Content Tables
      </div>
      <a href="{{ route('admin.database.index') }}" class="btn-cms btn-cms-ghost btn-cms-sm">Manage</a>
    </div>
    <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
      @foreach($tables->take(8) as $t)
      <div class="tbl-row">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="color:var(--accent);flex-shrink:0"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <a href="{{ route('admin.crud.index',$t->table_name) }}" style="flex:1;font-size:.82rem;font-weight:500;color:var(--text-2)">{{ $t->table_name }}</a>
        <a href="{{ route('admin.crud.create',$t->table_name) }}" class="btn-cms btn-cms-ghost btn-cms-sm" style="font-size:.72rem">+ Add</a>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  @if($recentUsers->count())
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Recent Users
      </div>
      <a href="{{ route('admin.users.index') }}" class="btn-cms btn-cms-ghost btn-cms-sm">All</a>
    </div>
    <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
      @foreach($recentUsers as $u)
      <div class="tbl-row" style="gap:.625rem">
        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-l));color:var(--text);font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ strtoupper(substr($u->name,0,1)) }}</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.82rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)">{{ $u->name }}</div>
          <div style="font-size:.7rem;color:var(--text-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u->email }}</div>
        </div>
        <span class="badge {{ $u->role==='super_admin'?'badge-accent':'badge-neutral' }}">{{ $u->role==='super_admin'?'Super':'Admin' }}</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif
</div>

@else
{{-- ══ Admin role ══ --}}

<div class="dash-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Quick Actions
      </div>
    </div>
    <div class="card-body">
      <div class="qa-grid">
        @if(auth()->user()->hasSystemPermission('page_builder'))
        <a href="{{ route('admin.vbuilder3.page.new') }}?mode=page" class="qa-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>New Page</a>
        @endif
        @if(auth()->user()->hasSystemPermission('media'))
        <a href="{{ route('admin.media.index') }}" class="qa-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>Upload</a>
        @endif
        <a href="{{ route('admin.my-logs') }}" class="qa-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>My Logs</a>
        <a href="{{ route('admin.profile.show') }}" class="qa-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>Profile</a>
      </div>
    </div>
  </div>

  {{-- My Permitted Content Tables widget --}}
  @if($tables->count())
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        My Content Tables
      </div>
    </div>
    <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
      @foreach($tables as $t)
      @php $tperm = \App\Models\TablePermission::where('user_id', auth()->id())->where('table_name', $t->table_name)->first(); @endphp
      <div class="tbl-row">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="color:var(--accent);flex-shrink:0"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <a href="{{ route('admin.crud.index', $t->table_name) }}" style="flex:1;font-size:.82rem;font-weight:500;color:var(--text-2)">{{ ucfirst(str_replace('_', ' ', $t->table_name)) }}</a>
        <div style="display:flex;gap:.2rem;flex-shrink:0">
          @if($tperm?->can_view)   <span class="badge badge-info"    style="font-size:.58rem;padding:.1rem .3rem">V</span>@endif
          @if($tperm?->can_create) <span class="badge badge-success" style="font-size:.58rem;padding:.1rem .3rem">C</span>@endif
          @if($tperm?->can_edit)   <span class="badge badge-warning" style="font-size:.58rem;padding:.1rem .3rem">E</span>@endif
          @if($tperm?->can_delete) <span class="badge badge-danger"  style="font-size:.58rem;padding:.1rem .3rem">D</span>@endif
        </div>
      </div>
      @endforeach
    </div>
    <div class="card-footer" style="font-size:.7rem;color:var(--text-3)">
      <span style="display:inline-flex;gap:.5rem;flex-wrap:wrap">
        <span><span class="badge badge-info" style="font-size:.6rem">V</span> View</span>
        <span><span class="badge badge-success" style="font-size:.6rem">C</span> Create</span>
        <span><span class="badge badge-warning" style="font-size:.6rem">E</span> Edit</span>
        <span><span class="badge badge-danger" style="font-size:.6rem">D</span> Delete</span>
      </span>
    </div>
  </div>
  @else
  <div class="card">
    <div class="card-body" style="text-align:center;padding:2rem;color:var(--text-3)">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" style="margin:0 auto 1rem;opacity:.3"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5"/></svg>
      <div style="font-size:.85rem;font-weight:600;color:var(--text-2)">No tables assigned</div>
      <div style="font-size:.78rem;margin-top:.25rem">Ask a Super Admin to grant you table access.</div>
    </div>
  </div>
  @endif

  {{-- My Recent Activity --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        My Recent Activity
      </div>
      <a href="{{ route('admin.my-logs') }}" class="btn-cms btn-cms-ghost btn-cms-sm">All</a>
    </div>
    <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
      @forelse($myLogs as $log)
      <div class="act-row">
        <span class="act-dot" style="background:var(--accent-l)"></span>
        <span class="act-badge">{{ $log->action }}</span>
        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-2);font-size:.78rem">{{ $log->target_label ? Str::limit($log->target_label,28) : '' }}</span>
        <span style="color:var(--text-4);font-size:.7rem;flex-shrink:0">{{ $log->created_at->diffForHumans(null,true) }}</span>
      </div>
      @empty
      <div style="color:var(--text-4);font-size:.82rem;padding:.5rem 0">No activity yet.</div>
      @endforelse
    </div>
  </div>
</div>
@endif
@endsection

