// Event_new
(function() {
            'use strict';

            // ═══════════════ CANVAS ANIMATION ═══════════════
            const hero = document.getElementById('heroBanner');
            const canvas = document.getElementById('heroCanvas');
            if (hero && canvas) {
                const ctx = canvas.getContext('2d');
                let particles = [], mouseX = null, mouseY = null, time = 0;
                const resize = () => { canvas.width = hero.clientWidth; canvas.height = hero.clientHeight; particles = Array.from({length: 150}, () => ({ x: Math.random()*canvas.width, y: Math.random()*canvas.height, size: Math.random()*2+1, alpha: Math.random()*0.3+0.1 })); };
                window.addEventListener('resize', resize); resize();
                hero.addEventListener('mousemove', e => { const r = hero.getBoundingClientRect(); mouseX = e.clientX - r.left; mouseY = e.clientY - r.top; });
                hero.addEventListener('mouseleave', () => { mouseX = mouseY = null; });
                const animate = (timestamp) => {
                    time += 0.016; ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.globalCompositeOperation = 'lighter';
                    particles.forEach(p => {
                        let fx = Math.sin(p.y*0.01 + time*0.4), fy = Math.cos(p.x*0.01 + time*0.3);
                        if (mouseX !== null) { const dx = p.x-mouseX, dy = p.y-mouseY, dist = Math.sqrt(dx*dx+dy*dy); if(dist<200) { const s = (1-dist/200)*4; fx += (dx/dist)*s; fy += (dy/dist)*s; } }
                        p.x = (p.x + fx + canvas.width) % canvas.width; p.y = (p.y + fy + canvas.height) % canvas.height;
                        ctx.fillStyle = `rgba(147, 197, 253, ${p.alpha})`; ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI*2); ctx.fill();
                    });
                    requestAnimationFrame(animate);
                };
                requestAnimationFrame(animate);
            }
            
            // ═══════════════ DATA LOGIC ═══════════════
            let allEventsData = [], currentFiltered = [], currentVisibleCount = 0;
            const INITIAL_LOAD = 12, LOAD_MORE = 8;

            const parseImages = (r) => { if(!r) return []; if(Array.isArray(r)) return r; try { const p = JSON.parse(r); return Array.isArray(p) ? p : [r]; } catch(e) { return [r]; } };
            const sanitizeUrl = (url) => (typeof url === 'string' ? url.replace(/http:\/\/192\.168\.1\.2:8000/gi, 'https://gpnainital.com') : '');
            const getFileName = (u) => u.split('/').pop().split('?')[0] || 'Document';

            async function fetchEvents() {
                const grid = document.getElementById('eventsGrid'), loader = document.getElementById('loadingEvents');
                try {
                    const tokenRes = await fetch('https://gpnainital.com/api/token/events').then(r => r.json()).catch(() => ({}));
                    if(tokenRes.token) {
                        const dataRes = await fetch(`https://gpnainital.com/api/data/events?token=${tokenRes.token}`).then(r => r.json());
                        allEventsData = dataRes.data || dataRes.records || (Array.isArray(dataRes) ? dataRes : []);
                    }
                    if(!allEventsData || allEventsData.length === 0) throw new Error();
                } catch(e) {
                    console.warn("API empty, using rich demo fallback");
                    const demoEvents = [
                        {
                            title: "Annual Tech Symposium 2026", type: "Academic", 
                            event_date: "Oct 15, 2026", venue: "Main Auditorium", organizer: "CS Dept",
                            description: "Our flagship technical fest featuring coding contests, project showcases, and expert talks from industry leaders.\n\nLunch and participation certificates will be provided to all registered attendees.",
                            image_url: ["https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800", "https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800"],
                            file_url: ["https://example.com/schedule.pdf"]
                        },
                        {
                            title: "Inter-College Cricket Tournament", type: "Sports", 
                            event_date: "April 28 - May 5, 2026", venue: "UGIP Sports Ground", organizer: "Sports Council",
                            description: "The most awaited sports event of the year! Watch teams from across the state compete for the prestigious UGIP Champions Trophy.",
                            image_url: ["https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=800"],
                            video_url: "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                        },
                        {
                            title: "Navrang: Spring Cultural Fest", type: "Cultural", 
                            event_date: "Nov 5-6, 2026", venue: "Open Air Theatre", organizer: "Cultural Committee",
                            description: "Experience the vibrant culture with music, dance, theater, and art exhibitions.",
                            image_url: ["https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800", "https://images.unsplash.com/photo-1533174000220-9fa8120b0808?w=800"]
                        },
                        {
                            title: "AI & Machine Learning Workshop", type: "Workshop", 
                            event_date: "Feb 12, 2026", venue: "Computer Lab 3", organizer: "Tech Club",
                            description: "A hands-on, two-day workshop covering the fundamentals of Machine Learning using Python.",
                            image_url: ["https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800"],
                            file_url: ["https://example.com/ML_Kit.zip"]
                        },
                        {
                            title: "Robotics Design Seminar", type: "Seminar", 
                            event_date: "Dec 10, 2026", venue: "Seminar Hall B", organizer: "Robotics Society",
                            description: "Discussing the future of automated manufacturing and AI-driven robotics with guest speakers.",
                            image_url: ["https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800"]
                        }
                    ];
                    // Generate 20 items for demo pagination
                    allEventsData = [];
                    for(let i=0; i<20; i++) {
                        const base = demoEvents[i % demoEvents.length];
                        allEventsData.push({...base, title: `${base.title} #${i+1}`});
                    }
                }
                if(loader) loader.style.display = 'none'; 
                renderAllEventsToDOM(allEventsData); 
                window.filterEvents(true);
            }

            function renderAllEventsToDOM(events) {
                const grid = document.getElementById('eventsGrid'); grid.innerHTML = '';
                events.forEach((ev, i) => {
                    const type = (ev.type || 'General').toLowerCase();
                    const img = sanitizeUrl(parseImages(ev.image_url || ev.images)[0] || ev.image);
                    const bg = {'sports':'#1d4ed8','workshop':'#8b5cf6','cultural':'#db2777','seminar':'#059669','academic':'#0f172a'}[type] || '#2563eb';
                    const wrap = document.createElement('div'); wrap.className = 'ev-card-wrap'; wrap.style.display = 'none';
                    wrap.innerHTML = `<div class="ev-card" data-type="${type}" onclick="window.openDetail(${i})">
                        <div class="ev-top" style="background-color:${bg};${img?`background-image:url('${img}')`:''}">${img?'':'📅'}</div>
                        <div class="ev-body">
                            <span class="ev-tag-cat" style="color:${bg}">${ev.type||'EVENT'}</span>
                            <div class="ev-title">${ev.title||'Untitled'}</div>
                            <div class="ev-meta-row"><div><i class="bi bi-calendar3"></i> ${ev.event_date||ev.date||'TBA'}</div><div><i class="bi bi-geo-alt"></i> ${ev.venue||'TBA'}</div></div>
                            <div class="ev-action"><span>Explore</span><div class="ev-action-arrow"><i class="bi bi-arrow-right"></i></div></div>
                        </div>
                    </div>`;
                    grid.appendChild(wrap);
                });
            }

            window.filterEvents = function(reset = false) {
                const type = document.getElementById('typeFilter').value, q = document.getElementById('searchInput').value.toLowerCase();
                const wrappers = Array.from(document.querySelectorAll('.ev-card-wrap'));
                currentFiltered = wrappers.filter(w => {
                    const matchType = type === 'all' || w.querySelector('.ev-card').dataset.type === type;
                    const matchQ = !q || w.querySelector('.ev-title').innerText.toLowerCase().includes(q);
                    return matchType && matchQ;
                });
                wrappers.forEach(w => { w.style.display = 'none'; w.style.animation = 'none'; });
                if(reset) currentVisibleCount = INITIAL_LOAD;
                const show = currentFiltered.slice(0, currentVisibleCount);
                show.forEach((w, i) => { w.style.display = 'block'; void w.offsetWidth; w.style.animation = 'fadeInSlideUp 0.5s ease-out forwards'; w.style.animationDelay = `${(i%LOAD_MORE)*0.05}s`; });
                document.getElementById('noEvents').style.display = currentFiltered.length === 0 ? 'block' : 'none';
                document.getElementById('loadMoreWrap').style.display = currentFiltered.length > currentVisibleCount ? 'block' : 'none';
            };

            window.loadMoreEvents = () => { currentVisibleCount += LOAD_MORE; window.filterEvents(); };
            window.resetFilters = () => { document.getElementById('typeFilter').value = 'all'; document.getElementById('searchInput').value = ''; window.filterEvents(true); };

            window.openDetail = (idx) => {
                const ev = allEventsData[idx]; if(!ev) return;
                document.getElementById('viewList').style.display = 'none';
                document.getElementById('viewDetail').style.display = 'block';
                window.scrollTo(0, 0);

                const images = parseImages(ev.image_url || ev.images).map(sanitizeUrl);
                const bannerImg = document.getElementById('dtBannerImg');
                if(images[0]) { bannerImg.src = images[0]; bannerImg.style.display = 'block'; document.getElementById('dtBannerIcon').style.display = 'none'; }
                else { bannerImg.style.display = 'none'; document.getElementById('dtBannerIcon').style.display = 'block'; }

                document.getElementById('dtHeroTitle').textContent = ev.title || 'Untitled Event';
                document.getElementById('dtHeroDept').textContent = ev.type || 'Special Event';
                document.getElementById('dtDate').textContent = ev.event_date || ev.date || 'TBA';
                document.getElementById('dtVenue').textContent = ev.venue || 'TBA';
                document.getElementById('dtOrg').textContent = ev.organizer || 'UGIP Administration';
                
                const desc = ev.description || ev.desc || 'No detailed description available.';
                document.getElementById('dtArticle').innerHTML = desc.split('\n\n').map(p => `<p>${p}</p>`).join('');

                const files = parseImages(ev.file_url || ev.files).map(sanitizeUrl).filter(f => f);
                const resCard = document.getElementById('dtResCard'), resBtns = document.getElementById('dtResButtons');
                resBtns.innerHTML = '';
                if(files.length > 0) {
                    files.forEach(f => {
                        const name = getFileName(f), ext = name.split('.').pop().toUpperCase();
                        resBtns.innerHTML += `<a href="${f}" target="_blank" class="d-file-card"><div class="d-file-left"><div class="d-file-icon"><i class="bi bi-file-earmark-text"></i></div><div class="d-file-info"><span class="d-file-name">${name}</span><span class="d-file-type">${ext} Document</span></div></div><div class="d-file-dl"><i class="bi bi-download"></i></div></a>`;
                    });
                    resCard.style.display = 'block';
                } else resCard.style.display = 'none';

                const gallery = document.getElementById('dtGallery'), gWrap = document.getElementById('dtGalleryWrap');
                gallery.innerHTML = ''; let hasG = false;
                if(ev.video_url) { hasG = true; gallery.innerHTML += `<div class="d-gallery-item full"><iframe src="${ev.video_url.replace('watch?v=','embed/')}"></iframe></div>`; }
                if(images.length > 1) { hasG = true; images.slice(1).forEach(img => gallery.innerHTML += `<div class="d-gallery-item"><img src="${img}"></div>`); }
                gWrap.style.display = hasG ? 'block' : 'none';
                document.getElementById('dtHeroGalleryBtn').style.display = hasG ? 'block' : 'none';
            };

            window.closeDetail = () => { document.getElementById('viewDetail').style.display = 'none'; document.getElementById('viewList').style.display = 'block'; window.scrollTo(0,0); };
            document.addEventListener('DOMContentLoaded', fetchEvents);
        })();