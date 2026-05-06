@extends('admin.layout')
@section('title','My Profile')
@section('page-title','👤 My Profile')

@push('styles')
<style>
/* Modern Elegant Layout for Profile */
.profile-shell {
    display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start;
}
@media(max-width:900px){ 
    .profile-shell { grid-template-columns: 1fr; gap: 1.5rem; } 
}

/* Avatar card */
.av-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    overflow: hidden; box-shadow: var(--shadow-sm);
}
.av-cover {
    height: 100px;
    background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.8), var(--accent-l)), url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 20.5V18H0v-2h20v-2H0v-2h20v-2H0V8h20V6H0V4h20V2H0V0h22v20h2V0h2v20h2V0h2v20h2V0h2v20h2V0h2v20h-2v20h-2V22h-2v20h-2V22h-2v20h-2V22h-2v20h-2V22h-2v20h-2V22h-2v20h-2V22H0v-2h20z' fill='rgba(255,255,255,0.05)' fill-rule='evenodd'/%3E%3C/svg%3E");
    background-size: cover;
}
.av-body {
    padding: 0 1.5rem 1.5rem; text-align: center;
}
.av-img {
    width: 96px; height: 96px; border-radius: 50%; border: 4px solid var(--surface);
    margin: -48px auto 1rem; display: block; object-fit: cover;
    background: linear-gradient(135deg, var(--accent), var(--accent-l)); color: white;
    font-size: 2.2rem; font-weight: 800; line-height: 88px; text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.av-name { font-weight: 700; font-size: 1.1rem; color: var(--text); }
.av-email { font-size: 0.85rem; color: var(--text-3); margin-top: 0.2rem; word-break: break-all; }
.av-badge { display: inline-block; margin: 0.75rem auto 0; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 999px; }
.badge-purple { background: rgba(var(--accent-rgb), 0.15); color: var(--accent-l); border: 1px solid rgba(var(--accent-rgb), 0.3); }
.badge-yellow { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-border); }

.av-stat {
    display: flex; justify-content: space-around; padding: 1rem 0;
    border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin: 1.25rem 0;
}
.av-stat-n { font-size: 1.3rem; font-weight: 800; color: var(--text); line-height: 1; }
.av-stat-l { font-size: 0.7rem; color: var(--text-3); font-weight: 500; margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.05em; }

