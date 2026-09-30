(() => {
  const root = document.documentElement;
  root.classList.remove('no-js');
  const safe = (fn) => { try { return fn(); } catch { return null; } };

  /* ---------- Theme ---------- */
  const THEME_KEY = 'uzman-theme';
  const applyTheme = (name) => {
    root.setAttribute('data-theme', name);
    safe(() => localStorage.setItem(THEME_KEY, name));
    document.querySelectorAll('[data-set]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.set === name)));
  };
  const queryTheme = new URLSearchParams(location.search).get('theme');
  applyTheme(['noir', 'bordeaux', 'emerald', 'twotone', 'inverse'].includes(queryTheme) ? queryTheme : safe(() => localStorage.getItem(THEME_KEY)) || 'noir');

  applyTheme(root.getAttribute('data-theme'));
  document.querySelectorAll('[data-set]').forEach((b) => b.addEventListener('click', () => applyTheme(b.dataset.set)));

  /* ---------- Header behaviour ---------- */
  const siteHeader = document.getElementById('siteHeader');
  let lastY = 0;
  const onScroll = () => {
    const y = window.scrollY;
    siteHeader.classList.toggle('scrolled', y > 40);
    siteHeader.classList.toggle('hide', y > 500 && y > lastY + 4 && !document.body.classList.contains('menu-open'));
    if (y < lastY - 4) siteHeader.classList.remove('hide');
    lastY = y;
  };
  const bar = document.querySelector('.progress');
  const onProgress = () => { if (bar) bar.style.transform = `scaleX(${Math.min(1, window.scrollY / Math.max(1, document.body.scrollHeight - innerHeight))})`; };
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return; ticking = true;
    requestAnimationFrame(() => { onScroll(); onProgress(); ticking = false; });
  }, { passive: true }); onScroll(); onProgress();
  const burger = document.getElementById('burger');
  burger?.addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-open');
    burger.setAttribute('aria-expanded', String(open));
  });
  document.querySelectorAll('.mobile-menu a').forEach((a) => a.addEventListener('click', () => document.body.classList.remove('menu-open')));

  /* ---------- Reveal on scroll ---------- */
  const reveal = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); reveal.unobserve(e.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  document.querySelectorAll('.rv').forEach((el) => reveal.observe(el));

  /* ---------- Manifesto word-by-word ---------- */
  document.querySelectorAll('.manifesto p').forEach((p) => {
    const html = p.innerHTML.replace(/<em>(.*?)<\/em>/g, (_, t) => t.split(' ').map((w) => `<span class="w hl">${w}</span>`).join(' '));
    const tmp = document.createElement('div'); tmp.innerHTML = html;
    const walk = (node) => [...node.childNodes].forEach((n) => {
      if (n.nodeType === 3) {
        const frag = document.createDocumentFragment();
        n.textContent.split(/(\s+)/).forEach((t) => { if (!t.trim()) frag.append(t); else { const s = document.createElement('span'); s.className = 'w'; s.textContent = t; frag.append(s); } });
        n.replaceWith(frag);
      }
    });
    walk(tmp); p.innerHTML = tmp.innerHTML;
    const words = [...p.querySelectorAll('.w')];
    const update = () => {
      const r = p.getBoundingClientRect(), vh = window.innerHeight;
      const progress = Math.min(1, Math.max(0, (vh * 0.85 - r.top) / (r.height + vh * 0.25)));
      const n = Math.round(progress * words.length);
      words.forEach((w, i) => w.classList.toggle('on', i < n));
    };
    let t2 = false;
    window.addEventListener('scroll', () => { if (t2) return; t2 = true; requestAnimationFrame(() => { update(); t2 = false; }); }, { passive: true }); update();
  });

  /* ---------- Ambient videos: load near viewport, pause off-screen ---------- */
  const RATE = .6; // ambient clips play slower for a calmer feel
  const ambient = [...document.querySelectorAll('.vbg video')];
  ambient.forEach((v) => { v.defaultPlaybackRate = RATE; v.playbackRate = RATE; });
  const saveData = navigator.connection && navigator.connection.saveData;
  if (ambient.length && !saveData && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const vio = new IntersectionObserver((entries) => entries.forEach((e) => {
      const v = e.target;
      if (e.isIntersecting) {
        if (!v.getAttribute('src')) { v.muted = true; v.src = (v.canPlayType('video/mp4; codecs="avc1.42E01E"') || !v.canPlayType('video/webm; codecs="vp9"')) ? v.dataset.src : v.dataset.webm; }
        v.defaultPlaybackRate = RATE; v.playbackRate = RATE;
        v.play().catch(() => {});
      } else v.pause();
    }), { rootMargin: '240px' });
    ambient.forEach((v) => vio.observe(v));
  }

  /* ---------- Hero slider ---------- */
  const slides = [...document.querySelectorAll('.hero .slide')], dots = [...document.querySelectorAll('.hero .dot')];
  if (slides.length > 1) {
    const veil = document.querySelector('.hero .vbg-fg');
    const ms = 4960, still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let cur = 0, timer;
    const setVeil = (i) => { if (veil) veil.style.setProperty('--m', `url("${new URL(slides[i].dataset.m, location.href).href}")`); };
    setVeil(0);
    document.querySelector('.slide-nav').style.setProperty('--slide-ms', ms + 'ms');
    const go = (n, first) => {
      n = (n + slides.length) % slides.length;
      if (n === cur && !first) return;
      const prev = slides[cur];
      prev.classList.remove('on'); prev.classList.add('out');
      setTimeout(() => prev.classList.remove('out'), 1400);
      if (veil) { veil.classList.add('off'); setTimeout(() => { setVeil(n); veil.classList.remove('off'); }, 650); }
      slides[n].classList.add('on');
      dots.forEach((d, k) => { d.classList.toggle('on', k === n); d.setAttribute('aria-selected', k === n); });
      cur = n;
    };
    const start = () => { clearInterval(timer); if (!still) timer = setInterval(() => go(cur + 1), ms); };
    dots.forEach((d, k) => d.addEventListener('click', () => { go(k); start(); }));
    document.addEventListener('visibilitychange', () => document.hidden ? clearInterval(timer) : start());
    start();
  }

  /* ---------- Hero parallax ---------- */
  const stage = document.querySelector('.hero-stage'), bottle = document.querySelector('.hero-bottle');
  if (stage && bottle && matchMedia('(hover:hover)').matches) {
    stage.addEventListener('mousemove', (e) => {
      const r = stage.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      bottle.style.transform = `perspective(900px) rotateY(${x * 14}deg) rotateX(${-y * 10}deg) translate(${x * 12}px, ${y * 12}px)`;
    });
    stage.addEventListener('mouseleave', () => { bottle.style.transform = ''; });
  }

  /* ---------- Gallery filter (private label tubes) ---------- */
  const filterBtns = document.querySelectorAll('.filters button');
  filterBtns.forEach((b) => b.addEventListener('click', () => {
    filterBtns.forEach((x) => x.classList.toggle('on', x === b));
    document.querySelectorAll('.tcard').forEach((it) => { it.hidden = b.dataset.f !== 'all' && it.dataset.cat !== b.dataset.f; });
  }));

  /* ---------- Lightbox ---------- */
  const lightLinks = [...document.querySelectorAll('a[data-lb]')];
  if (lightLinks.length) {
    const lb = document.createElement('div');
    lb.className = 'lb'; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true');
    lb.innerHTML = `<button class="lb-x" aria-label="${{ tr: 'Kapat', en: 'Close', fr: 'Fermer', ar: 'إغلاق', ru: 'Закрыть' }[document.documentElement.lang] || 'Close'}"></button><img alt=""><div class="lb-cap"></div>`;
    document.body.append(lb);
    const lbImg = lb.querySelector('img'), lbCap = lb.querySelector('.lb-cap');
    const closeLb = () => { lb.classList.remove('open'); document.body.style.overflow = ''; };
    lightLinks.forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault(); lbImg.src = a.getAttribute('href'); lbImg.alt = a.dataset.cap || ''; lbCap.textContent = a.dataset.cap || '';
      lb.classList.add('open'); document.body.style.overflow = 'hidden'; lb.querySelector('.lb-x').focus();
    }));
    lb.addEventListener('click', (e) => { if (e.target !== lbImg) closeLb(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLb(); });
  }

  /* ---------- Forms ---------- */
  document.querySelectorAll('form[data-form]').forEach((form) => {
    const status = form.querySelector('.form-status');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const bad = [...form.querySelectorAll('[required]')].filter((f) => !f.checkValidity());
      form.querySelectorAll('[required]').forEach((f) => f.setAttribute('aria-invalid', String(bad.includes(f))));
      if (bad.length) { status.textContent = form.dataset.err; bad[0].focus(); return; }
      // TODO: backend / e-posta servisi bağlanacak (demo aşamasında yalnızca onay gösterilir)
      status.textContent = form.dataset.ok;
      form.reset();
    });
  });
})();
