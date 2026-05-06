@extends('admin.layout')
@section('title', $user ? 'Edit User' : 'New User')
@section('page-title', $user ? 'Edit User: '.$user->name : 'New User')

@section('topbar-actions')
<a href="{{ route('admin.users.index') }}" class="btn-cms btn-cms-secondary">
    Back
</a>
@endsection

@push('styles')
<style>
/* Modern Elegant Form Styling */
.card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;
}
.card-title {
    font-size: 1.25rem; font-weight: 700; margin-bottom: 2rem;
    color: var(--text); border-bottom: 1px solid var(--border); padding-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem; justify-content: space-between;
}
.card-title-left {
    display: flex; align-items: center; gap: 0.5rem;
}
.form-group { margin-bottom: 1.5rem; }
.form-group label {
    display: block; font-size: 0.95rem; font-weight: 600;
    color: var(--text-2); margin-bottom: 0.6rem;
}
.form-control, .form-select {
    width: 100%; padding: 0.85rem 1.15rem; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: 8px; color: var(--text);
    font-size: 1rem; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}
.form-control:focus, .form-select:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(var(--accent-rgb), 0.15); background: var(--surface);
}

/* Role Selector Box */
.role-box {
    padding: 1rem; border: 1px solid var(--border); border-radius: 8px;
    background: color-mix(in srgb, var(--surface) 50%, var(--surface-2)); cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 1rem;
}
.role-box:hover { border-color: var(--text-3); }
.role-box.active-super { border-color: var(--amber); background: var(--amber-bg); }
.role-box.active-admin { border-color: var(--accent); background: rgba(var(--accent-rgb), 0.08); }

/* Switch Toggle Styling */
.form-switch .form-check-input {
    width: 3rem; height: 1.5rem; cursor: pointer; background-color: var(--border);
    border-color: var(--border);
}
.form-switch .form-check-input:checked {
    background-color: var(--green); border-color: var(--green);
}

/* IP List */
.ip-row {
    display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;
    background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 0.5rem;
}
</style>
@endpush

@section('content')
<div style="max-width:800px;">

