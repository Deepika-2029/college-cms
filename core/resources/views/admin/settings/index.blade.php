@extends('admin.layout')

@section('title', 'Settings')
@section('page-title', 'Site Settings')

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['settings/style.css']) }}">
@endpush

@section('content')
<form action="{{ route('admin.settings.save') }}" method="POST" id="settings-form">
@csrf

<div class="settings-grid">

    {{-- Sidebar nav --}}
    <nav class="settings-nav">
        <a href="#media" class="active">💾 Media Storage</a>
        <a href="#general">⚙️ General</a>
    </nav>

    {{-- Panels --}}
    <div>

        {{-- ── Media Storage ──────────────────────────────────────── --}}
        <div class="section-card" id="media">
            <div class="section-header">
                <div class="section-icon">💾</div>
                <div>
                    <h2>Media Storage</h2>
                    <p>Where uploaded files are stored by default. Users can override per-upload.</p>
                </div>
            </div>
            <div class="section-body">

                <div class="form-group">
                    <label style="margin-bottom:0.6rem;">Default Storage Driver</label>
                    <div class="driver-toggle" id="driver-toggle-main">
                        <button type="button" class="driver-opt {{ $settings['media_driver'] === 'local' ? 'active' : '' }}"
                                data-value="local" onclick="selectDriver('local')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            Local Server
                        </button>
                        <button type="button" class="driver-opt cloud {{ $settings['media_driver'] === 'cloudinary' ? 'active' : '' }}"
                                data-value="cloudinary"
                                {{ !$cloudinaryReady ? 'disabled title="Configure Cloudinary credentials below first"' : '' }}
                                onclick="selectDriver('cloudinary')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/></svg>
                            Cloudinary CDN
                        </button>
                    </div>
                    <input type="hidden" name="media_driver" id="media_driver" value="{{ $settings['media_driver'] }}">
                    <div class="form-hint" style="margin-top:0.75rem;">
                        @if($cloudinaryReady)
                            <span class="status-dot green"></span>Cloudinary is configured and ready to use.
                        @else
                            <span class="status-dot grey"></span>Cloudinary not configured — enter credentials below to enable it.
                        @endif
                    </div>
                </div>

                <div class="divider"></div>

                {{-- Cloudinary credentials header --}}
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                    <h3 style="font-size:1rem;font-weight:700;color:var(--text);margin:0;display:flex;align-items:center;gap:0.4rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/></svg> Cloudinary Credentials
                    </h3>
                    <span id="cld-status-badge" class="badge {{ $cloudinaryReady ? 'badge-success' : 'badge-danger' }}">
                        {{ $cloudinaryReady ? '✓ Connected' : 'Not configured' }}
                    </span>
                    <button type="button" id="btn-test-cld" onclick="testCloudinary()" class="btn-cms btn-cms-secondary btn-cms-sm ms-auto">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Test Connection
                    </button>
                </div>

                {{-- Test result box --}}
                <div id="cld-test-result" style="display:none;padding:.875rem 1.25rem;border-radius:var(--r);
                     font-size:.8125rem;margin-bottom:1.25rem;border:1px solid transparent;font-weight:500;"></div>

                <p style="font-size:.8125rem;color:var(--text-3);margin-bottom:1.25rem;line-height:1.5;">
                    Get these from
                    <a href="https://cloudinary.com/console" target="_blank" rel="noopener">
                        cloudinary.com/console
                    </a> → Dashboard. The free tier gives you 25 GB storage and 25 GB bandwidth per month.
                </p>

                {{-- Credentials grid --}}
                <div class="cred-row">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Cloud Name</label>
                        <input type="text" name="cloudinary_cloud_name" id="cld-cloud-name" class="form-control"
                                value="{{ $settings['cloudinary_cloud_name'] }}" placeholder="e.g. my-college">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">API Key</label>
                        <input type="text" name="cloudinary_api_key" id="cld-api-key" class="form-control"
                                value="{{ $settings['cloudinary_api_key'] }}" placeholder="123456789012345">
                    </div>
                </div>
                <div class="cred-row" style="margin-top:1.25rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">API Secret</label>
                        <div class="secret-wrap">
                            <input type="password" name="cloudinary_api_secret" id="api-secret" class="form-control"
                                   value="{{ $settings['cloudinary_api_secret'] ? '••••••••' : '' }}"
                                   placeholder="{{ $settings['cloudinary_api_secret'] ? 'Secret saved — leave blank to keep' : 'Your API secret' }}"
                                   autocomplete="new-password">
                            @if($settings['cloudinary_api_secret'])
                            <div class="form-hint" style="display:flex;align-items:center;gap:0.3rem;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                Secret is saved. Enter a new value only to replace it.
                            </div>
                            @endif
                            <button type="button" class="secret-eye" onclick="toggleSecret()" title="Toggle visibility">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Upload Preset <span class="text-muted">(optional)</span></label>
                        <input type="text" name="cloudinary_upload_preset" class="form-control"
                                value="{{ $settings['cloudinary_upload_preset'] }}" placeholder="unsigned_preset_name">
                        <div class="form-hint">Leave blank to use signed uploads (recommended).</div>
                    </div>
                </div>

                {{-- Folder setting --}}
                <div class="form-group" style="margin-top:1.25rem;">
                    <label class="form-label">Uploads Folder <span class="text-muted">(optional)</span></label>
                    <input type="text" name="cloudinary_folder" class="form-control"
                           value="{{ $settings['cloudinary_folder'] ?? 'college-cms' }}"
                           placeholder="college-cms">
                    <div class="form-hint">
                        Files are uploaded to this Cloudinary folder.
                        Current: <code>{{ $cloudinaryFolder ?? 'college-cms' }}</code>
                    </div>
                </div>

                {{-- What Cloudinary gives you --}}
                <div class="cld-info-box">
                    <strong><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;vertical-align:-2px;margin-right:4px;"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/></svg> With Cloudinary you get:</strong>
                    <div style="margin-top:.6rem;display:grid;grid-template-columns:1fr 1fr;gap:.4rem;">
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Auto image optimization</span>
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Global CDN delivery</span>
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> On-the-fly transformations</span>
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Zero server storage limits</span>
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Auto WebP/AVIF output</span>
                        <span style="display:flex;gap:.3rem;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Centralized asset deletion</span>
                    </div>
                </div>
            </div>
        </div>


        {{-- ── General ─────────────────────────────────────────────── --}}
        <div class="section-card" id="general">
            <div class="section-header">
                <div class="section-icon">⚙️</div>
                <div>
                    <h2>General Preferences</h2>
                    <p>System configuration including timezone and backend appearance.</p>
                </div>
            </div>
            <div class="section-body">
                <div class="cred-row">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">System Timezone</label>
                        <select name="timezone" class="form-control">
                            @foreach(['Asia/Dhaka','Asia/Kolkata','Asia/Karachi','Asia/Dubai','Europe/London','America/New_York','America/Los_Angeles','UTC'] as $tz)
                                <option value="{{ $tz }}" {{ $settings['timezone'] === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Items per Page <span class="text-muted">(Admin data tables)</span></label>
                        <input type="number" name="items_per_page" class="form-control"
                               value="{{ $settings['items_per_page'] }}" min="5" max="100" step="5">
                    </div>
                </div>
                <div class="cred-row" style="margin-top:1.25rem;">
                    <div class="form-group" style="margin:0; width:100%;">
                        <label class="form-label">Admin Dashboard Theme</label>
                        <select name="admin_theme" class="form-control">
                            @php $currentTheme = $settings['admin_theme'] ?? 'default-dark'; @endphp
                            <option value="default-dark" {{ $currentTheme === 'default-dark' ? 'selected' : '' }}>Modern Dark (Default)</option>
                            <option value="light-mode" {{ $currentTheme === 'light-mode' ? 'selected' : '' }}>Clean Light</option>
                            <option value="midnight-blue" {{ $currentTheme === 'midnight-blue' ? 'selected' : '' }}>Midnight Blue</option>
                            <option value="forest-green" {{ $currentTheme === 'forest-green' ? 'selected' : '' }}>Forest Green</option>
                            <option value="cyberpunk" {{ $currentTheme === 'cyberpunk' ? 'selected' : '' }}>Cyberpunk Neon</option>
                            <option value="neon-grass" {{ $currentTheme === 'neon-grass' ? 'selected' : '' }}>Neon Grass</option>
                        </select>
                        <div class="form-hint">Changes the visual style of all administrator pages across the CMS.</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:1rem;margin-bottom:2rem;background:var(--surface);padding:1.5rem;border-radius:var(--r-xl);border:1px solid var(--border);box-shadow:var(--shadow-sm);align-items:center;">
            <button type="submit" class="btn-cms btn-cms-primary btn-cms-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Save All Settings
            </button>
            <a href="{{ route('admin.dashboard') }}" class="btn-cms btn-cms-secondary btn-cms-lg">Discard Changes</a>
        </div>

    </div>
</div>
</form>
@endsection

@push('scripts')
<script src="{{ route('admin.page-asset', ['settings/script.js']) }}" defer></script>
@endpush
