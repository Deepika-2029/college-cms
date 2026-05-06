@extends('admin.layout')

@section('title', 'Table Permissions')
@section('page-title', 'Table Permissions')

@push('styles')
<style>
    .perm-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 1rem; }
    .perm-table th, .perm-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: center; }
    .perm-table th:first-child, .perm-table td:first-child { text-align: left; position: sticky; left: 0; background: var(--bg); z-index: 10; border-right: 1px solid var(--border); }
    .perm-table thead th { background: var(--surface); position: sticky; top: 0; z-index: 11; border-top: 1px solid var(--border); }
    .perm-table thead th:first-child { z-index: 12; }
    
    .chk-wrap { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
    .chk-wrap input[type="checkbox"] { width: 1.1rem; height: 1.1rem; cursor: pointer; accent-color: var(--accent); }
    
    .user-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .user-info { display: flex; gap: 1rem; align-items: center; }
    .user-av { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
</style>
@endpush

@section('content')

@if($admins->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <div class="empty-state-title">No Admin Users</div>
        <div class="empty-state-desc">Create admin users to assign table permissions. Super Admins already have full access.</div>
        <a href="{{ route('admin.users.create') }}" class="btn-cms btn-cms-primary" style="margin-top: 1rem;">Create User</a>
    </div>
@else
    
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-2);">
        Manage CRUD (Create, Read, Update, Delete) permissions for all tables per admin user.<br>
        <strong>Note:</strong> Super Admins inherently have full access and are not listed here.
    </div>

    @foreach($admins as $admin)
        <div class="user-card">
            <div class="user-info">
                @if($admin->avatarUrl())
                    <img src="{{ $admin->avatarUrl() }}" class="user-av" style="object-fit: cover;">
                @else
                    <div class="user-av">{{ strtoupper(substr($admin->name,0,1)) }}</div>
                @endif
                <div>
                    <div style="font-weight: 600; color: var(--text);">{{ $admin->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-3);">{{ $admin->email }}</div>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <form action="{{ route('admin.permissions.grant-all', $admin->id) }}" method="POST" onsubmit="event.preventDefault(); cmsConfirm('Grant Access', 'Grant full access to {{ $admin->name }}?', 'Grant All').then(ok => { if(ok) this.submit(); });">
                    @csrf
                    <button class="btn-cms btn-cms-secondary btn-cms-sm">Grant All</button>
                </form>
                <form action="{{ route('admin.permissions.revoke-all', $admin->id) }}" method="POST" onsubmit="event.preventDefault(); cmsConfirm('Revoke Access', 'Revoke ALL access from {{ $admin->name }}?', 'Revoke All').then(ok => { if(ok) this.submit(); });">
                    @csrf
                    <button class="btn-cms btn-cms-danger btn-cms-sm">Revoke All</button>
                </form>
                <a href="{{ route('admin.permissions.user', $admin->id) }}" class="btn-cms btn-cms-primary btn-cms-sm">Edit Specifics</a>
            </div>
        </div>
    @endforeach

@endif

@endsection