<form action="{{ $user ? route('admin.users.update',$user) : route('admin.users.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($user) @method('PUT') @endif

    {{-- Basic Info --}}
    <div class="card">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Basic Info
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 form-group">
                <label>Full Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name',$user?->name) }}" required maxlength="100" />
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 form-group">
                <label>Email Address <span style="color:var(--red)">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email',$user?->email) }}" required maxlength="150" />
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 form-group">
                <label>Department</label>
                <input type="text" name="department" class="form-control"
                       value="{{ old('department',$user?->department) }}" maxlength="150"
                       placeholder="e.g. Content Team" />
            </div>
            <div class="col-md-6 form-group">
                <label>Bio</label>
                <textarea name="bio" class="form-control" rows="2" maxlength="500"
                          placeholder="Short bio…">{{ old('bio',$user?->bio) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Password --}}
    <div class="card">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ $user ? 'Change Password' : 'Password' }}
            </div>
            @if($user)
            <span style="font-size:0.8rem;color:var(--text-3);font-weight:normal;">Leave blank to keep current</span>
            @endif
        </div>
        <div class="row g-4">
            <div class="col-md-6 form-group">
                <label>{{ $user?'New Password':'Password' }} {{ $user?'':'*' }}</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwField1"
                           class="form-control @error('password') is-invalid @enderror"
                           {{ $user?'':'required' }} minlength="10" autocomplete="new-password"
                           placeholder="Min 10 chars" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                    <button type="button" class="btn pw-toggle-btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" onclick="togglePw('pwField1', this)">
                        <span class="icon-hide"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="icon-show" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    </button>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div style="font-size:0.75rem; color:var(--text-3); margin-top:0.4rem;">10+ chars, uppercase, number, special char.</div>
            </div>
            <div class="col-md-6 form-group">
                <label>Confirm Password {{ $user?'':'*' }}</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="pwField2"
                           class="form-control" {{ $user?'':'required' }} minlength="10" autocomplete="new-password"
                           placeholder="Confirm chars" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                    <button type="button" class="btn pw-toggle-btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" onclick="togglePw('pwField2', this)">
                        <span class="icon-hide"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="icon-show" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Role --}}
    {{-- Role --}}
    {{-- Role --}}
    <div class="card">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Role Assignment
            </div>
        </div>
        <div class="row g-4">
            @if(auth()->user()->isSuperAdmin())
            <div class="col-md-6">
                <input type="radio" id="r_super" name="role" value="super_admin" 
                       {{ old('role', $user?->role ?? '') === 'super_admin' ? 'checked' : '' }} style="display:none;" onchange="updateRoleUI()" />
                <label class="role-box" id="box_super" for="r_super">
                    <span style="font-size:2rem; width:40px; text-align:center;">👑</span>
                    <div>
                        <div style="font-weight:600; font-size:1.05rem; color:var(--text);">Super Admin</div>
                        <div style="font-size:0.8rem; color:var(--text-3);">Full system access</div>
                    </div>
                </label>
            </div>
            @endif
            <div class="col-md-6">
                <input type="radio" id="r_admin" name="role" value="admin" 
                       {{ old('role', $user?->role ?? 'admin') === 'admin' ? 'checked' : '' }} style="display:none;" onchange="updateRoleUI()" />
                <label class="role-box" id="box_admin" for="r_admin">
                    <span style="font-size:2rem; width:40px; text-align:center;">🛠</span>
                    <div>
                        <div style="font-weight:600; font-size:1.05rem; color:var(--text);">Admin</div>
                        <div style="font-size:0.8rem; color:var(--text-3);">Manage content and media</div>
                    </div>
                </label>
            </div>
        </div>
        

        
        @error('role')<div class="text-danger mt-2" style="font-size:0.85rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Status --}}
    <div class="card">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Account Status
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
            <div class="form-check form-switch mb-0" style="padding-left:0;">
                <input class="form-check-input" type="checkbox" name="status" value="1"
                       id="statusToggle" style="margin-left:0;" onchange="updateStatusUI()"
                       {{ old('status',$user?->status??true)?'checked':'' }} />
            </div>
            <div>
                <div id="statusLabelTitle" style="font-weight:600; font-size:1.05rem; color:var(--text);">
                    {{ old('status',$user?->status??true)?'Active':'Inactive' }}
                </div>
                <div style="font-size:0.85rem; color:var(--text-3);">Inactive users cannot log in to the CMS</div>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:1rem; margin-bottom: 2rem;">
        <button type="submit" class="btn-cms btn-cms-primary">
            {{ $user?'Save Changes':'Create User' }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn-cms btn-cms-secondary">Cancel</a>
    </div>
</form>

{{-- ── Super Admin extras: IP Allowlist + Password Reset ─────────────── --}}
@if($user && auth()->user()->isSuperAdmin())
<div style="margin-top:3rem; border-top: 1px solid #334155; padding-top:2rem;">

    <h3 style="font-size:1.15rem; color:var(--text); margin-bottom: 1.5rem;">Super Admin Tools</h3>

    {{-- Password Reset --}}
    <div class="card" x-data="{show:false}">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--amber);"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Force Reset Password
            </div>
        </div>
        <p style="font-size:0.9rem; color:var(--text-3); margin-bottom:1.5rem;">
            Reset {{ $user->name }}'s password without needing their current one.
        </p>
        <form method="POST" action="{{ route('admin.users.reset-password',$user) }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5 form-group mb-0">
                    <label>New Password</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="pwField3" class="form-control"
                               required minlength="10" placeholder="Min 10 chars" autocomplete="new-password" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                        <button type="button" class="btn pw-toggle-btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" onclick="togglePw('pwField3', this)">
                            <span class="icon-hide"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="icon-show" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                    </div>
                </div>
                <div class="col-md-5 form-group mb-0">
                    <label>Confirm Password</label>
                    <input type="password" name="new_password_confirmation" class="form-control"
                           required autocomplete="new-password" placeholder="Confirm" />
                </div>
                <div class="col-md-2 form-group mb-0">
                    <button type="submit" class="btn-cms btn-cms-primary w-100"
                            onclick="event.preventDefault(); cmsConfirm('Reset Password', 'Reset password for {{ addslashes($user->name) }}?', 'Reset').then(ok => { if(ok) this.closest('form').submit(); })">
                        Reset
                    </button>
                </div>
            </div>
            <div style="font-size:0.75rem; color:var(--text-3); margin-top:0.6rem;">10+ chars, uppercase, digit, special char required.</div>
        </form>
    </div>

    {{-- IP Allowlist --}}
    <div class="card">
        <div class="card-title">
            <div class="card-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--green);"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                IP Allowlist
            </div>
            <span class="badge badge-purple">{{ $ipAllowlist->count() }} {{ Str::plural('entry',$ipAllowlist->count()) }}</span>
        </div>
        <p style="font-size:0.9rem; color:var(--text-3); margin-bottom:1.5rem;">
            If any IPs are recorded here, this user can <strong>only</strong> log in from those matching IPs. Leave empty for open access.
        </p>

        <div style="margin-bottom: 2rem;">
            @forelse($ipAllowlist as $ipEntry)
            <div class="ip-row">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="color:var(--green);"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <code style="font-size:0.95rem; color:var(--text); font-weight:600; flex:1;">{{ $ipEntry->ip_address }}</code>
                @if($ipEntry->label)
                    <span style="font-size:0.75rem; color:var(--text); background:var(--surface-3); padding:0.25rem 0.6rem; border-radius:999px;">{{ $ipEntry->label }}</span>
                @endif
                <span style="font-size:0.8rem; color:var(--text-3); margin-left:1rem; margin-right:1rem;">{{ $ipEntry->created_at->diffForHumans() }}</span>
                <form method="POST" action="{{ route('admin.users.remove-ip',[$user,$ipEntry]) }}" style="margin:0;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-cms btn-cms-danger btn-cms-sm"
                            onclick="event.preventDefault(); cmsConfirm('Remove IP', 'Remove IP {{ $ipEntry->ip_address }}?', 'Remove').then(ok => { if(ok) this.closest('form').submit(); })">
                        Remove
                    </button>
                </form>
            </div>
            @empty
            <div style="color:var(--text-3); font-size:0.9rem; font-style:italic; padding:1rem; text-align:center; background:var(--surface-2); border-radius:8px; border:1px solid var(--border);">
                No IP restrictions — user can log in from anywhere.
            </div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.users.add-ip',$user) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-5 form-group mb-0">
                <label>Add new IP Address</label>
                <input type="text" name="ip_address" class="form-control" placeholder="e.g. 192.168.1.1"
                       pattern="^[0-9a-fA-F.:]+$" required />
            </div>
            <div class="col-md-5 form-group mb-0">
                <label>Label <span style="font-weight:normal; color:var(--text-3);">(optional)</span></label>
                <input type="text" name="label" class="form-control" placeholder="e.g. Office Network" maxlength="100" />
            </div>
            <div class="col-md-2 form-group mb-0">
                <button type="submit" class="btn-cms btn-cms-secondary w-100">Add</button>
            </div>
        </form>
    </div>
