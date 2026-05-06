@extends('admin.layout')

@section('title', 'Site Identity')
@section('page-title', 'Site Identity')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.page-asset', ['site-identity/style.css']) }}">
@endpush

@section('content')
<form action="{{ route('admin.site-identity.save') }}" method="POST" id="site-identity-form">
@csrf

<div class="identity-grid">

    {{-- ── Sticky Sidebar Nav ──────────────────────────────────────────── --}}
    <nav class="identity-nav">
        <a href="#site" class="active">🏫 Site Info</a>
        <a href="#seo">🔍 SEO &amp; Browser</a>
        <a href="#branding">🎨 Branding</a>
        <a href="#social">🔗 Social Media</a>
        <a href="#custom-tokens">➕ Custom Tokens</a>
        <a href="#tokens">🌐 Global Tokens</a>
    </nav>

    {{-- ── Panels ──────────────────────────────────────────────────────── --}}
    <div>

        {{-- ── Site Info ───────────────────────────────────────────────── --}}
        <div class="section-card" id="site">
            <div class="section-header">
                <div class="section-icon">🏫</div>
                <div>
                    <h2>Site Information</h2>
                    <p>Basic details about your college — used by page builder tokens and the admin panel.</p>
                </div>
            </div>
            <div class="section-body">

                <div class="field-row">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">College Name</label>
                        <input type="text" name="college_name" class="form-control"
                               value="{{ $settings['college_name'] ?? '' }}"
                               placeholder="Government Polytechnic Nainital">
                        <div class="form-hint">Token: <code>[[college_name]]</code></div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control"
                               value="{{ $settings['site_name'] ?? '' }}"
                               placeholder="GP Nainital Portal">
                        <div class="form-hint">Token: <code>[[site_name]]</code></div>
                    </div>
                </div>

                <div class="field-row" style="margin-top:1.25rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="site_tagline" class="form-control"
                               value="{{ $settings['site_tagline'] ?? '' }}"
                               placeholder="Inspiring Minds. Building Futures.">
                        <div class="form-hint">Token: <code>[[site_tagline]]</code></div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="site_email" class="form-control"
                               value="{{ $settings['site_email'] ?? '' }}"
                               placeholder="info@college.edu">
                        <div class="form-hint">Token: <code>[[site_email]]</code></div>
                    </div>
                </div>

                <div class="field-row" style="margin-top:1.25rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Phone / Contact</label>
                        <input type="text" name="site_phone" class="form-control"
                               value="{{ $settings['site_phone'] ?? '' }}"
                               placeholder="+91 98765 43210">
                        <div class="form-hint">Token: <code>[[site_phone]]</code></div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Homepage Slug</label>
                        <input type="text" name="homepage_slug" class="form-control"
                               value="{{ $settings['homepage_slug'] ?? 'home' }}"
                               placeholder="home"
                               pattern="[a-z0-9\-]+"
                               title="Lowercase letters, numbers and hyphens only">
                        <div class="form-hint">The page slug that loads at <code>/</code> — default is <code>home</code>.</div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1.25rem;">
                    <label class="form-label">Address</label>
                    <textarea name="site_address" class="form-control" rows="2"
                              placeholder="123 College Road, City, State">{{ $settings['site_address'] ?? '' }}</textarea>
                    <div class="form-hint">Token: <code>[[site_address]]</code></div>
                </div>

            </div>
        </div>

        {{-- ── SEO / Browser Title ──────────────────────────────────────── --}}
        <div class="section-card" id="seo">
            <div class="section-header">
                <div class="section-icon">🔍</div>
                <div>
                    <h2>SEO &amp; Browser Title</h2>
                    <p>This title appears in Google search results and the browser tab for every page on your website.</p>
                </div>
            </div>
            <div class="section-body">

                <div class="form-group">
                    <label class="form-label" style="font-weight:700;">Site Browser Title</label>
                    <input type="text" name="site_browser_title" class="form-control"
                           value="{{ $settings['site_browser_title'] ?? '' }}"
                           placeholder="Government Polytechnic Nainital"
                           style="font-size:1rem;font-weight:600;">
                    <div class="form-hint" style="margin-top:.5rem;">
                        This is appended to every page title in the browser tab and Google: <br>
                        <code style="background:rgba(0,0,0,.07);padding:2px 6px;border-radius:4px;">Home Page Title — Government Polytechnic Nainital</code><br><br>
                        ⚠️ <strong>After saving, re-publish all pages from the Visual Builder for Google to pick up the new title.</strong>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1.25rem;">
                    <label class="form-label">Default Meta Description</label>
                    <textarea name="site_meta_description" class="form-control" rows="3"
                              placeholder="One of Uttarakhand's premier Polytechnic Colleges, committed to producing industry-ready engineers...">{{ $settings['site_meta_description'] ?? '' }}</textarea>
                    <div class="form-hint">Used as the description shown in Google when a page has no custom description. Token: <code>[[site_meta_description]]</code></div>
                </div>

            </div>
        </div>

        {{-- ── Branding ─────────────────────────────────────────────────── --}}
        <div class="section-card" id="branding">
            <div class="section-header">
                <div class="section-icon">🎨</div>
                <div>
                    <h2>Branding</h2>
                    <p>College logo, affiliated university logo, and favicon — all managed via the media library.</p>
                </div>
            </div>
            <div class="section-body">
                <div class="branding-row">

                    {{-- College Logo --}}
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:700;">College Logo</label>
                        <input type="hidden" name="site_logo" id="logo-input" value="{{ $settings['site_logo'] ?? '' }}">
                        @if(!empty($settings['site_logo']))
                            <img id="logo-preview"
                                 src="{{ str_starts_with($settings['site_logo'],'http') ? $settings['site_logo'] : asset(ltrim($settings['site_logo'],'/')) }}"
                                 alt="College Logo"
                                 style="height:60px;max-width:180px;object-fit:contain;border-radius:var(--r);border:1px solid var(--border);background:var(--surface-3);padding:.25rem;margin-bottom:.75rem;">
                        @else
                            <div id="logo-preview-empty" class="logo-placeholder">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        @endif
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="pickMediaSetting('logo')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                {{ !empty($settings['site_logo']) ? 'Change' : 'Select Logo' }}
                            </button>
                            @if(!empty($settings['site_logo']))
                            <button type="button" class="btn-cms btn-cms-danger btn-cms-sm" onclick="clearMediaSetting('logo')">Remove</button>
                            @endif
                        </div>
                        <div class="form-hint" style="margin-top:.6rem;">PNG/SVG, ~300×80px &bull; Token: <code>[[site_logo]]</code></div>
                    </div>

                    {{-- University Affiliation Logo --}}
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:700;">University Affiliation Logo</label>
                        <input type="hidden" name="university_logo" id="university-logo-input" value="{{ $settings['university_logo'] ?? '' }}">
                        @if(!empty($settings['university_logo']))
                            <img id="university-logo-preview"
                                 src="{{ str_starts_with($settings['university_logo'],'http') ? $settings['university_logo'] : asset(ltrim($settings['university_logo'],'/')) }}"
                                 alt="University Logo"
                                 style="height:60px;max-width:180px;object-fit:contain;border-radius:var(--r);border:1px solid var(--border);background:var(--surface-3);padding:.25rem;margin-bottom:.75rem;">
                        @else
                            <div id="university-logo-preview-empty" class="logo-placeholder">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                        @endif
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="pickMediaSetting('university-logo')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                {{ !empty($settings['university_logo']) ? 'Change' : 'Select Logo' }}
                            </button>
                            @if(!empty($settings['university_logo']))
                            <button type="button" class="btn-cms btn-cms-danger btn-cms-sm" onclick="clearMediaSetting('university-logo')">Remove</button>
                            @endif
                        </div>
                        <div class="form-hint" style="margin-top:.6rem;">PNG/SVG, ~200×70px &bull; Token: <code>[[university_logo]]</code></div>
                    </div>

                    {{-- Favicon --}}
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:700;">Favicon</label>
                        <input type="hidden" name="site_favicon" id="favicon-input" value="{{ $settings['site_favicon'] ?? '' }}">
                        @if(!empty($settings['site_favicon']))
                            <img id="favicon-preview"
                                 src="{{ str_starts_with($settings['site_favicon'],'http') ? $settings['site_favicon'] : asset(ltrim($settings['site_favicon'],'/')) }}"
                                 alt="Favicon"
                                 style="width:60px;height:60px;object-fit:contain;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface-3);padding:.25rem;margin-bottom:.75rem;">
                        @else
                            <div id="favicon-preview-empty" class="favicon-placeholder">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            </div>
                        @endif
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="pickMediaSetting('favicon')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                {{ !empty($settings['site_favicon']) ? 'Change' : 'Select' }}
                            </button>
                            @if(!empty($settings['site_favicon']))
                            <button type="button" class="btn-cms btn-cms-danger btn-cms-sm" onclick="clearMediaSetting('favicon')">Remove</button>
                            @endif
                        </div>
                        <div class="form-hint" style="margin-top:.6rem;">ICO/PNG, 32×32px min</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Social Media ─────────────────────────────────────────────── --}}
        <div class="section-card" id="social">
            <div class="section-header">
                <div class="section-icon">🔗</div>
                <div>
                    <h2>Social Media</h2>
                    <p>Links used in navigation and page footers. Provide full, valid URLs.</p>
                </div>
            </div>
            <div class="section-body">
                <div class="field-row">
                    @foreach([
                        ['name'=>'social_facebook',  'label'=>'Facebook',  'placeholder'=>'https://facebook.com/yourcollege',   'icon'=>'<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>'],
                        ['name'=>'social_twitter',   'label'=>'Twitter/X', 'placeholder'=>'https://twitter.com/yourcollege',    'icon'=>'<path d="M22 4.01c-1 .49-1.98.689-3 .99-1.121-1.265-2.783-1.335-4.38-.737S11.977 6.323 12 8v1c-3.245.083-6.135-1.395-8-4 0 0-4.182 7.433 4 11-1.872 1.247-3.739 2.088-6 2 3.308 1.803 6.913 2.423 10.034 1.517 3.58-1.04 6.522-3.723 7.651-7.742a13.84 13.84 0 00.497-3.753C20.18 7.773 21.692 5.25 22 4.009z"/>'],
                        ['name'=>'social_instagram', 'label'=>'Instagram', 'placeholder'=>'https://instagram.com/yourcollege',  'icon'=>'<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
                        ['name'=>'social_youtube',   'label'=>'YouTube',   'placeholder'=>'https://youtube.com/@yourcollege',   'icon'=>'<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>'],
                        ['name'=>'social_linkedin',  'label'=>'LinkedIn',  'placeholder'=>'https://linkedin.com/company/yourcollege','icon'=>'<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'],
                        ['name'=>'social_whatsapp',  'label'=>'WhatsApp',  'placeholder'=>'+91 9876543210',                     'icon'=>'<path d="M21 11.5a8.38 8.38 0 01-1 4.2C19.3 17 17 18 14.5 18H10z"/><circle cx="12" cy="12" r="10"/><path d="M16.5 16.5l-3.5-3.5"/>'],
                        ['name'=>'social_telegram',  'label'=>'Telegram',  'placeholder'=>'https://t.me/yourcollege',           'icon'=>'<polygon points="22 2 15 22 11 13 2 9 22 2"/><line x1="22" y1="2" x2="11" y2="13"/>'],
                    ] as $s)
                    <div class="form-group" style="margin:0;">
                        <label class="form-label social-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $s['icon'] !!}</svg>
                            {{ $s['label'] }}
                        </label>
                        <input type="url" name="{{ $s['name'] }}" class="form-control"
                               value="{{ $settings[$s['name']] ?? '' }}"
                               placeholder="{{ $s['placeholder'] }}">
                        <div class="form-hint">Token: <code>[[{{ $s['name'] }}]]</code></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>



        {{-- ── Custom Global Tokens ───────────────────────────────────────── --}}
        <div class="section-card" id="custom-tokens">
            <div class="section-header">
                <div class="section-icon">➕</div>
                <div>
                    <h2>Custom Tokens</h2>
                    <p>Create your own reusable variables (e.g. `admission_fee`, `principal_name`) that can be placed anywhere using `[[token_name]]`.</p>
                </div>
            </div>
            <div class="section-body">
                <div id="custom-tokens-wrapper">
                    @forelse($customTokens as $key => $val)
                        <div class="field-row token-item" style="align-items:flex-start; margin-bottom:10px;">
                            <div class="form-group" style="margin:0; flex:1;">
                                <input type="text" name="custom_token_keys[]" class="form-control token-key" value="{{ $key }}" placeholder="token_name" pattern="[a-zA-Z0-9_]+" title="Only alphanumeric and underscores allowed" readonly>
                            </div>
                            <div class="form-group" style="margin:0; flex:2;">
                                <input type="text" name="custom_token_values[]" class="form-control token-val" value="{{ $val }}" placeholder="Value to replace" readonly>
                            </div>
                            <div style="display:flex; gap:0.25rem;">
                                <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm" onclick="unlockToken(this)" title="Edit Custom Token">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                </button>
                                <button type="button" class="btn-cms btn-cms-danger btn-cms-sm" onclick="this.closest('.token-item').remove()" title="Remove Custom Token">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        {{-- No custom tokens yet --}}
                    @endforelse
                </div>
                <button type="button" class="btn-cms btn-cms-secondary btn-cms-sm mt-2" onclick="addCustomToken()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Custom Token
                </button>
            </div>
        </div>

        {{-- ── Global Tokens Reference ──────────────────────────────────── --}}
        <div class="section-card" id="tokens">
            <div class="section-header">
                <div class="section-icon">🌐</div>
                <div>
                    <h2>Global Tokens</h2>
                    <p>Paste any token into a page template in the Visual Builder. It updates live across the whole site when you save here — no page rebuild needed.</p>
                </div>
            </div>
            <div class="section-body">

                @if(!empty($globalStrings))
                    <p style="font-size:.8rem;color:var(--text-3);margin-bottom:.75rem;">
                        Click a token to copy it. Paste it directly into any HTML field in the Visual Builder.
                    </p>
                    <div class="token-grid">
                        @foreach($globalStrings as $key => $value)
                        <div class="token-card">
                            <span class="token-tag" onclick="copyToken('{{ $key }}')" title="Click to copy [[{{ $key }}]]">[[{{ $key }}]]</span>
                            <span class="token-value" title="{{ $value }}">{{ $value ?: '(empty)' }}</span>
                            <button type="button" class="token-copy-btn" onclick="copyToken('{{ $key }}')" title="Copy token">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="token-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.3;margin-bottom:.75rem;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        <p>No tokens yet. Fill in the fields above and click <strong>Save</strong> to generate your tokens.</p>
                    </div>
                @endif

            </div>
        </div>

        {{-- ── Save Bar ─────────────────────────────────────────────────── --}}
        <div class="identity-save-bar">
            <button type="submit" class="btn-cms btn-cms-primary btn-cms-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Save Site Identity
            </button>
            <a href="{{ route('admin.dashboard') }}" class="btn-cms btn-cms-secondary btn-cms-lg">Discard Changes</a>
            <span style="margin-left:auto;font-size:.8rem;color:var(--text-3);">
                Changes reflect on the website <strong>instantly</strong> — no page rebuild required.
            </span>
        </div>

    </div>
</div>
</form>

@endsection

@push('scripts')
<script>
function unlockToken(btn) {
    const row = btn.closest('.token-item');
    const keyInput = row.querySelector('.token-key');
    const valInput = row.querySelector('.token-val');
    keyInput.removeAttribute('readonly');
    valInput.removeAttribute('readonly');
    keyInput.focus();
    // Optional: Hide edit button after unlocking
    btn.style.display = 'none';
}

function addCustomToken() {
    const wrapper = document.getElementById('custom-tokens-wrapper');
    const div = document.createElement('div');
    div.className = 'field-row token-item';
    div.style.cssText = 'align-items:flex-start; margin-bottom:10px;';
    div.innerHTML = `
        <div class="form-group" style="margin:0; flex:1;">
            <input type="text" name="custom_token_keys[]" class="form-control" value="" placeholder="token_name" pattern="[a-zA-Z0-9_]+" title="Only alphanumeric and underscores allowed">
        </div>
        <div class="form-group" style="margin:0; flex:2;">
            <input type="text" name="custom_token_values[]" class="form-control" value="" placeholder="Value to replace">
        </div>
        <button type="button" class="btn-cms btn-cms-danger btn-cms-sm" onclick="this.closest('.token-item').remove()" title="Remove Custom Token">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </button>
    `;
    wrapper.appendChild(div);
}
</script>
<script src="{{ route('admin.page-asset', ['site-identity/script.js']) }}" defer></script>
@endpush

