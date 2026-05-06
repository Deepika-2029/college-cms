<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<script>
(function(){
  var t = '{{ $cmsAdminTheme ?? "default-dark" }}';
  document.documentElement.setAttribute('data-theme', t);
  document.documentElement.style.colorScheme = (t === 'light-mode') ? 'light' : 'dark';
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — {{ $cmsSiteName ?? 'CMS Admin' }}</title>
<link rel="stylesheet" href="{{ route('admin.serve', 'admin.css') }}?v={{ time() }}">
@stack('styles')
@if(!empty($cmsCustomCss))
<style id="cms-custom-css">{!! $cmsCustomCss !!}</style>
@endif
<script>
window.CMS_CSRF   = '{{ csrf_token() }}';
window.CMS_MEDIA  = { driver: '{{ $cmsMediaDriver ?? "local" }}', cloudinaryReady: {{ ($cmsCloudinaryReady ?? false) ? 'true' : 'false' }} };
window.ADMIN_PREFIX = '{{ env('ADMIN_PREFIX', 'admin') }}';
</script>
</head>
<body class="@yield('body-class')">

@php
  try { $userRole = auth()->user()->role ?? 'admin'; } catch (\Throwable) { $userRole = 'admin'; }
  $isSuperAdmin = $userRole === 'super_admin';
  $isAdmin      = in_array($userRole, ['super_admin','admin']);
  try { $registeredTables = \App\Models\TablesRegistry::all(); } catch (\Throwable) { $registeredTables = collect(); }
  // For admin (non-super): filter to only tables they can view
  if (!$isSuperAdmin && $isAdmin) {
    try {
      $allowedTableNames = \App\Models\TablePermission::where('user_id', auth()->id())
        ->where('can_view', true)->pluck('table_name')->toArray();
      $sidebarTables = $registeredTables->filter(fn($t) => in_array($t->table_name, $allowedTableNames));
    } catch (\Throwable) { $sidebarTables = collect(); }
  } else {
    $sidebarTables = $registeredTables;
  }
@endphp

<div id="sb-overlay" onclick="toggleSidebar()"></div>

{{-- ══════════════ SIDEBAR ══════════════ --}}
<nav id="cms-sidebar">

  {{-- Brand --}}
  <a href="{{ route('admin.dashboard') }}" class="sb-brand">
    <div class="sb-brand-mark">
      <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
        <path d="M10 2C5.58 2 2 5.58 2 10s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8z" stroke="currentColor" stroke-width="1.5"/>
        <path d="M10 6v4l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </div>
    <span class="sb-brand-name">{{ $cmsSiteName ?? 'College CMS' }}</span>
  </a>

  <div id="cms-sidebar-scroll">

    {{-- Role badge --}}
    <div style="padding:.25rem .625rem">
      <span class="role-pill {{ $isSuperAdmin ? 'role-super' : 'role-admin' }}">
        @if($isSuperAdmin)
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Super Admin
        @else
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Admin
        @endif
      </span>
    </div>

    {{-- Main --}}
    <div class="sb-section">
      <div class="sb-section-label">Main</div>
      <a href="{{ route('admin.dashboard') }}" class="nav-lnk {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm0 10a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4zm10-10a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V5zm0 10a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4z"/></svg>
        Dashboard
      </a>
      <a href="{{ route('admin.my-logs') }}" class="nav-lnk {{ request()->routeIs('admin.my-logs') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        My Activity
      </a>
    </div>

    @if($isAdmin && (auth()->user()->hasSystemPermission('database_builder') || auth()->user()->hasSystemPermission('page_builder') || auth()->user()->hasSystemPermission('media')))
    <div class="sb-section">
      <div class="sb-section-label">Content</div>
      @if(auth()->user()->hasSystemPermission('database_builder'))
      <a href="{{ route('admin.database.index') }}" class="nav-lnk {{ request()->routeIs('admin.database.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="9" ry="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 5v14c0 1.657 4.03 3 9 3s9-1.343 9-3V5" stroke="currentColor" stroke-width="1.5"/><path d="M3 12c0 1.657 4.03 3 9 3s9-1.343 9-3" stroke="currentColor" stroke-width="1.5"/></svg>
        Database Builder
      </a>
      @endif
      @if(auth()->user()->hasSystemPermission('page_builder'))
      <a href="{{ route('admin.vbuilder3.pages') }}" class="nav-lnk {{ request()->routeIs('admin.vbuilder3.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        Page Builder
      </a>

      @endif
      @if(auth()->user()->hasSystemPermission('media'))
      <a href="{{ route('admin.media.index') }}" class="nav-lnk {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Media
      </a>
      @endif
    </div>
    @endif

    @if(auth()->user()->hasSystemPermission('api_keys') || auth()->user()->hasSystemPermission('settings') || auth()->user()->hasSystemPermission('tools'))
    <div class="sb-section">
      <div class="sb-section-label">System</div>

      @if(auth()->user()->hasSystemPermission('api_keys'))
      <a href="{{ route('admin.api-keys.index') }}" class="nav-lnk {{ request()->routeIs('admin.api-keys.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        API Keys
      </a>
      @endif

      @if(auth()->user()->hasSystemPermission('settings'))
      <a href="{{ route('admin.site-identity.index') }}" class="nav-lnk {{ request()->routeIs('admin.site-identity.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.38 8.38 0 0113 0"/><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" opacity=".3"/></svg>
        Site Identity
      </a>
      <a href="{{ route('admin.navigation.index') }}" class="nav-lnk {{ request()->routeIs('admin.navigation.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        Navigation
      </a>
      <a href="{{ route('admin.settings.index') }}" class="nav-lnk {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
        Settings
      </a>
      @endif

      @if(auth()->user()->hasSystemPermission('tools'))
      <a href="{{ route('admin.tools.index') }}" class="nav-lnk {{ request()->routeIs('admin.tools.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Tools
      </a>
      @endif

    </div>
    @endif

    @if($isAdmin && (auth()->user()->hasSystemPermission('user_management') || auth()->user()->hasSystemPermission('advanced_users') || auth()->user()->hasSystemPermission('audit_logs')))
    <div class="sb-section">
      <div class="sb-section-label">People</div>
      @if(auth()->user()->hasSystemPermission('user_management'))
      <a href="{{ route('admin.users.index') }}" class="nav-lnk {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Users
      </a>
      @endif
      @if(auth()->user()->hasSystemPermission('advanced_users'))
      <a href="{{ route('admin.permissions.index') }}" class="nav-lnk {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.114 2.007-.327 2.95m5.772 4.41l.03-.047C20.472 16.634 21 14.887 21 13M15 11c0-1.874-.707-3.585-1.87-4.88" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Permissions
      </a>
      @endif
      @if(auth()->user()->hasSystemPermission('audit_logs'))
      @php try{ $suspCount=\App\Models\AuditLog::where('is_suspicious',true)->whereDate('created_at',today())->count(); }catch(\Throwable){ $suspCount=0; } @endphp
      <a href="{{ route('admin.audit.index') }}" class="nav-lnk {{ request()->routeIs('admin.audit.index') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Audit Logs
        @if($suspCount > 0)<span class="sb-badge">{{ $suspCount }}</span>@endif
      </a>
      @endif
    </div>
    @endif

    @if($isAdmin && $sidebarTables->count())
    <div class="sb-section">
      <div class="sb-section-label">Content Management</div>
      @foreach($sidebarTables as $rt)
      @php
        $tPerm = !$isSuperAdmin
          ? \App\Models\TablePermission::where('user_id', auth()->id())->where('table_name', $rt->table_name)->first()
          : null;
      @endphp
      <a href="{{ route('admin.crud.index', $rt->table_name) }}" class="nav-lnk {{ request()->route('table') === $rt->table_name ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        {{ ucfirst(str_replace('_', ' ', $rt->table_name)) }}
        @if(!$isSuperAdmin && $tPerm)
          @if(!$tPerm->can_create && !$tPerm->can_edit && !$tPerm->can_delete)
          <span style="margin-left:auto;font-size:.58rem;opacity:.5;font-weight:600">VIEW</span>
          @endif
        @endif
      </a>
      @endforeach
    </div>
    @endif

    {{-- Profile link --}}
    @php $pUser = auth()->user(); $avUrl = $pUser?->avatarUrl(); @endphp
    <a href="{{ route('admin.profile.show') }}" class="sb-profile {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
      @if($avUrl)
        <img src="{{ $avUrl }}" alt="" class="sb-avatar-img">
      @else
        <div class="sb-avatar-init">{{ strtoupper(substr($pUser?->name ?? 'A', 0, 1)) }}</div>
      @endif
      <span class="sb-profile-name">{{ $pUser?->name ?? 'Profile' }}</span>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="margin-left:auto;color:rgba(255,255,255,.3)"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
    </a>

  </div>{{-- /scroll --}}

  <div class="sb-footer">
    <div class="sb-footer-name">{{ auth()->user()->name ?? 'Admin' }}</div>
    <div class="sb-footer-email">{{ auth()->user()->email ?? '' }}</div>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="margin-top:.5rem">
      @csrf
      <button type="button" class="sb-logout" onclick="cmsLogout()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sign out
      </button>
    </form>
    <script>
    function cmsLogout() {
      // Refresh CSRF token before submitting to avoid 419 on expired sessions
      fetch('/{{ env("ADMIN_PREFIX", "admin") }}/login', { method: 'GET', credentials: 'same-origin' })
        .then(function() {
          // Grab fresh token from meta tag or window
          var token = document.querySelector('meta[name="csrf-token"]');
          if (token) {
            document.querySelector('#logout-form input[name="_token"]').value = token.getAttribute('content');
          }
          document.getElementById('logout-form').submit();
        })
        .catch(function() {
          // Fallback: just submit anyway
          document.getElementById('logout-form').submit();
        });
    }
    </script>
  </div>

</nav>

{{-- ══════════════ MAIN ══════════════ --}}
<div id="cms-main">
  <header id="cms-topbar">
    <div class="topbar-left">
      <button class="sb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <span class="page-title">@yield('page-title','Dashboard')</span>
    </div>
    <div class="page-actions">
      {{-- Theme toggle --}}
      <div class="theme-palette-wrapper" style="position:relative;">
        <button class="theme-toggle-btn" id="theme-btn" title="Select Theme" onclick="document.getElementById('theme-dropdown').style.display = document.getElementById('theme-dropdown').style.display === 'none' ? 'flex' : 'none';" style="background:transparent; border:none; color:var(--text); cursor:pointer;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 21a9 9 0 0 1-5.65-1.95A6 6 0 0 1 12 11h.65a4.34 4.34 0 0 0 4.19-3.23A4.3 4.3 0 0 0 15 3.32C19.34 4.8 22 9.07 22 14c0 3.87-3.13 7-7 7z"/></svg>
        </button>
        
        <div id="theme-dropdown" style="display:none; position:absolute; right:0; top:36px; background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:8px; box-shadow:var(--shadow-lg); width:180px; z-index:999; flex-direction:column; gap:4px;">
          <button onclick="cmsSetTheme('default-dark')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:#0c0e14; border:1px solid rgba(255,255,255,0.1);"></span> Modern Dark</button>
          <button onclick="cmsSetTheme('light-mode')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:var(--text); border:1px solid #cbd5e1;"></span> Clean Light</button>
          <button onclick="cmsSetTheme('midnight-blue')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:#070a13; border:1px solid rgba(255,255,255,0.1);"></span> Midnight Blue</button>
          <button onclick="cmsSetTheme('forest-green')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:#0c1310; border:1px solid rgba(255,255,255,0.1);"></span> Forest Green</button>
          <button onclick="cmsSetTheme('cyberpunk')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:#0a0514; border:1px solid rgba(255,255,255,0.1);"></span> Cyberpunk Neon</button>
          <button onclick="cmsSetTheme('neon-grass')" style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; background:none; border:none; padding:8px; color:var(--text); border-radius:6px; cursor:pointer;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'"><span style="width:16px; height:16px; border-radius:4px; background:#fafefa; border:1px solid #dcf2d9;"></span> Neon Grass</button>
        </div>
      </div>
      <button onclick="openCmd()" title="Quick search — Ctrl+K" class="cmd-trigger">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        <kbd>Ctrl K</kbd>
      </button>
      <a href="/" target="_blank" rel="noopener" class="btn-cms btn-cms-secondary btn-cms-sm hide-mobile">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        View Site
      </a>
      

      @yield('topbar-actions')
    </div>
  </header>

  <div id="cms-content">
    @if(session('new_key'))
    {{-- API Key alert: persistent, must be copyable. Cannot be a toast. --}}
    <div class="cms-alert alert-warning" style="flex-direction:column;gap:.5rem;align-items:flex-start;">
      <div style="font-weight:700;display:flex;align-items:center;gap:.4rem;color:var(--amber)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        API Key Created — Copy It Now
      </div>
      <code class="key-display">{{ session('new_key') }}</code>
      <div style="font-size:.78rem;opacity:.75">This key will <strong>NOT</strong> be shown again.</div>
      <button onclick="this.closest('.cms-alert').remove()" class="btn-cms btn-cms-secondary btn-cms-sm" style="margin-top:.3rem">Dismiss</button>
    </div>
    @endif
    @if($errors->any())
    {{-- Validation errors: inline so user sees them near the form --}}
    <div class="cms-alert alert-error" id="validation-errors">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <div style="flex:1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
      <button onclick="this.closest('.cms-alert').remove()" class="alert-close" aria-label="Close">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </button>
    </div>
    @endif
    {{-- Flash toasts fired via JS after DOM ready --}}
    @if(session('success') || session('error') || session('warning') || session('info'))
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      @if(session('success'))
        cmsToast(@json(session('success')), 'success', null, 5000);
      @endif
      @if(session('error'))
        cmsToast(@json(session('error')), 'error', null, 7000);
      @endif
      @if(session('warning'))
        cmsToast(@json(session('warning')), 'warning', null, 6000);
      @endif
      @if(session('info'))
        cmsToast(@json(session('info')), 'info', null, 5000);
      @endif
    });
    </script>
    @endif
    @yield('content')
  </div>
