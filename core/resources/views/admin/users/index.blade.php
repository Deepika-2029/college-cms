@extends('admin.layout')
@section('title','Users')
@section('page-title','User Management')

@section('topbar-actions')
<a href="{{ route('admin.users.create') }}" class="btn-cms btn-cms-primary btn-cms-sm">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    New User
</a>
@endsection

@push('styles')
<style>
/* Modern Elegant Layout for Users */
.card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    padding: 1.5rem; box-shadow: var(--shadow-sm); overflow: hidden;
    margin-bottom: 2rem;
}
.header-actions {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;
}
.search-form {
    display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;
}
.form-control, .form-select {
    padding: 0.5rem 0.85rem; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 6px; color: var(--text); font-size: 0.9rem;
}
.form-control:focus, .form-select:focus {
    outline: none; border-color: var(--accent);
}
.table-wrap {
    background: var(--surface-2); 
    border: 1px solid var(--border); 
    border-radius: 8px;
    overflow-x: auto;
    position: relative;
    /* CSS scroll shadows */
    background: linear-gradient(to right, var(--surface-2) 30%, rgba(255,255,255,0)), linear-gradient(to right, rgba(255,255,255,0), var(--surface-2) 70%) 0 100%, radial-gradient(farthest-side at 0 50%, rgba(0,0,0,0.2), rgba(0,0,0,0)), radial-gradient(farthest-side at 100% 50%, rgba(0,0,0,0.2), rgba(0,0,0,0)) 0 100%;
    background-repeat: no-repeat;
    background-color: var(--surface-2);
    background-size: 40px 100%, 40px 100%, 14px 100%, 14px 100%;
    background-position: 0 0, 100%, 0 0, 100%;
    background-attachment: local, local, scroll, scroll;
}
.data-table {
    width: 100%; border-collapse: collapse; font-size: 0.9rem;
    min-width: 700px; /* Force minimum width for mobile scroll */
}
.data-table th, .data-table td {
    padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); text-align: left;
    color: var(--text-2);
}
.data-table th {
    font-weight: 600; color: var(--text-3); background: color-mix(in srgb, var(--surface) 50%, var(--surface-2)); text-transform: uppercase;
    font-size: 0.75rem; letter-spacing: 0.05em;
}
.data-table tr:hover { background: rgba(255, 255, 255, 0.02); }
.data-table tr:last-child td { border-bottom: none; }
.badge {
    padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
}
.badge-purple { background: rgba(var(--accent-rgb), 0.15); color: var(--accent-l); border: 1px solid rgba(var(--accent-rgb), 0.3); }
.badge-yellow { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-border); }

/* Styled Alert Box */
.cms-confirm-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0, 0.6); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.2s;
}
.cms-confirm-overlay.active { opacity: 1; visibility: visible; }
.cms-confirm-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
    padding: 2.5rem 2rem; width: 90%; max-width: 420px;
    box-shadow: var(--shadow-lg);
    transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.cms-confirm-overlay.active .cms-confirm-card {
    transform: translateY(0) scale(1);
}
</style>
@endpush

@section('content')

