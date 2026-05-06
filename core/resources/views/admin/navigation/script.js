/**
 * Navigation Manager Script — Final Clean Version
 * - Viewport controls: iframe actual width set → CSS media queries fire correctly
 * - Auto-expand iframe height via postMessage from inside the iframe
 * - Tab switching, drag-reorder, add/remove/sub-links/dividers, AJAX save
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── State ────────────────────────────────────────────────────────────
    let navbarMenu = JSON.parse(JSON.stringify(window.INITIAL_NAVBAR || []));
    let footerMenu = JSON.parse(JSON.stringify(window.INITIAL_FOOTER || []));
    let isDirty    = false;
    let currentTab = 'navbar';
    let previewDebounceTimer = null;

    // Debug: log what was loaded from server
    console.log('[Nav] INITIAL_NAVBAR loaded:', navbarMenu.length, 'items', navbarMenu);
    console.log('[Nav] INITIAL_FOOTER loaded:', footerMenu.length, 'items', footerMenu);

    // ── DOM References ───────────────────────────────────────────────
    const contentBox = document.getElementById('nav-preview-content-box');
    const navIframe  = document.getElementById('nav-preview-navbar-iframe');
    const footIframe = document.getElementById('nav-preview-footer-iframe');
    const vpDisplay  = document.getElementById('nav-vp-display');
    const scalerWrap = document.getElementById('nav-preview-scaler-wrap') || document.querySelector('.nav-preview-scaler-wrap');

    // ── Viewport State ───────────────────────────────────────────────────
    // KEY INSIGHT: We set the IFRAME element's actual pixel width, not just
    // the container. CSS media-queries inside the iframe fire based on the
    // iframe's own rendered width, so this is the only way to make the
    // hamburger appear at 375px and the full desktop nav appear at 1280px.
    let currentViewportWidth = null; // null = full panel width

    /** Auto-size iframe height from its inner content */
    function autoSizeIframe(iframe) {
        if (!iframe || !iframe.contentDocument) return;
        try {
            const body = iframe.contentDocument.body;
            if (!body) return;
            const minH = (iframe.id === 'nav-preview-navbar-iframe') ? 600 : 80;
            const h = Math.max(body.scrollHeight, body.offsetHeight, minH);
            iframe.style.height = h + 'px';
            fixScalerHeight(h);
        } catch (e) { /* cross-origin safety */ }
    }

    /** Fix the outer scaler-wrap height — now uses overflow:auto so just leave it at 80vh */
    function fixScalerHeight(iframeH) {
        // scaler-wrap is fixed at 80vh with overflow:auto — no dynamic height needed
        // Only fix the scale transform correction when zoomed out
        if (currentViewportWidth !== null) {
            var available = scalerWrap ? scalerWrap.clientWidth - 24 : 900;
            if (currentViewportWidth > available) {
                // already handled in applyPx
            }
        }
    }

    /** Listen for height reports from inside the iframes */
    window.addEventListener('message', function (e) {
        if (!e.data || e.data.type !== 'nav-preview-height') return;
        const iframe = e.data.src === 'navbar' ? navIframe : footIframe;
        if (!iframe) return;
        // NEVER let navbar iframe shrink below 900px (navbar + hero needs space)
        const minH = (e.data.src === 'navbar') ? 900 : 100;
        const h = Math.max(e.data.height, minH);
        iframe.style.height    = h + 'px';
        iframe.style.minHeight = minH + 'px';
    });

    /** Apply a specific pixel width preset or 'full' */
    window.navSetViewport = function (value) {
        document.querySelectorAll('.nav-vp-preset').forEach(b => b.classList.remove('active'));

        if (value === 'full') {
            currentViewportWidth = null;
            // Reset both iframes to fluid width
            [navIframe, footIframe].forEach(f => {
                if (f) { f.style.width = '100%'; f.style.minWidth = ''; }
            });
            if (contentBox) {
                contentBox.style.width    = '100%';
                contentBox.style.maxWidth = '100%';
                contentBox.style.transform = 'none';
                contentBox.style.transformOrigin = '';
            }
            if (scalerWrap) scalerWrap.style.height = '';
            if (vpDisplay) vpDisplay.textContent = 'Full';
            document.getElementById('vp-full')?.classList.add('active');
            setTimeout(() => autoSizeIframe(currentTab === 'navbar' ? navIframe : footIframe), 100);
        } else {
            const px = parseInt(value);
            currentViewportWidth = px;
            applyPx(px);
            if (px <= 480)       document.getElementById('vp-mobile')?.classList.add('active');
            else if (px <= 820)  document.getElementById('vp-tablet')?.classList.add('active');
            else if (px <= 1300) document.getElementById('vp-laptop')?.classList.add('active');
        }
    };

    function applyPx(px) {
        const available = scalerWrap ? scalerWrap.clientWidth - 24 : 900;
        if (vpDisplay) vpDisplay.textContent = px + 'px';

        // Set iframe to the ACTUAL desired pixel width → CSS breakpoints fire correctly
        [navIframe, footIframe].forEach(f => {
            if (f) { f.style.width = px + 'px'; f.style.minWidth = px + 'px'; }
        });
        if (contentBox) {
            contentBox.style.width    = px + 'px';
            contentBox.style.maxWidth = px + 'px';

            // Scale down if wider than the available panel area
            if (px > available) {
                const scale = available / px;
                contentBox.style.transform       = `scale(${scale})`;
                contentBox.style.transformOrigin = 'top left';
            } else {
                contentBox.style.transform       = 'none';
                contentBox.style.transformOrigin = '';
            }
        }

        // Re-measure height after the iframe relayouts
        setTimeout(() => autoSizeIframe(currentTab === 'navbar' ? navIframe : footIframe), 150);
    }

    // ±50px step buttons
    document.getElementById('vp-dec')?.addEventListener('click', () => {
        if (currentViewportWidth === null) currentViewportWidth = Math.round(scalerWrap.clientWidth - 24);
        currentViewportWidth = Math.max(280, currentViewportWidth - 50);
        document.querySelectorAll('.nav-vp-preset').forEach(b => b.classList.remove('active'));
        applyPx(currentViewportWidth);
    });
    document.getElementById('vp-inc')?.addEventListener('click', () => {
        if (currentViewportWidth === null) currentViewportWidth = Math.round(scalerWrap.clientWidth - 24);
        currentViewportWidth = Math.min(2560, currentViewportWidth + 50);
        document.querySelectorAll('.nav-vp-preset').forEach(b => b.classList.remove('active'));
        applyPx(currentViewportWidth);
    });

    // Recompute scale on window resize
    window.addEventListener('resize', () => {
        if (currentViewportWidth !== null) applyPx(currentViewportWidth);
    });

    // ── Height reporter injected into each iframe ─────────────────────────
    // This tiny script runs INSIDE the iframe to auto-size it, handles links
    function makeHeightReporter(src) {
        return `<script>
(function(){
    var SRC = '${src}';
    function report(){
        // Give the browser a tick to paint dropdowns first
        var h = Math.max(
            document.documentElement ? document.documentElement.scrollHeight : 0,
            document.body ? document.body.scrollHeight : 0,
            SRC === 'navbar' ? 900 : 100
        );
        window.parent.postMessage({ type: 'nav-preview-height', src: SRC, height: h }, '*');
    }
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(report, 400); });
    window.addEventListener('load', function(){ setTimeout(report, 600); });
    document.addEventListener('DOMContentLoaded', function(){
        try {
            new MutationObserver(function(){ setTimeout(report, 100); })
                .observe(document.body, { attributes: true, childList: true, subtree: false });
        } catch(e){}
    });
})();
<\/script>`;
    }

    // ── Preview Builders ─────────────────────────────────────────────────
    function generateMenuHtml(menuArr, isFooter) {
        if (!menuArr || !menuArr.length) return '';
        var html = '';
        menuArr.forEach(function(item) {
            // Fake URL for preview so clicks don't break the iframe
            var url  = 'javascript:void(0)';
            var icon = item.icon ? '<svg class="ico"><use href="#' + esc(item.icon) + '"></use></svg> ' : '';
            if (isFooter) {
                html += '<li><a href="' + url + '">' + icon + esc(item.label || '') + '</a></li>\n';
            } else {
                if (item.children && item.children.length > 0) {
                    html += '<li class="nav-item drop-wrap">\n';
                    html += '  <a class="nav-link has-drop" href="' + url + '" aria-haspopup="true" aria-expanded="false">\n';
                    html += '    ' + icon + esc(item.label || '') + ' <svg class="ico caret"><use href="#i-chevdown"></use></svg>\n';
                    html += '  </a>\n';
                    html += '  <ul class="dropdown" role="menu">\n';
                    item.children.forEach(function(child) {
                        if (child.label === '---') {
                            html += '    <div class="drop-divider"></div>\n';
                        } else {
                            // Fake URL for preview so clicks don't break the iframe
                            var curl = 'javascript:void(0)';
                            var cicon = child.icon ? '<svg class="ico"><use href="#' + esc(child.icon) + '"></use></svg> ' : '';
                            html += '    <li><a href="' + curl + '">' + cicon + esc(child.label || '') + '</a></li>\n';
                        }
                    });
                    html += '  </ul>\n';
                    html += '</li>\n';
                } else {
                    html += '<li class="nav-item">\n';
                    html += '  <a class="nav-link" href="' + url + '">' + icon + esc(item.label || '') + '</a>\n';
                    html += '</li>\n';
                }
            }
        });
        return html;
    }

    function injectMenuIntoHtml(templateHtml, menuArr, menuId, isFooter) {
        var pattern;
        if (!isFooter) {
            pattern = /(<ul[^>]*?(?:data-cms-menu=["']main["']|id=["']navList["'])[^>]*>)([\s\S]*?)(<\/\s*ul\s*>)/i;
        } else {
            pattern = /(<ul[^>]*?(?:data-cms-menu=["']footer["']|id=["']footerQuickLinks["'])[^>]*>|<h4[^>]*>Quick Links<\/h4>\s*<ul[^>]*class=["'][^"']*f-links[^"']*["'][^>]*>)([\s\S]*?)(<\/\s*ul\s*>)/i;
        }

        return templateHtml.replace(pattern, function(match, openTag, innerHtml, closeTag) {
            var menuHtml = generateMenuHtml(menuArr, isFooter);
            var drawerTopHtml = '';
            var mApplyHtml = '';
            
            if (!isFooter) {
                var m1 = innerHtml.match(/<li[^>]*id=["']drawerTop["'][^>]*>[\s\S]*?<\/li>/i);
                if (m1) drawerTopHtml = m1[0] + '\n';
                var m2 = innerHtml.match(/<li[^>]*id=["']mApplyItem["'][^>]*>[\s\S]*?<\/li>/i);
                if (m2) mApplyHtml = '\n' + m2[0];
            }
            return openTag + '\n' + drawerTopHtml + menuHtml + mApplyHtml + '\n' + closeTag;
        });
    }

    function buildNavbarPreviewSrcdoc() {
        const css  = window.NAVBAR_TEMPLATE_CSS  || '';
        const js   = window.NAVBAR_TEMPLATE_JS   || '';
        let html   = window.NAVBAR_TEMPLATE_HTML || '';
        html = injectMenuIntoHtml(html, navbarMenu, 'main', false);
        return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="/admin-vendor/fonts">
<link href="/admin-vendor/fonts/outfit/outfit.css" rel="stylesheet">
<link href="/admin-vendor/fonts/playfair/playfair.css" rel="stylesheet">
<style>
${css}
html, body { margin: 0; padding: 0; overflow-x: hidden; }
body { padding-top: 0 !important; }
.site-header { position: relative !important; box-shadow: var(--sh-md); }
/* Preview: only hide the sticky back-to-top button */
#btt { display: none !important; }
</style>
</head>
<body>
${html}
${makeHeightReporter('navbar')}
<script>
try {
    // Only remove sticky back-to-top so it doesn't clutter the preview
    document.querySelectorAll('#btt').forEach(function(e){ e.remove(); });
    (function(){ ${js.replace(/<\/script>/gi, '<\\/script>')} })();
} catch(e){ console.warn('nav preview', e); }
<\/script>
</body>
</html>`;
    }

    function buildFooterPreviewSrcdoc() {
        const navCSS  = window.NAVBAR_TEMPLATE_CSS  || '';
        const navHtml = window.NAVBAR_TEMPLATE_HTML || '';
        let ftHtml    = window.FOOTER_TEMPLATE_HTML || '';
        const spriteMatch = navHtml.match(/<svg[^>]*style=["']display:none["'][^>]*>[\s\S]*?<\/svg>/i);
        const sprite = spriteMatch ? spriteMatch[0] : '';
        ftHtml = injectMenuIntoHtml(ftHtml, footerMenu, 'footer', true);
        return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="/admin-vendor/fonts">
<link href="/admin-vendor/fonts/outfit/outfit.css" rel="stylesheet">
<link href="/admin-vendor/fonts/playfair/playfair.css" rel="stylesheet">
<style>
${navCSS}
html, body { margin: 0; padding: 0; overflow-x: hidden; background: #08131f; }
</style>
</head>
<body>
${sprite}
${ftHtml}
${makeHeightReporter('footer')}
</body>
</html>`;
    }

    window.refreshPreviews = function refreshPreviews() {
        // Start with generous heights; postMessage reporter will expand further if needed
        if (navIframe)  {
            navIframe.style.height  = '900px';
            navIframe.style.minHeight = '900px';
            navIframe.srcdoc  = buildNavbarPreviewSrcdoc();
        }
        if (footIframe) {
            footIframe.style.height = '400px';
            footIframe.style.minHeight = '400px';
            footIframe.srcdoc = buildFooterPreviewSrcdoc();
        }
    };

    // ── Tab Switching ────────────────────────────────────────────────────
    var CONTENT_TABS = ['navbar-content', 'footer-content'];

    window.switchNavTab = function (tab, btn) {
        currentTab = tab;
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        // Toggle all editor panels
        ['navbar','footer','navbar-content','footer-content'].forEach(function(id) {
            var el = document.getElementById('editor-' + id);
            if (el) el.classList.toggle('hidden', tab !== id);
        });

        // Toggle info pills
        document.querySelectorAll('.nav-info-pill').forEach(function(p) { p.classList.add('hidden'); });
        var activePill = document.getElementById('info-' + tab);
        if (activePill) activePill.classList.remove('hidden');

        var isContentTab = CONTENT_TABS.includes(tab);
        var previewNote = document.getElementById('content-tab-note');
        var scalerEl    = document.getElementById('nav-preview-scaler-wrap');

        // Show/hide iframes and content note
        if (navIframe) navIframe.style.display  = (!isContentTab && (tab === 'navbar')) ? 'block' : 'none';
        if (footIframe) footIframe.style.display = (!isContentTab && (tab === 'footer')) ? 'block' : 'none';
        if (previewNote) previewNote.classList.toggle('hidden', !isContentTab);
        if (scalerEl)    scalerEl.style.display = isContentTab ? 'none' : '';

        if (isContentTab) {
            // Content tabs: render form if first time
            if (tab === 'navbar-content' && !document.getElementById('navbar-content-form').dataset.rendered) {
                renderNavbarContentForm();
                document.getElementById('navbar-content-form').dataset.rendered = '1';
            }
            if (tab === 'footer-content' && !document.getElementById('footer-content-form').dataset.rendered) {
                renderFooterContentForm();
                document.getElementById('footer-content-form').dataset.rendered = '1';
            }
        } else {
            if (currentViewportWidth !== null) {
                applyPx(currentViewportWidth);
            } else {
                [navIframe, footIframe].forEach(f => {
                    if (f) { f.style.width = '100%'; f.style.minWidth = ''; }
                });
                if (scalerWrap) scalerWrap.style.height = '';
                setTimeout(() => autoSizeIframe(tab === 'navbar' ? navIframe : footIframe), 80);
            }
        }
    };

    // ── Icon options ─────────────────────────────────────────────────────
    const ICONS = [
        ['', '— No Icon —'],
        ['i-home', 'Home'],
        ['i-building', 'Building / Institute'],
        ['i-dept', 'Department'],
        ['i-campus', 'Campus'],
        ['i-student', 'Student'],
        ['i-brief', 'Briefcase / Placements'],
        ['i-life', 'Heart / Life'],
        ['i-alumni', 'Person / Alumni'],
        ['i-info', 'Information'],
        ['i-notice', 'Notice / Megaphone'],
        ['i-calendar', 'Calendar / Events'],
        ['i-download', 'Download'],
        ['i-shield', 'Shield / Admin'],
        ['i-gallery', 'Gallery / Images'],
        ['i-mail', 'Email / Mail'],
        ['i-apply', 'Apply / Pencil'],
        ['i-file', 'File / Document'],
        ['i-users', 'Users / Group'],
        ['i-award', 'Award / Achievement'],
        ['i-cpu', 'CPU / Electronics'],
        ['i-bolt', 'Bolt / Electrical'],
        ['i-laptop', 'Laptop / IT'],
        ['i-gear', 'Gear / Mechanical'],
        ['i-pill', 'Pill / Pharmacy'],
        ['i-flask', 'Flask / Lab'],
        ['i-book', 'Book / Library'],
        ['i-tools', 'Tools / Workshop'],
        ['i-hostel', 'Hostel'],
        ['i-star', 'Star / NSS'],
        ['i-ctrl', 'Controller / Activities'],
        ['i-cash', 'Cash / Scholarships'],
        ['i-clip', 'Clipboard / Projects'],
        ['i-chart', 'Chart / Results'],
        ['i-journal', 'Journal / Study'],
        ['i-loc', 'Location / Map'],
        ['i-phone', 'Phone'],
        ['i-clock', 'Clock / Hours'],
        ['i-ext', 'External Link'],
        ['i-fb', 'Facebook'],
        ['i-li', 'LinkedIn'],
        ['i-tw', 'Twitter / X'],
        ['i-yt', 'YouTube'],
        ['i-ig', 'Instagram'],
        ['i-tg', 'Telegram'],
    ];

    function iconSelectHTML(selected) {
        selected = selected || '';
        return '<select class="icon-select">' +
            ICONS.map(function(opt) {
                var val = opt[0], label = opt[1];
                return '<option value="' + val + '"' + (val === selected ? ' selected' : '') + '>' + label + '</option>';
            }).join('') + '</select>';
    }

    // ── Dirty state tracking ─────────────────────────────────────────────
    function markDirty() {
        isDirty = true;
        var dot = document.getElementById('unsaved-dot');
        var lbl = document.getElementById('unsaved-label');
        if (dot) dot.className = 'nav-save-dot dirty';
        if (lbl) lbl.textContent = 'Unsaved changes';

        // Debounced live preview refresh (1.5s after last edit)
        clearTimeout(previewDebounceTimer);
        previewDebounceTimer = setTimeout(function() {
            refreshPreviews();
        }, 1500);
    }
    function markSaved() {
        isDirty = false;
        var dot = document.getElementById('unsaved-dot');
        var lbl = document.getElementById('unsaved-label');
        if (dot) dot.className = 'nav-save-dot saved';
        if (lbl) lbl.textContent = 'All changes saved';
    }

    // ── Delete Confirmation ──────────────────────────────────────────
    var _deleteCallback = null;
    window.confirmDelete = function(label, onConfirm) {
        _deleteCallback = onConfirm;
        var lbl = document.getElementById('del-modal-label');
        if (lbl) lbl.textContent = '"' + (label || 'this item') + '"';
        document.getElementById('nav-delete-modal').style.display = 'flex';
    };
    window.closeDeleteConfirm = function() {
        document.getElementById('nav-delete-modal').style.display = 'none';
        _deleteCallback = null;
    };

    // ── Drag & Drop ──────────────────────────────────────────────────────
    var dragSrc = null;
    function setupDrag(el, menuArr, index, renderFn) {
        el.setAttribute('draggable', 'true');
        el.addEventListener('dragstart', function(e) {
            dragSrc = { index: index, menuArr: menuArr, renderFn: renderFn };
            el.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        el.addEventListener('dragend', function() { el.classList.remove('dragging'); });
        el.addEventListener('dragover', function(e) { e.preventDefault(); el.classList.add('drag-over'); });
        el.addEventListener('dragleave', function() { el.classList.remove('drag-over'); });
        el.addEventListener('drop', function(e) {
            e.preventDefault();
            el.classList.remove('drag-over');
            if (!dragSrc || dragSrc.menuArr !== menuArr || dragSrc.index === index) return;
            var moved = menuArr.splice(dragSrc.index, 1)[0];
            menuArr.splice(index, 0, moved);
            markDirty();
            renderFn();
        });
    }

    // ── Render Navbar ────────────────────────────────────────────────────
    function renderNavbar() {
        var list = document.getElementById('navbar-list');
        list.innerHTML = '';

        if (!navbarMenu.length) {
            list.innerHTML = emptyState('No links yet. Click <strong>Add Link</strong> to start.');
            return;
        }

        navbarMenu.forEach(function(item, idx) {
            var row = document.createElement('div');
            row.className = 'menu-item-row';

            var hasChildren = item.children && item.children.length > 0;
            var isDivider   = item.label === '---';

            row.innerHTML =
                '<div class="menu-item-main">' +
                    '<div class="drag-handle" title="Drag to reorder">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<circle cx="9" cy="5" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="19" r="1" fill="currentColor"/>' +
                            '<circle cx="15" cy="5" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="19" r="1" fill="currentColor"/>' +
                        '</svg>' +
                    '</div>' +
                    '<input class="item-label-input" type="text" placeholder="Label" value="' + esc(item.label || '') + '">' +
                    '<div class="item-action-btns">' +
                        (!isDivider ?
                            '<button class="item-action-btn btn-toggle-sub' + (hasChildren ? ' open' : '') + '" title="Toggle sub-links">' +
                                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>' +
                            '</button>' +
                            '<button class="item-action-btn btn-add-sub" title="Add sub-link">' +
                                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                            '</button>'
                        : '') +
                        '<button class="item-action-btn btn-delete" title="Delete">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                (!isDivider ?
                    '<div class="menu-item-url-row">' +
                        '<span class="url-row-label">URL</span>' +
                        '<input class="item-url-input" type="text" placeholder="/link or https://..." value="' + esc(item.url || '') + '">' +
                        '<span class="url-row-label">Icon</span>' +
                        iconSelectHTML(item.icon) +
                    '</div>' +
                    '<div class="menu-children-wrap' + (hasChildren ? '' : ' hidden') + '">' +
                        renderChildItems(item.children || []) +
                        '<div style="display:flex;gap:5px;margin-top:5px;">' +
                            '<button class="add-child-btn btn-add-child">' +
                                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Sub-link' +
                            '</button>' +
                            '<button class="add-divider-btn btn-add-divider">── Divider</button>' +
                        '</div>' +
                    '</div>'
                : '');

            setupDrag(row, navbarMenu, idx, renderNavbar);

            row.querySelector('.item-label-input').addEventListener('input', function(e) {
                navbarMenu[idx].label = e.target.value; markDirty();
            });

            var urlInp = row.querySelector('.item-url-input');
            if (urlInp) urlInp.addEventListener('input', function(e) {
                navbarMenu[idx].url = e.target.value; markDirty();
            });

            var iconSel = row.querySelector('.icon-select');
            if (iconSel) iconSel.addEventListener('change', function(e) {
                navbarMenu[idx].icon = e.target.value; markDirty();
            });

            var toggleBtn = row.querySelector('.btn-toggle-sub');
            var childWrap = row.querySelector('.menu-children-wrap');
            if (toggleBtn) toggleBtn.addEventListener('click', function() {
                toggleBtn.classList.toggle('open');
                if (childWrap) childWrap.classList.toggle('hidden');
            });

            var addSubBtn = row.querySelector('.btn-add-sub');
            if (addSubBtn) addSubBtn.addEventListener('click', function() {
                if (!navbarMenu[idx].children) navbarMenu[idx].children = [];
                navbarMenu[idx].children.push({ label: 'Sub Link', url: '#', icon: '' });
                if (childWrap) childWrap.classList.remove('hidden');
                if (toggleBtn) toggleBtn.classList.add('open');
                markDirty(); renderNavbar();
            });

            var addChildBtn = row.querySelector('.btn-add-child');
            if (addChildBtn) addChildBtn.addEventListener('click', function() {
                if (!navbarMenu[idx].children) navbarMenu[idx].children = [];
                navbarMenu[idx].children.push({ label: 'Sub Link', url: '#', icon: '' });
                markDirty(); renderNavbar();
            });

            var addDivBtn = row.querySelector('.btn-add-divider');
            if (addDivBtn) addDivBtn.addEventListener('click', function() {
                if (!navbarMenu[idx].children) navbarMenu[idx].children = [];
                navbarMenu[idx].children.push({ label: '---', url: '#', icon: '' });
                markDirty(); renderNavbar();
            });

            row.querySelector('.btn-delete').addEventListener('click', function() {
                var label = navbarMenu[idx] ? navbarMenu[idx].label : '';
                confirmDelete(label, function() {
                    navbarMenu.splice(idx, 1); markDirty(); renderNavbar();
                });
            });

            attachChildEvents(row, navbarMenu[idx], renderNavbar);
            list.appendChild(row);
        });
    }

    // ── Render Footer ────────────────────────────────────────────────────
    function renderFooter() {
        var list = document.getElementById('footer-list');
        list.innerHTML = '';

        if (!footerMenu.length) {
            list.innerHTML = emptyState('No links yet. Click <strong>Add Link</strong> to start.');
            return;
        }

        footerMenu.forEach(function(item, idx) {
            var row = document.createElement('div');
            row.className = 'menu-item-row';

            row.innerHTML =
                '<div class="menu-item-main">' +
                    '<div class="drag-handle" title="Drag to reorder">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<circle cx="9" cy="5" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="19" r="1" fill="currentColor"/>' +
                            '<circle cx="15" cy="5" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="19" r="1" fill="currentColor"/>' +
                        '</svg>' +
                    '</div>' +
                    '<input class="item-label-input" type="text" placeholder="Label" value="' + esc(item.label || '') + '">' +
                    '<div class="item-action-btns">' +
                        '<button class="item-action-btn btn-delete" title="Delete">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="menu-item-url-row">' +
                    '<span class="url-row-label">URL</span>' +
                    '<input class="item-url-input" type="text" placeholder="/link or https://..." value="' + esc(item.url || '') + '">' +
                    '<span class="url-row-label">Icon</span>' +
                    iconSelectHTML(item.icon) +
                '</div>';

            setupDrag(row, footerMenu, idx, renderFooter);

            row.querySelector('.item-label-input').addEventListener('input', function(e) {
                footerMenu[idx].label = e.target.value; markDirty();
            });
            row.querySelector('.item-url-input').addEventListener('input', function(e) {
                footerMenu[idx].url = e.target.value; markDirty();
            });
            row.querySelector('.icon-select').addEventListener('change', function(e) {
                footerMenu[idx].icon = e.target.value; markDirty();
            });
            row.querySelector('.btn-delete').addEventListener('click', function() {
                var label = footerMenu[idx] ? footerMenu[idx].label : '';
                confirmDelete(label, function() {
                    footerMenu.splice(idx, 1); markDirty(); renderFooter();
                });
            });

            list.appendChild(row);
        });
    }

    // ── Children HTML ────────────────────────────────────────────────────
    function renderChildItems(children) {
        if (!children || !children.length) return '';
        return children.map(function(child, ci) {
            if (child.label === '---') {
                return '<div class="menu-child-divider-row" data-ci="' + ci + '">' +
                    '<span></span><span>── Divider ──</span><span></span>' +
                    '<button class="item-action-btn btn-delete-child" data-ci="' + ci + '" style="color:var(--text-3)">' +
                        '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                    '</button></div>';
            }
            return '<div class="menu-child-row" data-ci="' + ci + '">' +
                '<input class="item-label-input child-label" type="text" placeholder="Sub-link label" value="' + esc(child.label || '') + '">' +
                '<input class="item-url-input child-url" type="text" placeholder="/url" value="' + esc(child.url || '') + '">' +
                iconSelectHTML(child.icon) +
                '<button class="item-action-btn btn-delete-child" data-ci="' + ci + '" title="Remove">' +
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button></div>';
        }).join('');
    }

    function attachChildEvents(rowEl, parentItem, renderFn) {
        rowEl.querySelectorAll('.child-label').forEach(function(inp) {
            var ci = parseInt(inp.closest('[data-ci]').dataset.ci);
            inp.addEventListener('input', function(e) { parentItem.children[ci].label = e.target.value; markDirty(); });
        });
        rowEl.querySelectorAll('.child-url').forEach(function(inp) {
            var ci = parseInt(inp.closest('[data-ci]').dataset.ci);
            inp.addEventListener('input', function(e) { parentItem.children[ci].url = e.target.value; markDirty(); });
        });
        rowEl.querySelectorAll('.menu-children-wrap .icon-select').forEach(function(sel) {
            var ci = parseInt(sel.closest('[data-ci]').dataset.ci);
            sel.addEventListener('change', function(e) { parentItem.children[ci].icon = e.target.value; markDirty(); });
        });
        rowEl.querySelectorAll('.btn-delete-child').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var ci = parseInt(btn.dataset.ci);
                var childLabel = parentItem.children && parentItem.children[ci]
                    ? parentItem.children[ci].label : 'sub-link';
                if (childLabel === '---') childLabel = 'Divider';
                confirmDelete(childLabel, function() {
                    parentItem.children.splice(ci, 1);
                    markDirty(); renderFn();
                });
            });
        });
    }

    // ── Add Buttons ──────────────────────────────────────────────────────
    document.getElementById('add-nav-root').addEventListener('click', function() {
        navbarMenu.push({ label: 'New Link', url: '#', icon: 'i-home' });
        markDirty(); renderNavbar();
    });
    document.getElementById('add-footer-root').addEventListener('click', function() {
        footerMenu.push({ label: 'New Link', url: '#', icon: 'i-home' });
        markDirty(); renderFooter();
    });


    // ══════════════════════════════════════════════════════════════════════════
    // ── CONTENT EDITOR ── Full section content as JSON forms ─────────────────
    // ══════════════════════════════════════════════════════════════════════════

    // Shallow copy of parsed content (live state)
    var navbarContentData  = JSON.parse(JSON.stringify(window.NAVBAR_CONTENT || {}));
    var footerContentData  = JSON.parse(JSON.stringify(window.FOOTER_CONTENT || {}));

    /** Generic field builder helpers — using new premium CSS classes */
    function cfField(label, key, obj, type) {
        type = type || 'text';
        var id  = 'cf-' + key;
        var val = obj[key] != null ? String(obj[key]) : '';
        var inp = type === 'textarea'
            ? '<textarea id="' + id + '" class="content-field-textarea">' + esc(val) + '</textarea>'
            : '<input id="' + id + '" class="content-field-input" type="' + type + '" value="' + esc(val) + '">';
        return '<div class="content-field-group"><label class="content-field-label">' + label + '</label>' + inp + '</div>';
    }

    function cfSection(title, iconEmoji, content) {
        var svgMap = {
            '🏷️': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
            '⭐':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            '✅':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            '🌐':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            '📞':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 11.39a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.5h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.02z"/></svg>',
            '🔗':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
            '⚡':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
            '📚':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            '📄':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            '🗺️': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
            '←':  '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
        };
        var svg = svgMap[iconEmoji] || '';
        return '<div class="content-section">' +
            '<div class="content-section-header">' + svg + title + '</div>' +
            '<div class="content-section-body">' + content + '</div>' +
            '</div>';
    }

    function cfLinkList(label, key, obj, addBtnId) {
        var links = obj[key] || [];
        var rows = links.map(function(link, i) {
            return '<div class="cf-link-row social-link-row" data-key="' + key + '" data-i="' + i + '">' +
                '<input class="content-field-input cf-link-label" type="text" placeholder="Label" value="' + esc(link.label || '') + '" style="max-width:160px;">' +
                '<input class="content-field-input cf-link-url" type="text" placeholder="/url or https://" value="' + esc(link.url || '') + '">' +
                '<button class="item-action-btn btn-delete cf-link-del" data-i="' + i + '" title="Remove">' +
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button>' +
            '</div>';
        }).join('');

        return '<div class="content-field-group">' +
            '<div class="content-field-label" style="display:flex;align-items:center;justify-content:space-between;">' +
                label +
                '<button class="nav-add-root-btn" id="' + addBtnId + '" style="padding:4px 10px;font-size:.75rem;">+ Add</button>' +
            '</div>' +
            '<div id="ll-' + key + '" style="display:flex;flex-direction:column;gap:6px;">' + rows + '</div>' +
        '</div>';
    }

    function cfSocialList(obj) {
        var socials = obj.socials || [];
        var rows = socials.map(function(s, i) {
            return '<div class="social-link-row cf-link-row" data-key="socials" data-i="' + i + '">' +
                '<span class="social-link-platform">' + esc(s.platform || 'Social') + '</span>' +
                '<input class="social-link-input cf-soc-platform" type="text" placeholder="Platform" value="' + esc(s.platform || '') + '" style="max-width:120px;">' +
                '<input class="social-link-input cf-soc-url" type="text" placeholder="URL" value="' + esc(s.url || '') + '">' +
                '<button class="item-action-btn btn-delete cf-link-del" data-i="' + i + '" title="Remove">' +
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button>' +
            '</div>';
        }).join('');

        return '<div class="content-field-group">' +
            '<div class="content-field-label" style="display:flex;align-items:center;justify-content:space-between;">Social Links' +
                '<button class="nav-add-root-btn" id="cf-add-soc" style="padding:4px 10px;font-size:.75rem;">+ Add</button>' +
            '</div>' +
            '<div id="ll-socials" style="display:flex;flex-direction:column;gap:6px;">' + rows + '</div>' +
        '</div>';
    }

    /** Wire up a link list — syncs DOM → data object */
    function wireList(container, destArr, platformMode) {
        container.querySelectorAll('.cf-link-row').forEach(function(row) {
            var i = parseInt(row.dataset.i);
            if (platformMode) {
                row.querySelector('.cf-soc-platform').addEventListener('input', function(e) { destArr[i].platform = e.target.value; });
                row.querySelector('.cf-soc-url').addEventListener('input', function(e) { destArr[i].url = e.target.value; });
            } else {
                row.querySelector('.cf-link-label').addEventListener('input', function(e) { destArr[i].label = e.target.value; });
                row.querySelector('.cf-link-url').addEventListener('input', function(e) { destArr[i].url = e.target.value; });
            }
            row.querySelector('.cf-link-del').addEventListener('click', function() {
                destArr.splice(i, 1);
                row.remove();
                // Re-index remaining rows
                container.querySelectorAll('.cf-link-row').forEach(function(r, ni) { r.dataset.i = ni; });
            });
        });
    }

    // ── Navbar Content Form ───────────────────────────────────────────────
    function renderNavbarContentForm() {
        var d   = navbarContentData;
        var wrap = document.getElementById('navbar-content-form');

        wrap.innerHTML =
            cfSection('Brand', '🏷️',
                cfField('Brand Name', 'brand_name', d) +
                cfField('Brand Tagline', 'brand_tagline', d) +
                cfField('Brand URL', 'brand_url', d, 'url')
            ) +
            cfSection('Affiliation Badge', '⭐',
                cfField('Badge Name', 'affil_name', d) +
                cfField('Badge Sub', 'affil_sub', d) +
                cfField('Badge URL', 'affil_url', d, 'url')
            ) +
            cfSection('Apply Button', '✅',
                cfField('Label', 'apply_label', d) +
                cfField('URL', 'apply_url', d, 'url')
            ) +
            cfSection('Social Links', '🌐', cfSocialList(d)) +
            cfSection('Top-Bar Left Links', '←', cfLinkList('Links', 'topbar_left', d, 'cf-add-tb-left')) +
            cfSection('Main Navigation Links', '🗺️', cfLinkList('Links (Top Level)', 'nav_links', d, 'cf-add-nav'));

        // Bind text fields to data
        ['brand_name','brand_tagline','brand_url','affil_name','affil_sub','affil_url','apply_label','apply_url'].forEach(function(key) {
            var el = document.getElementById('cf-' + key);
            if (el) el.addEventListener('input', function(e) { d[key] = e.target.value; });
        });

        // Socials
        wireList(document.getElementById('ll-socials'), d.socials, true);
        document.getElementById('cf-add-soc').addEventListener('click', function() {
            d.socials.push({ platform: 'New', url: '#' });
            document.getElementById('navbar-content-form').dataset.rendered = '';
            renderNavbarContentForm();
        });

        // Top-bar left
        wireList(document.getElementById('ll-topbar_left'), d.topbar_left, false);
        document.getElementById('cf-add-tb-left').addEventListener('click', function() {
            d.topbar_left.push({ label: 'New', url: '#' });
            document.getElementById('navbar-content-form').dataset.rendered = '';
            renderNavbarContentForm();
        });

        // Nav links
        wireList(document.getElementById('ll-nav_links'), d.nav_links, false);
        document.getElementById('cf-add-nav').addEventListener('click', function() {
            d.nav_links.push({ label: 'New', url: '#' });
            document.getElementById('navbar-content-form').dataset.rendered = '';
            renderNavbarContentForm();
        });
    }

    // ── Footer Content Form ───────────────────────────────────────────────
    function renderFooterContentForm() {
        var d    = footerContentData;
        var wrap = document.getElementById('footer-content-form');

        wrap.innerHTML =
            cfSection('Brand', '🏷️',
                cfField('Brand Name', 'brand_name', d) +
                cfField('Brand Sub', 'brand_sub', d) +
                cfField('Brand URL', 'brand_url', d, 'url') +
                cfField('About Text', 'about_text', d, 'textarea')
            ) +
            cfSection('Affiliation', '⭐',
                cfField('Name', 'affil_name', d) +
                cfField('Sub', 'affil_sub', d) +
                cfField('URL', 'affil_url', d, 'url')
            ) +
            cfSection('Contact', '📞',
                cfField('Address', 'contact_address', d) +
                cfField('Email', 'contact_email', d, 'email') +
                cfField('Phone', 'contact_phone', d) +
                cfField('Office Hours', 'office_hours', d)
            ) +
            cfSection('Social Links', '🌐', cfSocialList(d)) +
            cfSection('Important Links', '🔗', cfLinkList('Links', 'important_links', d, 'cf-add-imp')) +
            cfSection('Quick Links', '⚡', cfLinkList('Links', 'quick_links', d, 'cf-add-ql')) +
            cfSection('Resource Links', '📚', cfLinkList('Links', 'resource_links', d, 'cf-add-res')) +
            cfSection('Footer Bottom', '📄',
                cfField('Copyright text', 'copyright', d) +
                cfField('Developer credit', 'developer', d)
            );

        // Text fields
        ['brand_name','brand_sub','brand_url','about_text','affil_name','affil_sub','affil_url',
         'contact_address','contact_email','contact_phone','office_hours','copyright','developer'].forEach(function(key) {
            var el = document.getElementById('cf-' + key);
            if (el) el.addEventListener('input', function(e) { d[key] = e.target.value; });
        });

        // Socials
        wireList(document.getElementById('ll-socials'), d.socials, true);
        document.getElementById('cf-add-soc').addEventListener('click', function() {
            d.socials.push({ platform: 'New', url: '#' });
            document.getElementById('footer-content-form').dataset.rendered = '';
            renderFooterContentForm();
        });

        // Link lists
        function wireAddBtn(btnId, arr, reRender) {
            var btn = document.getElementById(btnId);
            if (btn) btn.addEventListener('click', function() {
                arr.push({ label: 'New Link', url: '#' });
                document.getElementById('footer-content-form').dataset.rendered = '';
                renderFooterContentForm();
            });
        }

        wireList(document.getElementById('ll-important_links'), d.important_links, false);
        wireList(document.getElementById('ll-quick_links'), d.quick_links, false);
        wireList(document.getElementById('ll-resource_links'), d.resource_links, false);
        wireAddBtn('cf-add-imp', d.important_links);
        wireAddBtn('cf-add-ql', d.quick_links);
        wireAddBtn('cf-add-res', d.resource_links);
    }

    // ── Save Content API ─────────────────────────────────────────────────
    async function saveContentSection(section, data, btn) {
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Saving…';

        try {
            var r = await fetch(window.NAV_CONTENT_SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ section: section, data: data })
            });
            var resp = await r.json();
            if (r.ok && resp.success) {
                if (typeof window.cmsToast === 'function') window.cmsToast(resp.message || 'Saved! ✅', 'success');
                markSaved();
                refreshPreviews();
            } else {
                if (typeof window.cmsToast === 'function') window.cmsToast(resp.error || 'Save failed', 'error');
            }
        } catch(e) {
            if (typeof window.cmsToast === 'function') window.cmsToast('Network error', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = orig;
    }

    document.getElementById('save-navbar-content-btn').addEventListener('click', function() {
        saveContentSection('navbar', navbarContentData, this);
    });
    document.getElementById('save-footer-content-btn').addEventListener('click', function() {
        saveContentSection('footer', footerContentData, this);
    });


    // ── Save Confirmation Modal ───────────────────────────────────────────
    window.showSaveConfirm = function() {
        var navCount  = document.getElementById('save-modal-nav-count');
        var ftCount   = document.getElementById('save-modal-footer-count');
        if (navCount)  navCount.textContent  = navbarMenu.length;
        if (ftCount)   ftCount.textContent   = footerMenu.length;
        document.getElementById('nav-save-modal').style.display = 'flex';
    };
    window.closeSaveConfirm = function() {
        document.getElementById('nav-save-modal').style.display = 'none';
    };

    document.getElementById('nav-save-modal').addEventListener('click', function(e) {
        if (e.target === this) window.closeSaveConfirm();
    });

    document.getElementById('nav-delete-modal').addEventListener('click', function(e) {
        if (e.target === this) window.closeDeleteConfirm();
    });
    document.getElementById('nav-confirm-delete-btn').addEventListener('click', function() {
        if (typeof _deleteCallback === 'function') _deleteCallback();
        window.closeDeleteConfirm();
    });

    document.getElementById('save-nav-btn').addEventListener('click', function() {
        window.showSaveConfirm();
    });

    document.getElementById('nav-confirm-save-btn').addEventListener('click', function() {
        window.closeSaveConfirm();
        var btn = document.getElementById('save-nav-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Saving…';

        fetch(window.NAV_SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ navbar_menu: navbarMenu, footer_menu: footerMenu })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Navigation';
            if (data.success !== false) {
                markSaved();
                refreshPreviews();
                if (typeof window.cmsToast === 'function') window.cmsToast('Navigation saved! ✅', 'success');
            } else {
                if (typeof window.cmsToast === 'function') window.cmsToast('Failed to save.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Navigation';
            if (typeof window.cmsToast === 'function') window.cmsToast('Network error — could not save.', 'error');
        });
    });

    // ── Helpers ──────────────────────────────────────────────────────────
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function emptyState(msg) {
        return '<div class="nav-empty-state">' +
            '<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>' +
            '<p>' + msg + '</p></div>';
    }

    // ── Init ─────────────────────────────────────────────────────────────
    renderNavbar();
    renderFooter();
    refreshPreviews();
});