</div>

{{-- Command Palette --}}
<div id="cmd-backdrop" onclick="if(event.target===this)closeCmd()">
  <div id="cmd-box">
    <input id="cmd-input" type="text" placeholder="Search pages, settings, users…" autocomplete="off">
    <div id="cmd-results"></div>
    <div class="cmd-footer">
      <span><kbd>↑ ↓</kbd> navigate</span>
      <span><kbd>Enter</kbd> open</span>
      <span><kbd>Esc</kbd> close</span>
    </div>
  </div>
</div>


{{-- Builder Rules Modal --}}
<div id="cms-rules-modal" class="dlg-backdrop" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="dlg-box" style="width:90%; max-width:650px; text-align:left; max-height:80vh; overflow-y:auto; position:relative;">
    <button onclick="document.getElementById('cms-rules-modal').classList.remove('open')" style="position:absolute; right:15px; top:15px; background:transparent; border:none; cursor:pointer; color:var(--text-3);">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem;">Visual Builder Guidelines</h3>
    
    <div style="font-size:0.9rem; color:var(--text-3); margin-bottom:15px;">
      The Visual Builder provides a "Figma-like" experience. By default, standard HTML tags are automatically detected, but you can explicitly use data attributes for full control.
    </div>

    <div style="background:var(--surface-2); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid var(--border);">
      <code style="color:var(--accent); font-weight:bold; font-size:13px;">data-cms-drop="1"</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Makes this element a <b>Drop Zone</b>. Required if you want to drag components inside it. <i>(Note: structural tags like div, section, main, aside, header, footer are automatically treated as drop zones).</i></p>
    </div>

    <div style="background:var(--surface-2); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid var(--border);">
      <code style="color:var(--accent); font-weight:bold; font-size:13px;">data-cms-el="unique_id"</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Marks an element as <b>stylable</b> and <b>selectable</b>. The builder auto-generates these for supported tags if omitted.</p>
    </div>

    <div style="background:var(--surface-2); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid var(--border);">
      <code style="color:var(--accent); font-weight:bold; font-size:13px;">data-cms-type="image | link"</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Changes the right inspector panel options. <code>&lt;img&gt;</code> and <code>&lt;a&gt;</code> tags receive these automatically.</p>
    </div>

    <h4 style="margin:20px 0 10px;">Data Binding & Loops</h4>

    <div style="background:rgba(59,130,246,0.1); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid rgba(59,130,246,0.2);">
      <code style="color:var(--text); font-weight:bold; font-size:13px;">data-source="table_name"</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Fetches records from a registered database table.</p>
    </div>

    <div style="background:rgba(59,130,246,0.1); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid rgba(59,130,246,0.2);">
      <code style="color:var(--text); font-weight:bold; font-size:13px;">data-field="column_name"</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Replaces the inner text or `src` of this element with the database column value.</p>
    </div>

    <div style="background:rgba(59,130,246,0.1); padding:15px; border-radius:8px; margin-bottom:12px; border:1px solid rgba(59,130,246,0.2);">
      <code style="color:var(--text); font-weight:bold; font-size:13px;">data-repeat</code>
      <p style="font-size:12px; margin-top:5px; margin-bottom:0; color:var(--text-3);">Duplicates this specific element for every database record found.</p>
    </div>
  </div>
