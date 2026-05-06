// Project
(function() {
            // ── BOOTSTRAP 5 ICONS ──
            const iconGithub = () => `<i class="bi bi-code-slash"></i>`;
            const iconVideo = () => `<i class="bi bi-play-circle-fill"></i>`;
            const iconLinkedIn = () => `<i class="bi bi-linkedin"></i>`;
            const iconGlobe = () => `<i class="bi bi-globe2"></i>`;
            const iconDoc = () => `<i class="bi bi-file-earmark-text-fill"></i>`;

            // ═══════════════ REPELLING WATER FLOW (Canvas Animation) ═══════════════
            const hero = document.getElementById('heroBanner');
            const canvas = document.getElementById('heroCanvas');
            
            if (hero && canvas) {
                const ctx = canvas.getContext('2d');
                const particles = [];
                const PARTICLE_COUNT = window.innerWidth < 768 ? 120 : 300; 
                let mouseX = null, mouseY = null, time = 0;

                function resizeCanvas() { canvas.width = hero.clientWidth; canvas.height = hero.clientHeight; }
                window.addEventListener('resize', () => { resizeCanvas(); resetParticles(); });
                resizeCanvas();

                function createParticle() {
                    return { x: Math.random() * canvas.width, y: Math.random() * canvas.height, vx: 0, vy: 0, size: Math.random() * 2.2 + 0.8, alpha: Math.random() * 0.25 + 0.05 };
                }
                function resetParticles() {
                    particles.length = 0;
                    for (let i = 0; i < PARTICLE_COUNT; i++) particles.push(createParticle());
                }
                resetParticles();

                hero.addEventListener('mousemove', e => { const r = hero.getBoundingClientRect(); mouseX = e.clientX - r.left; mouseY = e.clientY - r.top; });
                hero.addEventListener('mouseleave', () => { mouseX = null; mouseY = null; });
                hero.addEventListener('touchmove', e => { const r = hero.getBoundingClientRect(); mouseX = e.touches[0].clientX - r.left; mouseY = e.touches[0].clientY - r.top; }, { passive: true });
                hero.addEventListener('touchend', () => { mouseX = null; mouseY = null; });

                function updateParticles(dt = 0.016) {
                    time += dt;
                    for (let p of particles) {
                        const waveX = Math.sin(p.y * 0.01 + time * 0.6) * 1.2, waveY = Math.cos(p.x * 0.012 + time * 0.45) * 1.0;
                        let fx = waveX, fy = waveY;
                        if (mouseX !== null && mouseY !== null) {
                            const dx = p.x - mouseX, dy = p.y - mouseY, dist = Math.sqrt(dx * dx + dy * dy) || 1;
                            if (dist < 180) { const strength = (1 - dist / 180) * 3.0; fx += (dx / dist) * strength; fy += (dy / dist) * strength; }
                        }
                        p.vx += fx * 0.04; p.vy += fy * 0.04; p.vx *= 0.95; p.vy *= 0.95; p.x += p.vx; p.y += p.vy;
                        if (p.x < -20) p.x = canvas.width + 20; if (p.x > canvas.width + 20) p.x = -20;
                        if (p.y < -20) p.y = canvas.height + 20; if (p.y > canvas.height + 20) p.y = -20;
                    }
                }

                function drawParticles() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.globalCompositeOperation = 'lighter';
                    for (let p of particles) {
                        const grad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 2.5);
                        grad.addColorStop(0, `rgba(147, 197, 253, ${p.alpha + 0.1})`);
                        grad.addColorStop(1, 'rgba(59, 130, 246, 0)');
                        ctx.fillStyle = grad; ctx.beginPath(); ctx.arc(p.x, p.y, p.size * 2.5, 0, Math.PI * 2); ctx.fill();
                    }
                    ctx.globalCompositeOperation = 'source-over';
                }

                let lastTime = performance.now();
                function animate(timestamp) {
                    const dt = Math.min(0.05, (timestamp - lastTime) / 1000);
                    lastTime = timestamp; updateParticles(dt); drawParticles(); requestAnimationFrame(animate);
                }
                requestAnimationFrame(animate);
            }

            // ═══════════════ PROJECT DATA FETCHING & LOGIC ═══════════════
            const TABLE_NAME = 'projects';
            
            const DEMO_PROJECTS = [
                { title: "AI Smart Attendance", department: "Information Technology", year: "2024", tags: "Python, PyTorch, OpenCV", team: "Rohit Sharma, Priya Singh", guide: "Prof. Alok Verma", description: "Facial recognition attendance system with edge computing. The AI Attendance System leverages edge-based facial recognition to automate classroom attendance without disrupting the educational flow. Built on a proprietary lightweight neural network.", github_url: "https://github.com", video_url: "https://www.youtube.com/embed/dQw4w9WgXcQ", linkedin_url: "https://linkedin.com", image_url: '["https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=600", "https://images.unsplash.com/photo-1527430253228-e93688616381?w=600"]' },
                { title: "Solar Hexacopter", department: "Mechanical Engineering", year: "2023", tags: "AutoCAD, DroneKit", team: "Vikram Joshi, Karan Rawat", guide: "Dr. Suresh Chandra", description: "Autonomous solar drone for agricultural survey. Design and fabrication of a highly efficient solar-electric hybrid vehicle tailored for short-distance hilly terrain transport.", linkedin_url: "https://linkedin.com", document_url: "https://example.com", image_url: '["https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=600", "https://images.unsplash.com/photo-1581092335878-2d9ff86ca2bf?w=600"]' },
                { title: "Smart Grid Tracker", department: "Electrical Engineering", year: "2024", tags: "Arduino, React, IoT", team: "Ravi Kumar, Meena Devi", guide: "Prof. Anita Sharma", description: "IoT smart meter with anomaly detection.", github_url: "https://github.com", video_url: "https://www.youtube.com/embed/9bZkp7q19f0", image_url: '["https://images.unsplash.com/photo-1593941707882-a5bba14938cb?w=600"]' },
                { title: "Seismic Isolation", department: "Civil Engineering", year: "2023", tags: "STAAD Pro, AutoCAD", team: "Amit Kumar, Gaurav Negi", guide: "Prof. Rajesh Pant", description: "Base isolation prototype reducing seismic acceleration.", image_url: '["https://images.unsplash.com/photo-1541888086925-0c13d3106208?w=600", "https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=600"]' },
                { title: "Herbal Cream", department: "Pharmacy", year: "2022", tags: "Formulation, Ayurveda", team: "Priya Das", guide: "Dr. Kavita Joshi", description: "Natural skincare from Himalayan flora." }
            ];

            let allProjectsData = [];
            let activeCategory = 'all', allCardWraps = [], loadedCount = 0;
            const LOAD_STEP = 12, LOAD_MORE_STEP = 8;

            async function fetchProjects() {
                const loader = document.getElementById('loadingProjects');
                const noRes = document.getElementById('emptyState');
                try {
                    // Step 1: Fetch short-lived token (No API Key exposed!)
                    const tokenRes = await fetch(`https://gpnainital.com/api/token/${TABLE_NAME}`);
                    if (!tokenRes.ok) throw new Error('Failed to fetch secure token');
                    const { token } = await tokenRes.json();

                    // Step 2: Use the token to fetch the actual data
                    const res = await fetch(`https://gpnainital.com/api/data/${TABLE_NAME}?token=${token}&limit=all`);
                    if (!res.ok) throw new Error('API Data Fetch Error');
                    const json = await res.json();
                    
                    allProjectsData = json.data || json.records || [];
                    if(allProjectsData.length === 0) throw new Error('Empty Database');
                    
                    loader.style.display = 'none';
                    buildFilters();
                    initialLoad();
                    window.filterProjects();
                } catch(e) {
                    console.error("API failure:", e);
                    loader.style.display = 'none';
                    noRes.innerHTML = `<h3 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load projects</h3><p class="text-muted">Please check your connection or try again later.</p>`;
                    noRes.style.display = 'block';
                }
            }

            function parseImages(r) { if (!r) return []; if (Array.isArray(r)) return r; try { const parsed = JSON.parse(r); return Array.isArray(parsed) ? parsed : [r]; } catch (e) { return [r]; } }
            
            function sanitizeUrl(url) {
                if (!url) return '';
                return url.replace(/http:\/\/192\.168\.1\.2:8000/gi, 'https://gpnainital.com');
            }

            function buildFilters() {
                const row = document.getElementById('filterRow');
                const map = {};
                allProjectsData.forEach(p => { const d = p.department || 'General'; map[d] = (map[d] || 0) + 1; });
                
                let html = `<button class="f-pill active border-0" data-cat="all" onclick="window.setCategory('all',this)">All <span class="f-count bg-white bg-opacity-25 ms-1 px-2 rounded-pill">${allProjectsData.length}</span></button>`;
                
                Object.keys(map).sort().forEach(d => { 
                    const cleanD = d.replace('Engineering', 'Eng.').replace('Information Technology', 'IT');
                    html += `<button class="f-pill border-0" data-cat="${d.toLowerCase()}" onclick="window.setCategory('${d.toLowerCase()}',this)">${cleanD} <span class="f-count ms-1 px-2 rounded-pill">${map[d]}</span></button>`; 
                });
                row.innerHTML = html;
            }

            function renderProjects(projects, append) {
                const grid = document.getElementById('projectGrid');
                if (!append) { 
                    // Keep loading spinner if it exists, otherwise clear everything except it
                    Array.from(grid.children).forEach(c => {
                        if(c.id !== 'loadingProjects') c.remove();
                    });
                    allCardWraps = []; loadedCount = 0; 
                }
                
                projects.forEach((p, idx) => {
                    const globalIdx = allProjectsData.indexOf(p); 
                    const cover = parseImages(p.image_url || p.images)[0] || '';
                    const safeCover = sanitizeUrl(cover);
                    const wrap = document.createElement('div');
                    wrap.className = 'card-wrap fade-in-up';
                    wrap.dataset.cat = (p.department || p.dept || 'General').toLowerCase();
                    wrap.dataset.searchable = `${p.title} ${p.department} ${p.tags} ${p.team||p.by||''}`.toLowerCase();
                    wrap.style.animationDelay = `${(idx % LOAD_STEP) * 0.05}s`;
                    
                    const isNew = String(p.year).includes('2024') || String(p.year).includes('2025');
                    
                    wrap.innerHTML = `
                    <div class="p-card" onclick="window.openDetail(${globalIdx})">
                        <div class="p-visual">
                            ${safeCover ? `<img class="p-visual-img" src="${safeCover}" alt="${p.title}" loading="lazy">` : ''}
                            <div class="p-accent-strip"></div>
                            ${isNew ? `<div class="p-year-badge">✦ New</div>` : `<div class="p-year-badge">${p.year || ''}</div>`}
                        </div>
                        <div class="p-body">
                            <h3 class="p-title">${p.title || 'Untitled'}</h3>
                            <div class="p-meta">
                                <span class="text-truncate">${p.department || 'General'}</span>
                                <span class="text-truncate">${p.team || p.by || 'Student Team'}</span>
                            </div>
                            <div class="p-tags">
                                ${(p.tags || '').split(',').slice(0, 2).map(t => `<span class="p-tag">${t.trim()}</span>`).join('')}
                            </div>
                            <div class="p-footer">
                                <span class="p-cta-arrow"><i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>`;
                    
                    grid.appendChild(wrap);
                    allCardWraps.push(wrap);
                });
                
                loadedCount += projects.length;
                document.getElementById('resultCount').textContent = allCardWraps.length;
                updateLoadMoreBtn();
            }

            function initialLoad() { renderProjects(allProjectsData.slice(0, LOAD_STEP), false); }
            function updateLoadMoreBtn() { document.getElementById('loadMoreWrap').style.display = loadedCount >= allProjectsData.length ? 'none' : 'block'; }

            window.loadMoreProjects = function() {
                const q = document.getElementById('projectSearch').value.toLowerCase();
                const filteredData = allProjectsData.filter(p => {
                    const matchCat = (activeCategory === 'all' || (p.department || 'General').toLowerCase() === activeCategory);
                    const matchSearch = (!q || `${p.title} ${p.department} ${p.tags} ${p.team||p.by||''}`.toLowerCase().includes(q));
                    return matchCat && matchSearch;
                });

                const remaining = filteredData.slice(loadedCount, loadedCount + LOAD_MORE_STEP);
                if (remaining.length === 0) return;
                renderProjects(remaining, true);
                window.filterProjects();
            };

            window.setCategory = (cat, btn) => {
                document.querySelectorAll('.f-pill').forEach(b => b.classList.remove('active', 'text-white'));
                btn.classList.add('active', 'text-white');
                activeCategory = cat;
                window.filterProjects();
            };

            window.filterProjects = () => {
                const q = document.getElementById('projectSearch').value.toLowerCase();
                let visible = 0;
                allCardWraps.forEach(w => {
                    const match = (activeCategory === 'all' || w.dataset.cat === activeCategory) && (!q || w.dataset.searchable.includes(q));
                    if(match) {
                        w.style.display = 'block';
                        w.style.animation = 'none'; void w.offsetWidth; // trigger reflow
                        w.style.animation = `fadeInSlideUp 0.4s ease-out forwards ${(visible % 8) * 0.05}s`;
                        visible++;
                    } else {
                        w.style.display = 'none';
                    }
                });
                document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
                document.getElementById('resultCount').textContent = visible;
            };

            window.debouncedFilter = () => { 
                clearTimeout(window._debounce);
                window._debounce = setTimeout(window.filterProjects, 150); 
            };

            window.openDetail = (idx) => {
                const p = allProjectsData[idx]; if (!p) return;
                
                document.getElementById('viewList').style.display = 'none';
                document.getElementById('viewDetail').style.display = 'block';
                window.scrollTo({ top: 0 });
                
                document.getElementById('dtTopbarTitle').textContent = p.title || 'Untitled';
                
                const images = parseImages(p.image_url || p.images).map(img => sanitizeUrl(img));
                const bannerImg = document.getElementById('dtBannerImg');
                if (images.length > 0 && images[0]) { 
                    bannerImg.src = images[0]; bannerImg.style.display = 'block'; 
                } else { 
                    bannerImg.src = ''; bannerImg.style.display = 'none'; 
                }
                
                document.getElementById('dtHeroDept').textContent = p.department || 'Project';
                document.getElementById('dtHeroTitle').textContent = p.title || 'Untitled';
                
                document.getElementById('dtDept').textContent = p.department || '–';
                document.getElementById('dtYear').textContent = p.year || '–';
                document.getElementById('dtTeam').textContent = p.team || p.by || '–';
                document.getElementById('dtGuide').textContent = p.guide || p.guideName || '–';
                
                const chips = document.getElementById('dtChips'); chips.innerHTML = '';
                (p.tags || '').split(',').forEach(t => {
                    if (t.trim()) chips.innerHTML += `<span class="d-chip">${t.trim()}</span>`;
                });
                
                // Resources Building (1-row Grid)
                const resDiv = document.getElementById('dtResButtons'); resDiv.innerHTML = '';
                const resources = [
                    { url: p.github_url || p.github, icon: iconGithub, label: 'Code' }, 
                    { url: p.video_url || p.video, icon: iconVideo, label: 'Demo' }, 
                    { url: p.document_url || p.ppt || p.docs, icon: iconDoc, label: 'Docs' },
                    { url: p.linkedin_url || p.linkedin, icon: iconLinkedIn, label: 'LinkedIn' }, 
                    { url: p.liveUrl || p.live_url, icon: iconGlobe, label: 'Live' }
                ];
                let hasRes = false;
                resources.forEach(r => { 
                    if (r.url && r.url.trim() !== '') { 
                        hasRes = true; 
                        resDiv.innerHTML += `
                        <a href="${r.url}" target="_blank" rel="noopener" class="res-square-box text-center text-decoration-none">
                            <div class="res-icon-wrap">${r.icon()}</div>
                            <div class="res-label">${r.label}</div>
                        </a>`; 
                    } 
                });
                document.getElementById('dtResCard').style.display = hasRes ? 'flex' : 'none';
                
                const desc = p.description || p.desc || 'No detailed description provided.';
                document.getElementById('dtArticle').innerHTML = `<div>${desc.split('\n\n').map(c => `<p>${c.trim()}</p>`).join('')}</div>`;
                
                const gallery = document.getElementById('dtGallery'); gallery.innerHTML = '';
                const wrap = document.getElementById('dtGalleryWrap');
                let hasMedia = false;
                
                const vidUrl = p.video_url || p.video;
                if (vidUrl && vidUrl.trim() !== '') { 
                    hasMedia = true; 
                    const embed = vidUrl.includes('/embed/') ? vidUrl : vidUrl.replace('watch?v=', 'embed/');
                    gallery.innerHTML += `<div class="d-gallery-item full"><iframe src="${embed}" loading="lazy" allowfullscreen></iframe></div>`; 
                }
                
                if (images.length > 1) { 
                    hasMedia = true; 
                    for (let i = 1; i < images.length; i++) {
                        gallery.innerHTML += `<div class="d-gallery-item"><img src="${images[i]}" loading="lazy" alt="Project Media"></div>`; 
                    }
                }
                wrap.style.display = hasMedia ? 'block' : 'none';
            };

            window.closeDetail = () => { 
                document.getElementById('viewDetail').style.display = 'none';
                document.getElementById('viewList').style.display = 'block';
                window.scrollTo({ top: 0 });
                document.getElementById('dtBannerImg').src = ''; 
                
                // Stop video
                const gallery = document.getElementById('dtGallery');
                if (gallery) { const iframe = gallery.querySelector('iframe'); if(iframe) iframe.src = iframe.src; } 
            };

            // Init API Fetch
            fetchProjects();

        })();