</div>
@endif

</div>

@push('scripts')
<script>
    // Vanilla JS Controller for User Form
    function updateRoleUI() {
        var rSuper = document.getElementById('r_super');
        var rAdmin = document.getElementById('r_admin');
        var isSuper = rSuper && rSuper.checked;
        var isAdmin = rAdmin && rAdmin.checked;
        
        var boxSuper = document.getElementById('box_super');
        var boxAdmin = document.getElementById('box_admin');
        if(boxSuper) {
            if(isSuper) boxSuper.classList.add('active-super');
            else boxSuper.classList.remove('active-super');
        }
        if(boxAdmin) {
            if(isAdmin) boxAdmin.classList.add('active-admin');
            else boxAdmin.classList.remove('active-admin');
        }
        
    }

    function updateStatusUI() {
        var statusOk = document.getElementById('statusToggle').checked;
        document.getElementById('statusLabelTitle').textContent = statusOk ? 'Active' : 'Inactive';
    }

    function togglePw(fieldId, btnEl) {
        var inp = document.getElementById(fieldId);
        var isText = inp.type === 'text';
        inp.type = isText ? 'password' : 'text';
        btnEl.querySelector('.icon-hide').style.display = isText ? 'block' : 'none';
        btnEl.querySelector('.icon-show').style.display = isText ? 'none' : 'block';
    }

    // Initialize all UI elements accurately on DOM Load
    document.addEventListener('DOMContentLoaded', function() {
        updateRoleUI();
        updateStatusUI();
    });
</script>
@endpush
@endsection