<div class="card">
    <div class="header-actions">
        <div>
            <span style="color:var(--text-3);font-size:0.95rem;font-weight:600;">
                All Registered Users
                @if(request()->hasAny(['search','role','status']))
                 <span style="color:var(--accent);">(Filtered)</span>
                @endif
            </span>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="search-form">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name or email…" class="form-control" style="width:200px;" />
            
            <select name="role" class="form-select" style="width:140px;">
                <option value="">All Roles</option>
                <option value="super_admin" @selected(request('role')==='super_admin')>Super Admin</option>
                <option value="admin"       @selected(request('role')==='admin')>Admin</option>
            </select>
            
            <select name="status" class="form-select" style="width:130px;">
                <option value="">All Status</option>
                <option value="active"   @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
            
            <button type="submit" class="btn-cms btn-cms-primary btn-cms-sm">Filter</button>
            @if(request()->hasAny(['search','role','status']))
                <a href="{{ route('admin.users.index') }}" class="btn-cms btn-cms-secondary btn-cms-sm">Clear</a>
            @endif
        </form>
    </div>

    @if($users->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--text-3);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 style="margin:0 auto 1rem;display:block;opacity:0.3;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <p style="font-size:0.9rem;">No users found matching criteria.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th style="width:160px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr id="user-row-{{ $user->id }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:.75rem;">
                                @if($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt=""
                                         style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid var(--border);">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-l));color:white;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        {{ strtoupper(substr($user->name,0,1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;font-size:.875rem;color:var(--text);">
                                        {{ $user->name }}
                                        @if($user->id===auth()->id())
                                            <span style="font-size:.7rem;color:var(--text-3);font-weight:400;margin-left:4px;">(you)</span>
                                        @endif
                                    </div>
                                    <div style="font-size:.78rem;color:var(--text-3);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $user->role==='super_admin'?'badge-yellow':'badge-purple' }}">
                                {{ $user->role==='super_admin'?'👑 Super Admin':'🛠 Admin' }}
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-2);">{{ $user->department ?: '—' }}</td>
                        <td>
                            @if($user->id!==auth()->id())
                            <button class="flex items-center gap-1"
                                    style="background:none;border:none;cursor:pointer;padding:.2rem;border-radius:4px;display:flex;align-items:center;"
                                    onclick="toggleStatus({{ $user->id }},this)"
                                    data-status="{{ $user->status?'1':'0' }}">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $user->status?'var(--green)':'var(--red)' }};display:inline-block;box-shadow:0 0 5px {{ $user->status?'rgba(var(--green-rgb),0.5)':'rgba(var(--red-rgb),0.5)' }};"></span>
                                <span style="font-size:.8rem;color:var(--text-2);">{{ $user->status?'Active':'Inactive' }}</span>
                            </button>
                            @else
                            <span class="flex items-center gap-1" style="display:flex;align-items:center;">
                                <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;box-shadow:0 0 5px rgba(var(--green-rgb),0.5);"></span>
                                <span style="font-size:.8rem;color:var(--text-2);">Active</span>
                            </span>
                            @endif
                        </td>
                        <td style="color:var(--text-3);font-size:.8rem;">{{ $user->created_at->format('M j, Y') }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                                <a href="{{ route('admin.users.edit',$user) }}" class="btn-cms btn-cms-secondary btn-cms-sm">
                                    Edit
                                </a>
                                @if($user->id!==auth()->id())
                                <form id="del-user-{{ $user->id }}" method="POST"
                                      action="{{ route('admin.users.destroy',$user) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" onclick="openDeleteConfirm('del-user-{{ $user->id }}', '{{ htmlspecialchars(addslashes($user->email)) }}')"
                                        class="btn-cms btn-cms-danger btn-cms-sm">
                                    Delete
                                </button>
                                @else
                                <button class="btn-cms btn-cms-secondary btn-cms-sm" disabled style="opacity:0.5;">
                                    Delete
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($users->hasPages())
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1.5rem;font-size:.85rem;color:var(--text-3);flex-wrap:wrap;gap:.5rem;border-top:1px solid var(--border);padding-top:1.25rem;">
        <span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</span>
        <div style="display:flex;gap:.3rem;">
            @if($users->onFirstPage())
                <span class="btn-cms btn-cms-secondary btn-cms-sm" style="opacity:.4;pointer-events:none;">‹ Prev</span>
            @else
                <a href="{{ $users->previousPageUrl() }}" class="btn-cms btn-cms-secondary btn-cms-sm">‹ Prev</a>
            @endif
            
            @foreach(range(max(1,$users->currentPage()-2),min($users->lastPage(),$users->currentPage()+2)) as $pg)
                @if($pg==$users->currentPage())
                    <span class="btn-cms btn-cms-primary btn-cms-sm">{{ $pg }}</span>
                @else
                    <a href="{{ $users->url($pg) }}" class="btn-cms btn-cms-secondary btn-cms-sm">{{ $pg }}</a>
                @endif
            @endforeach
            
            @if($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="btn-cms btn-cms-secondary btn-cms-sm">Next ›</a>
            @else
                <span class="btn-cms btn-cms-secondary btn-cms-sm" style="opacity:.4;pointer-events:none;">Next ›</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Custom Styled Confirmation Modal --}}
<div id="cms-confirm-modal" class="cms-confirm-overlay">
    <div id="cms-confirm-box" class="cms-confirm-card">
        <div style="font-size:3.5rem;margin-bottom:1rem;text-align:center;line-height:1;">⚠️</div>
        <div id="cms-confirm-title" style="font-size:1.35rem;font-weight:700;margin-bottom:0.75rem;color:var(--text);text-align:center;">
            Confirm Deletion
        </div>
        <div id="cms-confirm-msg" style="color:var(--text-3);font-size:1rem;margin-bottom:2rem;text-align:center;line-height:1.5;">
            Are you sure you want to permanently delete this user? This action cannot be undone.
        </div>
        <div style="display:flex;gap:1rem;justify-content:center;">
            <button class="btn-cms btn-cms-secondary" onclick="closeConfirmModal()" style="padding:0.75rem 1.5rem;font-size:1rem;">Cancel</button>
            <button class="btn-cms btn-cms-danger" id="cms-confirm-btn" style="padding:0.75rem 1.5rem;font-size:1rem;">Yes, Delete</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentDeleteFormId = null;
function openDeleteConfirm(formId, email) {
    currentDeleteFormId = formId;
    document.getElementById('cms-confirm-msg').innerHTML = 'Permanently delete <strong>' + email + '</strong>? This cannot be undone.';
    document.getElementById('cms-confirm-modal').classList.add('active');
}
function closeConfirmModal() {
    document.getElementById('cms-confirm-modal').classList.remove('active');
    currentDeleteFormId = null;
}
document.getElementById('cms-confirm-btn').addEventListener('click', function() {
    if(currentDeleteFormId) document.getElementById(currentDeleteFormId).submit();
});
</script>
<script src="{{ route('admin.page-asset', ['users/script.js']) }}" defer></script>
@endpush