</div>

<style>
  .dlg-backdrop { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(4px); }
  .dlg-backdrop.open { display:flex; }
  .dlg-box { background:var(--surface); border:1px solid var(--border); padding:24px; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,0.4); }
</style>

{{-- Confirm Dialog --}}
<div id="dlg-backdrop">
  <div id="dlg-box">
    <h6 id="dlg-title"></h6>
    <p id="dlg-msg"></p>
    <div class="dlg-actions">
      <button onclick="dlgCancel()" class="btn-cms btn-cms-secondary">Cancel</button>
      <button id="dlg-confirm-btn" class="btn-cms btn-cms-danger">Delete</button>
    </div>
  </div>
</div>

{{-- Toast container --}}
<div id="cms-toasts"></div>

@include('admin.partials.media-picker')

@stack('scripts')
<script defer src="/admin-vendor/alpine/alpine.min.js"></script>
<script src="{{ route('admin.serve', 'admin.js') }}" defer></script>
<script>
// Theme Palette Integration
function cmsSetTheme(themeName) {
  document.getElementById('theme-dropdown').style.display = 'none';
  var root = document.documentElement;
  root.setAttribute('data-theme', themeName);
  root.style.colorScheme = (themeName === 'light-mode') ? 'light' : 'dark';
  
  // Persist back to server settings dynamically
  fetch('{{ route('admin.settings.theme') }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CMS_CSRF },
    body: JSON.stringify({ theme: themeName })
  }).catch(e => console.error("Theme save failed:", e));
}

