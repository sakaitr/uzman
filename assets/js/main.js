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
  applyTheme(['noir', 'bordeaux', 'emerald'].includes(queryTheme) ? queryTheme : safe(() => localStorage.getItem(THEME_KEY)) || 'noir');

  /* ---------- SVG sprite (product art, themed via CSS variables) ---------- */
  const sprite = `
  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <linearGradient id="g-gold" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" style="stop-color:var(--gold-hi)"/><stop offset=".55" style="stop-color:var(--gold)"/><stop offset="1" style="stop-color:var(--gold-lo)"/>
      </linearGradient>
      <linearGradient id="g-metal" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" style="stop-color:var(--gold-lo)"/><stop offset=".3" style="stop-color:var(--gold)"/><stop offset=".48" style="stop-color:var(--gold-hi)"/><stop offset=".72" style="stop-color:var(--gold-lo)"/><stop offset="1" style="stop-color:var(--glass)"/>
      </linearGradient>
      <linearGradient id="g-glass" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#fff" stop-opacity=".16"/><stop offset=".22" stop-color="#fff" stop-opacity=".02"/><stop offset=".8" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#fff" stop-opacity=".08"/>
      </linearGradient>
      <linearGradient id="g-liquid" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" style="stop-color:var(--liquid)" stop-opacity=".95"/><stop offset="1" style="stop-color:var(--liquid)" stop-opacity=".45"/>
      </linearGradient>
    </defs>
    <symbol id="perfume" viewBox="0 0 200 320">
      <rect x="66" y="6" width="68" height="78" rx="5" fill="url(#g-gold)"/>
      <rect x="74" y="12" width="6" height="66" fill="#fff" opacity=".28"/>
      <rect x="82" y="84" width="36" height="20" fill="var(--glass)" stroke="var(--gold)" stroke-width="1.2"/>
      <rect x="18" y="104" width="164" height="208" rx="16" fill="var(--glass)" stroke="url(#g-gold)" stroke-width="2"/>
      <path d="M20 196 Q100 184 180 196 L180 296 Q180 310 166 310 L34 310 Q20 310 20 296Z" fill="url(#g-liquid)"/>
      <rect x="18" y="104" width="164" height="208" rx="16" fill="url(#g-glass)"/>
      <rect x="44" y="176" width="112" height="92" fill="var(--glass)" stroke="var(--gold)" stroke-width="1"/>
      <rect x="49" y="181" width="102" height="82" fill="none" stroke="var(--gold)" stroke-opacity=".35" stroke-width=".6"/>
      <text x="100" y="208" text-anchor="middle" font-family="Manrope,sans-serif" font-size="8" letter-spacing="4" fill="var(--gold)" font-weight="600">ATELIER</text>
      <text x="100" y="234" text-anchor="middle" font-family="Cormorant Garamond,serif" font-size="24" fill="var(--text)" font-style="italic">Extrait</text>
      <text x="100" y="252" text-anchor="middle" font-family="Manrope,sans-serif" font-size="6" letter-spacing="2.5" fill="var(--muted)">100 ML · PARFUM</text>
      <line x1="30" y1="118" x2="30" y2="298" stroke="#fff" stroke-opacity=".28" stroke-width="2" stroke-linecap="round"/>
    </symbol>
    <symbol id="aerosol" viewBox="0 0 120 320">
      <rect x="44" y="6" width="32" height="10" rx="3" fill="var(--gold)"/>
      <path d="M32 16h56v42H32z" fill="url(#g-metal)"/>
      <rect x="28" y="58" width="64" height="8" fill="var(--gold-lo)"/>
      <path d="M22 76 Q22 66 60 66 Q98 66 98 76 L98 296 Q98 314 60 314 Q22 314 22 296Z" fill="url(#g-metal)"/>
      <rect x="22" y="130" width="76" height="120" fill="var(--glass)" opacity=".92"/>
      <line x1="22" y1="130" x2="98" y2="130" stroke="var(--gold-hi)" stroke-width="1.4"/>
      <line x1="22" y1="250" x2="98" y2="250" stroke="var(--gold-hi)" stroke-width="1"/>
      <text x="60" y="160" text-anchor="middle" font-family="Manrope,sans-serif" font-size="6.5" letter-spacing="3" fill="var(--gold)" font-weight="700">UZMAN</text>
      <text x="60" y="190" text-anchor="middle" font-family="Cormorant Garamond,serif" font-size="21" fill="var(--text)" font-style="italic">Noir</text>
      <text x="60" y="208" text-anchor="middle" font-family="Manrope,sans-serif" font-size="5.5" letter-spacing="2" fill="var(--muted)">DEODORANT</text>
      <text x="60" y="236" text-anchor="middle" font-family="Manrope,sans-serif" font-size="6" letter-spacing="2" fill="var(--gold-hi)">150 ML</text>
      <rect x="44" y="84" width="9" height="210" fill="#fff" opacity=".22"/>
    </symbol>
    <symbol id="diffuser" viewBox="0 0 200 320">
      <g stroke="var(--gold-hi)" stroke-width="2.4" stroke-linecap="round" opacity=".9">
        <line x1="92" y1="150" x2="58" y2="8"/><line x1="100" y1="150" x2="96" y2="0"/><line x1="108" y1="150" x2="140" y2="10"/><line x1="96" y1="150" x2="76" y2="20" stroke-opacity=".6"/><line x1="104" y1="150" x2="120" y2="18" stroke-opacity=".6"/>
      </g>
      <rect x="84" y="140" width="32" height="30" fill="var(--glass)" stroke="var(--gold)" stroke-width="1.4"/>
      <rect x="78" y="134" width="44" height="10" rx="2" fill="url(#g-gold)"/>
      <path d="M84 170 Q22 180 22 236 L22 292 Q22 314 44 314 L156 314 Q178 314 178 292 L178 236 Q178 180 116 170Z" fill="var(--glass)" stroke="url(#g-gold)" stroke-width="2"/>
      <path d="M24 226 Q100 214 176 226 L176 292 Q176 312 156 312 L44 312 Q24 312 24 292Z" fill="url(#g-liquid)"/>
      <path d="M84 170 Q22 180 22 236 L22 292 Q22 314 44 314 L156 314 Q178 314 178 292 L178 236 Q178 180 116 170Z" fill="url(#g-glass)"/>
      <rect x="56" y="240" width="88" height="52" fill="var(--glass)" stroke="var(--gold)" stroke-width=".9"/>
      <text x="100" y="262" text-anchor="middle" font-family="Cormorant Garamond,serif" font-size="17" fill="var(--text)" font-style="italic">Maison</text>
      <text x="100" y="278" text-anchor="middle" font-family="Manrope,sans-serif" font-size="5.5" letter-spacing="3" fill="var(--gold)">AMBIANCE</text>
    </symbol>
    <symbol id="jar" viewBox="0 0 200 240">
      <rect x="30" y="16" width="140" height="62" rx="6" fill="url(#g-gold)"/>
      <rect x="40" y="22" width="7" height="50" fill="#fff" opacity=".28"/>
      <path d="M38 84h124v6c12 6 16 18 16 34v72c0 22-14 32-34 32H56c-20 0-34-10-34-32v-72c0-16 4-28 16-34z" fill="var(--glass)" stroke="url(#g-gold)" stroke-width="2"/>
      <path d="M22 146h156v50c0 22-14 32-34 32H56c-20 0-34-10-34-32z" fill="url(#g-liquid)" opacity=".7"/>
      <path d="M38 84h124v6c12 6 16 18 16 34v72c0 22-14 32-34 32H56c-20 0-34-10-34-32v-72c0-16 4-28 16-34z" fill="url(#g-glass)"/>
      <text x="100" y="160" text-anchor="middle" font-family="Manrope,sans-serif" font-size="7" letter-spacing="4" fill="var(--gold-hi)" font-weight="600">UZMAN</text>
      <text x="100" y="186" text-anchor="middle" font-family="Cormorant Garamond,serif" font-size="24" fill="var(--text)" font-style="italic">Cire</text>
      <text x="100" y="202" text-anchor="middle" font-family="Manrope,sans-serif" font-size="5.5" letter-spacing="2.5" fill="var(--muted)">HAIR WAX · 100 ML</text>
    </symbol>
  </svg>`;
  const viewBoxes = { perfume: '0 0 200 320', aerosol: '0 0 120 320', diffuser: '0 0 200 320', jar: '0 0 200 240' };
  const art = (id, cls = '') => `<svg class="${cls}" viewBox="${viewBoxes[id]}" role="img" aria-label="${id}"><use href="#${id}"/></svg>`;
  window.uzmanArt = art;

  document.body.insertAdjacentHTML('afterbegin', sprite);
  applyTheme(root.getAttribute('data-theme'));
  document.querySelectorAll('[data-set]').forEach((b) => b.addEventListener('click', () => applyTheme(b.dataset.set)));

  /* Product art slots: <span data-art="perfume">…</span> */
  document.querySelectorAll('[data-art]').forEach((el) => { el.innerHTML = art(el.dataset.art, el.dataset.cls || ''); });

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
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
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
    window.addEventListener('scroll', update, { passive: true }); update();
  });

  /* ---------- Counters ---------- */
  const lang = root.lang || 'tr';
  const fmt = new Intl.NumberFormat(lang === 'ar' ? 'ar-u-nu-latn' : lang);
  const counter = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return; counter.unobserve(e.target);
      const el = e.target, end = +el.dataset.count, dur = 1800, t0 = performance.now();
      const tick = (t) => { const k = Math.min(1, (t - t0) / dur), v = Math.round(end * (1 - Math.pow(1 - k, 4))); el.firstChild.textContent = el.hasAttribute("data-plain") ? v : fmt.format(v); if (k < 1) requestAnimationFrame(tick); };
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.6 });
  document.querySelectorAll('[data-count]').forEach((el) => counter.observe(el));

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
    lb.innerHTML = '<button class="lb-x" aria-label="×">×</button><img alt=""><div class="lb-cap"></div>';
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