/* Section cards */
.p-section {
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.p-section-head {
    padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex;
    align-items: center; justify-content: space-between;
}
.p-section-title {
    font-weight: 700; font-size: 1.05rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;
}
.p-section-body { padding: 1.5rem; }

/* Forms inside sections */
.form-group { margin-bottom: 1.5rem; }
.form-group label {
    display: block; font-size: 0.95rem; font-weight: 600;
    color: var(--text-2); margin-bottom: 0.6rem;
}
.form-control {
    width: 100%; padding: 0.85rem 1.15rem; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: 8px; color: var(--text);
    font-size: 1rem; transition: all 0.3s ease;
}
.form-control:focus {
    outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(var(--accent-rgb), 0.15);
}

/* Permission badges */
.perm-row {
    display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0;
    border-bottom: 1px solid var(--border); font-size: 0.85rem;
}
.perm-row:last-child { border-bottom: none; }

/* Login history */
.lh-row {
    display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0;
    border-bottom: 1px solid var(--border); font-size: 0.85rem;
}
.lh-row:last-child { border-bottom: none; }
.lh-badge-green { background: var(--green-bg); color: var(--green); padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem; }
.lh-badge-red { background: var(--red-bg); color: var(--red); padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem; }
.lh-badge-gray { background: var(--surface-3); color: var(--text-2); padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.7rem; }

/* Password strength */
.pw-bar-wrap { height: 4px; background: var(--border); border-radius: 2px; margin-top: 0.5rem; overflow: hidden; }
.pw-bar { height: 100%; border-radius: 2px; transition: width 0.3s ease, background 0.3s ease; width: 0; }
</style>
@endpush

@section('content')

@php
$totalActions = \App\Models\AuditLog::where('user_id',$user->id)->count();
$loginCount   = \App\Models\AuditLog::where('user_id',$user->id)->where('action','login')->count();
$mediaCount   = \App\Models\MediaFile::where('uploaded_by',$user->id)->count();
$loginHistory = \App\Models\AuditLog::where('user_id',$user->id)
    ->whereIn('action',['login','login.failed','logout'])
    ->latest()->limit(12)->get();
@endphp

<div class="profile-shell">

    {{-- ── LEFT: Identity card ── --}}
    <div>
        <div class="av-card">
            <div class="av-cover"></div>
            <div class="av-body">
                @if($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" class="av-img" alt="" />
                @else
                    <div class="av-img">
                        {{ substr($user->name,0,1) }}
                    </div>
                @endif
                <div class="av-name">{{ $user->name }}</div>
                <div class="av-email">{{ $user->email }}</div>
                <span class="av-badge {{ $user->role==='super_admin'?'badge-yellow':'badge-purple' }}">
                    {{ $user->role==='super_admin'?'👑 Super Admin':'🛠 Admin' }}
                </span>
                @if($user->department)
                <div style="font-size:0.85rem;color:var(--text-3);margin-top:0.75rem;font-weight:500;">
                    {{ $user->department }}
                </div>
                @endif
                @if($user->bio)
                <div style="font-size:0.85rem;color:var(--text-3);margin-top:0.75rem;line-height:1.6;text-align:left;background:var(--surface-2);padding:1rem;border-radius:8px;">
                    {{ $user->bio }}
                </div>
                @endif

                {{-- Stats --}}
                <div class="av-stat">
                    <div style="text-align:center;">
                        <div class="av-stat-n">{{ number_format($totalActions) }}</div>
                        <div class="av-stat-l">Actions</div>
                    </div>
                    <div style="text-align:center;">
                        <div class="av-stat-n">{{ number_format($loginCount) }}</div>
                        <div class="av-stat-l">Logins</div>
                    </div>
                    <div style="text-align:center;">
                        <div class="av-stat-n">{{ number_format($mediaCount) }}</div>
                        <div class="av-stat-l">Uploads</div>
                    </div>
                </div>

                <div style="font-size:0.75rem;color:var(--text-3);">
                    Member since {{ $user->created_at->format('M Y') }}
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="p-section" style="margin-top:1.5rem;">
            <div class="p-section-head">
                <span class="p-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.114 2.007-.327 2.95m5.772 4.41l.03-.047C20.472 16.634 21 14.887 21 13M15 11c0-1.874-.707-3.585-1.87-4.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Permissions</span>
            </div>
            <div style="padding:1rem 1.5rem;">
                @foreach($permissions as $key => $perm)
                <div class="perm-row">
                    {!! $perm['granted'] ? '<span style="color:var(--green);display:flex;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>' : '<span style="color:var(--text-3);display:flex;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></span>' !!}
                    <span style="color:{{ $perm['granted']?'var(--text)':'var(--text-3)' }};">{{ $perm['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Edit sections ── --}}
    <div>

        {{-- Session alerts --}}
        @if(session('success_password'))
        <div class="cms-alert alert-success" style="margin-bottom:1.5rem; background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); padding:1rem; border-radius:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="mr-2 inline" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>{{ session('success_password') }}</span>
        </div>
        @endif

        {{-- Basic Info --}}
        <div class="p-section">
            <div class="p-section-head">
                <span class="p-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Basic Info</span>
            </div>
            <div class="p-section-body">
                <form method="POST" action="{{ route('admin.profile.update-info') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-4">
                        <div class="col-md-6 form-group">
                            <label>Full Name <span style="color:var(--red);">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name',$user->name) }}" required maxlength="100" />
                            @error('name')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control"
                                   value="{{ old('department',$user->department) }}" maxlength="150"
                                   placeholder="e.g. Graphics Team" />
                        </div>
                        <div class="col-12 form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="3" maxlength="500"
                                      placeholder="Write something about yourself…">{{ old('bio',$user->bio) }}</textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Profile Photo</label>
                            <input type="file" name="avatar" class="form-control"
                                   accept="image/jpeg,image/png,image/gif,image/webp" style="padding:0.65rem 1rem;" />
                            <div style="font-size:0.75rem; color:var(--text-3); margin-top:0.4rem;">JPG, PNG, WebP. Max 2 MB.</div>
                            @if($user->avatar)
                            <div style="margin-top:0.6rem;">
                                <label style="font-weight:500; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.4rem; color:var(--red); cursor:pointer;">
                                    <input type="checkbox" name="remove_avatar" value="1" style="cursor:pointer;" />
                                    Remove current photo
                                </label>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn-cms btn-cms-primary">
                            Save Info
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Email --}}
        <div class="p-section" x-data="{show:false}">
            <div class="p-section-head">
                <span class="p-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Change Email</span>
            </div>
            <div class="p-section-body">
                <form method="POST" action="{{ route('admin.profile.update-email') }}">
                    @csrf @method('PUT')
                    <div class="row g-4">
                        <div class="col-md-6 form-group">
                            <label>New Email Address <span style="color:var(--red);">*</span></label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email',$user->email) }}" required maxlength="150" />
                            @error('email')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Current Password <span style="color:var(--red);">*</span></label>
                            <div class="input-group">
                                <input :type="show?'text':'password'" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       required autocomplete="current-password" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                                <button type="button" class="btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" @click="show=!show">
                                    <span x-show="!show"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <span x-show="show" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                </button>
                            </div>
                            @error('current_password')
                            <div class="text-danger mt-1" style="font-size:0.85rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn-cms btn-cms-primary">
                            Update Email
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="p-section" x-data="{showC:false,showN:false,strength:0}">
            <div class="p-section-head">
                <span class="p-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Change Password</span>
            </div>
            <div class="p-section-body">
                <form method="POST" action="{{ route('admin.profile.update-password') }}">
                    @csrf @method('PUT')
                    <div class="row g-4">
                        <div class="col-md-4 form-group">
                            <label>Current Password <span style="color:var(--red);">*</span></label>
                            <div class="input-group">
                                <input :type="showC?'text':'password'" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       required autocomplete="current-password" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                                <button type="button" class="btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" @click="showC=!showC">
                                    <span x-show="!showC"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <span x-show="showC" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                </button>
                            </div>
                            @error('current_password')
                            <div class="text-danger mt-1" style="font-size:0.85rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>New Password <span style="color:var(--red);">*</span></label>
                            <div class="input-group">
                                <input :type="showN?'text':'password'" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required minlength="10" autocomplete="new-password"
                                       x-on:input="strength=pwStrength($event.target.value)" style="border-top-right-radius:0; border-bottom-right-radius:0; border-right:0;" />
                                <button type="button" class="btn" style="background:var(--surface); border:1px solid var(--border); color:var(--text-3); border-top-right-radius:8px; border-bottom-right-radius:8px;" @click="showN=!showN">
                                    <span x-show="!showN"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <span x-show="showN" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                </button>
                            </div>
                            <div class="pw-bar-wrap">
                                <div class="pw-bar"
                                     :style="`width:${strength*25}%;background:${['var(--red)','#f97316','var(--amber)','var(--green)'][strength-1]||'var(--surface-3)'}`">
                                </div>
                            </div>
                            <div style="font-size:0.75rem;margin-top:0.4rem;font-weight:600;"
                                 :style="`color:${['var(--red)','#f97316','var(--amber)','var(--green)'][strength-1]||'var(--text-3)'}`"
                                 x-text="['','Weak','Fair','Good','Strong'][strength]||'Enter password'">
                            </div>
                            @error('password')
                            <div class="text-danger mt-1" style="font-size:0.85rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Confirm Password <span style="color:var(--red);">*</span></label>
                            <input type="password" name="password_confirmation"
                                   class="form-control" required autocomplete="new-password" placeholder="Repeat password" />
                        </div>
                    </div>
                    <div style="font-size:0.85rem; color:var(--text-3); margin-top:0.5rem; margin-bottom:1.5rem;">
                        Min 10 characters with uppercase, number, and special character.
                    </div>
                    <div>
                        <button type="submit" class="btn-cms btn-cms-primary">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Login History --}}
        <div class="p-section">
            <div class="p-section-head">
                <span class="p-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:var(--accent);"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Login History</span>
                <a href="{{ route('admin.my-logs') }}" style="font-size:0.85rem; color:var(--accent); text-decoration:none; font-weight:600;">View all activity →</a>
            </div>
            <div style="padding:1.5rem;">
                @forelse($loginHistory as $log)
                <div class="lh-row">
                    <span style="font-size:1.1rem;">{{ $log->action==='login'?'🔑':($log->action==='logout'?'🚪':'⛔') }}</span>
                    <span class="{{ $log->action==='login'?'lh-badge-green':($log->action==='logout'?'lh-badge-gray':'lh-badge-red') }}">
                        {{ $log->action }}
                    </span>
                    <span style="font-family:monospace; font-size:0.8rem; color:var(--text-2); display:inline-block; margin-left:1rem;">{{ $log->ip_address ?? '—' }}</span>
                    @if($log->country)
                    <span style="font-size:0.8rem; color:var(--text-3); flex:1;">{{ $log->country }}{{ $log->city ? ', '.$log->city : '' }}</span>
                    @else
                    <span style="flex:1;"></span>
                    @endif
                    <span style="font-size:0.75rem; color:var(--text-3);" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                        {{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
                @empty
                <div style="color:var(--text-3); font-size:0.9rem; padding:1rem; text-align:center; background:var(--surface-2); border-radius:8px; border:1px solid var(--border);">No login history yet.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="{{ route('admin.page-asset', ['profile/script.js']) }}" defer></script>
@endpush