// Close dropdown if clicked outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.theme-palette-wrapper')) {
    var d = document.getElementById('theme-dropdown');
    if(d) d.style.display = 'none';
  }
});

/* ══════════════════════════════════════════════════════
   CSRF AUTO-REFRESH — prevents 419 Page Expired errors
   ══════════════════════════════════════════════════════ */
(function () {
  const CSRF_ENDPOINT = '/' + window.ADMIN_PREFIX + '/csrf-token';
  const REFRESH_EVERY = 20 * 60 * 1000; // 20 minutes

  /** Fetch a fresh CSRF token and update all references in the DOM */
  async function refreshCsrf() {
    try {
      const res = await window.__origFetch(CSRF_ENDPOINT, { credentials: 'same-origin' });
      if (!res.ok) return null;
      const { token } = await res.json();
      if (!token) return null;

      // Update the global JS variable
      window.CMS_CSRF = token;

      // Update <meta name="csrf-token">
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta) meta.setAttribute('content', token);

      // Update every hidden CSRF input on the page
      document.querySelectorAll('input[name="_token"]').forEach(el => { el.value = token; });

      return token;
    } catch (e) {
      return null;
    }
  }

  // ── Proactive: refresh every 20 minutes so the token never goes stale ──
  setInterval(refreshCsrf, REFRESH_EVERY);

  // ── Reactive: patch window.fetch to intercept 419 responses ──
  window.__origFetch = window.__origFetch || window.fetch.bind(window);

  window.fetch = async function (input, init = {}) {
    // Make sure every fetch carries the latest CSRF token in headers
    const headers = new Headers(init.headers || {});
    if (!headers.has('X-CSRF-TOKEN') && window.CMS_CSRF) {
      headers.set('X-CSRF-TOKEN', window.CMS_CSRF);
    }
    init = { ...init, headers };

    let response = await window.__origFetch(input, init);

    // 419 = CSRF token mismatch → refresh token and retry ONCE
    if (response.status === 419) {
      const freshToken = await refreshCsrf();
      if (freshToken) {
        const retryHeaders = new Headers(init.headers || {});
        retryHeaders.set('X-CSRF-TOKEN', freshToken);
        // Also fix _token in FormData body if present
        let retryBody = init.body;
        if (retryBody instanceof FormData && retryBody.has('_token')) {
          retryBody = new FormData();
          for (const [k, v] of init.body.entries()) {
            retryBody.append(k, k === '_token' ? freshToken : v);
          }
        }
        response = await window.__origFetch(input, { ...init, headers: retryHeaders, body: retryBody });
      }
    }

    return response;
  };

  // ── Reactive: intercept <form> submits and fix stale _token fields ──
  document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    const tokenInput = form.querySelector('input[name="_token"]');
    if (!tokenInput) return;

    // If the token in the form already matches what we have, do nothing
    if (tokenInput.value === window.CMS_CSRF) return;

    // Token mismatch — refresh before submit
    e.preventDefault();
    const freshToken = await refreshCsrf();
    if (freshToken) {
      tokenInput.value = freshToken;
    }
    form.submit();
  }, true); // capture phase so we run before other submit listeners

})();

</script>
@if(!empty($cmsCustomJs))
<script>{!! $cmsCustomJs !!}</script>
@endif
</body>
</html>
