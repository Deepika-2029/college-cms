/* navbar */
(function(){
  'use strict';

  const header   = document.getElementById('siteHeader');
  const menuBtn  = document.getElementById('menuBtn');
  const navList  = document.getElementById('navList');
  const backdrop = document.getElementById('backdrop');
  const btt      = document.getElementById('btt');
  const drawerTop = document.getElementById('drawerTop');
  const mApply   = document.getElementById('mApplyItem');
  const isMob    = () => window.innerWidth < 992;

  /* ─ Show/hide mobile-only elements ─ */
  function syncMobile(){
    const m = isMob();
    drawerTop.style.display = m ? 'block' : 'none';
    mApply.style.display    = m ? 'block' : 'none';
  }
  syncMobile();
  window.addEventListener('resize', syncMobile, {passive:true});

  /* ─ Drawer open/close ─ */
  function open(){
    navList.classList.add('open');
    backdrop.classList.add('show');
    menuBtn.classList.add('open');
    menuBtn.setAttribute('aria-expanded','true');
    document.body.style.overflow = 'hidden';
  }
  function close(){
    navList.classList.remove('open');
    backdrop.classList.remove('show');
    menuBtn.classList.remove('open');
    menuBtn.setAttribute('aria-expanded','false');
    document.body.style.overflow = '';
    navList.querySelectorAll('.dropdown.open').forEach(d=>d.classList.remove('open'));
    navList.querySelectorAll('.nav-link.drop-open').forEach(l=>l.classList.remove('drop-open'));
  }

  menuBtn.addEventListener('click', ()=> navList.classList.contains('open') ? close() : open());
  backdrop.addEventListener('click', close);

  /* ─ Mobile accordion ─ */
  navList.querySelectorAll('.nav-link.has-drop').forEach(toggle=>{
    toggle.addEventListener('click', function(e){
      if(!isMob()) return;
      e.preventDefault(); e.stopPropagation();
      const drop   = this.closest('.drop-wrap').querySelector('.dropdown');
      const isOpen = drop.classList.contains('open');
      navList.querySelectorAll('.dropdown.open').forEach(d=>d.classList.remove('open'));
      navList.querySelectorAll('.nav-link.drop-open').forEach(l=>l.classList.remove('drop-open'));
      if(!isOpen){ drop.classList.add('open'); this.classList.add('drop-open'); }
    });
  });

  /* ─ Close drawer on leaf link tap ─ */
  navList.querySelectorAll('a:not(.has-drop)').forEach(a=>{
    a.addEventListener('click',()=>{ if(isMob()) setTimeout(close, 110); });
  });

  /* ─ Scroll: hide on down, show on up ─ */
  let lastY = 0;
  window.addEventListener('scroll',()=>{
    const y = window.scrollY;
    header.classList.toggle('scrolled', y > 10);
    if(!isMob()) header.classList.toggle('hide', y > 80 && y > lastY);
    btt.classList.toggle('show', y > 320);
    lastY = y;
  },{passive:true});

  /* ─ Back to top ─ */
  btt.addEventListener('click',()=> window.scrollTo({top:0,behavior:'smooth'}));
})();