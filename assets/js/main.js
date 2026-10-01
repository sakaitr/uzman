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
  // Theme: the admin picks the site theme; visitors can only switch when the optional switcher is enabled.
  const switcher = root.dataset.switcher === '1';
  const queryTheme = switcher ? new URLSearchParams(location.search).get('theme') : null;
  const themes = ['noir', 'bordeaux', 'emerald', 'twotone', 'inverse'];
  const initial = themes.includes(queryTheme) ? queryTheme : (switcher && safe(() => localStorage.getItem(THEME_KEY))) || root.getAttribute('data-theme');
  if (themes.includes(initial)) root.setAttribute('data-theme', initial);
  document.querySelectorAll('[data-set]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.set === root.getAttribute('data-theme'))));
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

  /* ---------- Measurement: cookie-less hit beacon, campaign attribution, optional trackers (with consent) ---------- */
  const cfgEl = document.getElementById('uz-cfg');
  const CFG = cfgEl ? safe(() => JSON.parse(cfgEl.textContent)) : null;
  const ATTR_EMPTY = { s: '', m: '', c: '', t: '', x: '', id: '', r: '' };
  let sess = { ...ATTR_EMPTY, lp: location.pathname }, ft = null, trackLead = () => {};
  if (CFG) {
    const qs = new URLSearchParams(location.search);
    const refHost = safe(() => { const h = new URL(document.referrer).hostname.replace(/^www\./, ''); return h && h !== location.hostname.replace(/^www\./, '') ? h : ''; }) || '';
    const cur = { s: qs.get('utm_source') || '', m: qs.get('utm_medium') || '', c: qs.get('utm_campaign') || '', t: qs.get('utm_term') || '', x: qs.get('utm_content') || '', id: qs.get('gclid') ? 'gclid' : qs.get('fbclid') ? 'fbclid' : qs.get('msclkid') ? 'msclkid' : '', r: refHost, lp: location.pathname };
    const signal = !!(cur.s || cur.m || cur.c || cur.id || cur.r);
    const stored = safe(() => JSON.parse(sessionStorage.getItem('uz-sess')));
    let isNew = false;
    if (stored) sess = stored; else { sess = signal ? cur : { ...ATTR_EMPTY, lp: location.pathname }; isNew = true; safe(() => sessionStorage.setItem('uz-sess', JSON.stringify(sess))); }
    ft = safe(() => JSON.parse(localStorage.getItem('uz-ft')));
    if (ft && Date.now() - ft.ts > 30 * 864e5) ft = null;
    if (!ft && signal) { ft = { ...cur, ts: Date.now() }; safe(() => localStorage.setItem('uz-ft', JSON.stringify(ft))); }
    // privacy-friendly page-view counter (no cookies, no identifiers); respects Do-Not-Track
    if (CFG.hit && navigator.doNotTrack !== '1' && !safe(() => localStorage.getItem('uz-notrack'))) {
      const fd = new FormData();
      fd.append('s', CFG.slug); fd.append('l', CFG.lang); fd.append('n', isNew ? '1' : '0'); fd.append('r', sess.r); fd.append('us', sess.s); fd.append('um', sess.m); fd.append('uc', sess.c); fd.append('c', sess.id);
      const send = () => { if (!(navigator.sendBeacon && navigator.sendBeacon(CFG.hit, fd))) fetch(CFG.hit, { method: 'POST', body: fd, keepalive: true }).catch(() => {}); };
      (window.requestIdleCallback || ((f) => setTimeout(f, 1200)))(send);
    }
    // optional third-party trackers (GTM / GA4 / Google Ads / Meta Pixel) — started only after consent
    const hasTrackers = !!(CFG.gtm || CFG.ga4 || CFG.gads || CFG.meta);
    const loadJs = (src) => { const el = document.createElement('script'); el.async = true; el.src = src; document.head.appendChild(el); };
    const startTrackers = () => {
      if (window.__uzTrk) return; window.__uzTrk = 1; window.dataLayer = window.dataLayer || [];
      if (CFG.gtm) { window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' }); loadJs('https://www.googletagmanager.com/gtm.js?id=' + CFG.gtm); }
      const gid = CFG.ga4 || CFG.gads;
      if (gid) { window.gtag = function () { window.dataLayer.push(arguments); }; window.gtag('js', new Date()); if (CFG.ga4) window.gtag('config', CFG.ga4); if (CFG.gads) window.gtag('config', CFG.gads); loadJs('https://www.googletagmanager.com/gtag/js?id=' + gid); }
      if (CFG.meta) {
        /* eslint-disable */ !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js'); /* eslint-enable */
        window.fbq('init', CFG.meta); window.fbq('track', 'PageView');
      }
    };
    trackLead = () => {
      if (!window.__uzTrk) return;
      window.dataLayer.push({ event: 'generate_lead' });
      if (window.gtag) { window.gtag('event', 'generate_lead'); if (CFG.gads && CFG.gadsl) window.gtag('event', 'conversion', { send_to: CFG.gads + '/' + CFG.gadsl }); }
      if (window.fbq) window.fbq('track', 'Lead');
    };
    if (hasTrackers) {
      const bar = document.getElementById('consentBar');
      const decided = safe(() => localStorage.getItem('uz-consent'));
      if (!CFG.consent || decided === 'yes') startTrackers(); else if (bar && !decided) bar.hidden = false;
      document.querySelectorAll('#consentBar [data-c]').forEach((b) => b.addEventListener('click', () => {
        safe(() => localStorage.setItem('uz-consent', b.dataset.c)); bar.hidden = true; if (b.dataset.c === 'yes') startTrackers();
      }));
      document.querySelectorAll('[data-consent-open]').forEach((b) => b.addEventListener('click', () => { if (bar) bar.hidden = false; }));
    }
  }

  /* ---------- Forms ---------- */
  document.querySelectorAll('form[data-form]').forEach((form) => {
    const status = form.querySelector('.form-status');
    const btn = form.querySelector('button[type=submit]');
    const t0 = Date.now();
    // the signed token is requested as soon as the page loads (the server rejects machine-speed submissions)
    let tokenP = null, tokenAt = 0;
    const getToken = () => {
      if (!tokenP || Date.now() - tokenAt > 3000000) {
        tokenAt = Date.now();
        tokenP = fetch(form.dataset.action.replace('quote.php', 'token.php'), { credentials: 'same-origin' }).then((r) => r.json()).then((j) => j.token);
      }
      return tokenP;
    };
    getToken().catch(() => { tokenP = null; });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      status.classList.remove('err');
      const bad = [...form.querySelectorAll('[required]')].filter((f) => !f.checkValidity());
      form.querySelectorAll('[required]').forEach((f) => f.setAttribute('aria-invalid', String(bad.includes(f))));
      if (bad.length) { status.textContent = form.dataset.err; status.classList.add('err'); bad[0].focus(); return; }
      btn.disabled = true;
      const data = new FormData(form);
      data.append('lang', form.dataset.lang || 'tr');
      data.append('elapsed', String(Date.now() - t0));
      const at = (sess.s || sess.m || sess.c || sess.id || sess.r) ? sess : (ft || sess);
      data.append('a_source', at.s || ''); data.append('a_medium', at.m || ''); data.append('a_campaign', at.c || ''); data.append('a_term', at.t || ''); data.append('a_content', at.x || '');
      data.append('a_cid', at.id || ''); data.append('a_ref', at.r || ''); data.append('a_landing', at.lp || location.pathname);
      try {
        data.append('token', await getToken());
        const res = await fetch(form.dataset.action, { method: 'POST', body: data, credentials: 'same-origin' });
        const j = await res.json();
        if (!res.ok || !j.ok) throw new Error(j.error || 'fail');
        status.textContent = form.dataset.ok;
        trackLead();
        form.reset();
      } catch (err) {
        status.textContent = form.dataset.fail || form.dataset.err;
        status.classList.add('err');
      } finally { btn.disabled = false; }
    });
  });
})();
