(() => {
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  let lang = 'tr';
  try { lang = localStorage.getItem('uz-admin-lang') || 'tr'; } catch (e) {}

  /* ---- language tabs (synced across the page) ---- */
  function showLang(l) {
    lang = l; try { localStorage.setItem('uz-admin-lang', l); } catch (e) {}
    $$('.ml-pane').forEach((p) => p.classList.toggle('on', p.dataset.l === l));
    $$('.ml-tabs button, .langbar button').forEach((b) => b.classList.toggle('on', b.dataset.l === l));
  }
  function markEmpty() {
    $$('.ml').forEach((m) => $$('.ml-pane', m).forEach((p) => {
      const inp = $('input,textarea', p); const tab = $(`.ml-tabs button[data-l="${p.dataset.l}"]`, m);
      if (tab && inp) tab.classList.toggle('empty', !inp.value.trim() && m.dataset.req !== '0');
    }));
  }
  document.addEventListener('click', (e) => {
    const b = e.target.closest('.ml-tabs button, .langbar button'); if (b) { e.preventDefault(); showLang(b.dataset.l); }
  });
  document.addEventListener('input', (e) => { if (e.target.closest('.ml')) markEmpty(); dirty = true; });
  showLang(lang); markEmpty();

  /* ---- confirm + unsaved ---- */
  let dirty = false;
  document.addEventListener('change', (e) => { if (e.target.closest('form[data-guard]')) dirty = true; });
  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  document.addEventListener('submit', () => { dirty = false; });
  document.addEventListener('click', (e) => {
    const c = e.target.closest('[data-confirm]'); if (c && !confirm(c.dataset.confirm)) e.preventDefault();
  });

  /* ---- sortable rows ---- */
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-move]'); if (!b) return; e.preventDefault();
    const row = b.closest('[data-row]'); if (!row) return;
    const sib = b.dataset.move === 'up' ? row.previousElementSibling : row.nextElementSibling;
    if (sib && sib.hasAttribute('data-row')) { b.dataset.move === 'up' ? sib.before(row) : sib.after(row); dirty = true; }
  });

  /* ---- media picker ---- */
  const modal = $('#picker');
  let target = null, cache = null;
  const csrf = (document.querySelector('meta[name=csrf]') || {}).content;
  async function gal() {
    if (!cache) cache = await (await fetch('index.php?a=media_json')).json();
    const q = ($('#pk-q').value || '').toLowerCase();
    $('#pk-gal').innerHTML = cache.filter((m) => !q || m.name.toLowerCase().includes(q) || m.path.toLowerCase().includes(q)).map((m) =>
      `<button type="button" data-path="${m.path}"><img src="../${m.path}" loading="lazy" alt=""><span>${m.name}</span></button>`).join('');
  }
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-pick]');
    if (o) { target = o.closest('.img-in'); modal.classList.add('open'); gal(); return; }
    const c = e.target.closest('[data-clear]'); if (c) { const w = c.closest('.img-in'); $('input[type=text]', w).value = ''; $('.pv', w).innerHTML = ''; dirty = true; return; }
    const g = e.target.closest('#pk-gal button');
    if (g && target) { setImg(target, g.dataset.path); modal.classList.remove('open'); return; }
    if (e.target.closest('[data-close]') || e.target === modal) modal.classList.remove('open');
  });
  function setImg(w, path) { $('input[type=text]', w).value = path; $('.pv', w).innerHTML = /\.pdf$/i.test(path) ? 'PDF' : `<img src="../${path}" alt="">`; dirty = true; }
  if (modal) {
    $('#pk-q').addEventListener('input', gal);
    $('#pk-up').addEventListener('change', async (e) => {
      const f = e.target.files[0]; if (!f) return;
      const fd = new FormData(); fd.append('file', f); fd.append('_csrf', csrf);
      const r = await fetch('index.php?a=upload', { method: 'POST', body: fd }); const j = await r.json();
      if (j.error) { alert(j.error); return; }
      cache = null; if (target) { setImg(target, j.path); modal.classList.remove('open'); } e.target.value = '';
    });
  }

  /* ---- block editor ---- */
  let uid = Date.now() % 100000;
  const fill = (html) => html.replace(/__B__/g, 'n' + (++uid));
  const blocks = $('#blocks');
  if (blocks) {
    document.addEventListener('click', (e) => {
      const add = e.target.closest('[data-addblock]');
      if (add) { e.preventDefault(); const t = $(`#tpl-${add.dataset.addblock}`); blocks.insertAdjacentHTML('beforeend', fill(t.innerHTML)); showLang(lang); markEmpty(); dirty = true; $$('.block', blocks).pop().scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
      const ai = e.target.closest('[data-additem]');
      if (ai) { e.preventDefault(); const holder = $('.items', ai.closest('.block')); const t = $(`#tpl-item-${ai.dataset.additem}`); const n = 'i' + (++uid); holder.insertAdjacentHTML('beforeend', t.innerHTML.replace(/__B__/g, ai.closest('.block').dataset.bid).replace(/__I__/g, n)); showLang(lang); markEmpty(); dirty = true; return; }
    });
  }
  document.addEventListener('click', (e) => {
    const del = e.target.closest('[data-del]'); if (del) { e.preventDefault(); if (confirm('Silinsin mi?')) { del.closest(del.dataset.del).remove(); dirty = true; } return; }
    const ar = e.target.closest('[data-addrow]');
    if (ar) { e.preventDefault(); const t = document.getElementById(ar.dataset.addrow); document.getElementById(ar.dataset.into).insertAdjacentHTML('beforeend', t.innerHTML.replace(/__N__/g, String(++uid))); showLang(lang); markEmpty(); dirty = true; }
  });

  /* ---- brand colour preview (mirrors app/brand.php derivation) ---- */
  const bf = document.getElementById('brandForm');
  if (bf) {
    const rgb = (h) => [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16));
    const hex = (c) => '#' + c.map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
    const mix = (a, b, t) => { const x = rgb(a), y = rgb(b); return hex(x.map((v, i) => v + (y[i] - v) * t)); };
    const lum = (h) => { const c = rgb(h).map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]; };
    const ratio = (a, b) => { const l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };
    const shift = (h, dl) => {
      let [r, g, b] = rgb(h).map((v) => v / 255); const mx = Math.max(r, g, b), mn = Math.min(r, g, b); let l = (mx + mn) / 2; const d = mx - mn; let hh = 0, sat = 0;
      if (d > 0) { sat = d / (1 - Math.abs(2 * l - 1)); hh = mx === r ? ((g - b) / d) % 6 : mx === g ? (b - r) / d + 2 : (r - g) / d + 4; hh *= 60; if (hh < 0) hh += 360; }
      l = Math.max(0.04, Math.min(0.96, l + dl)); const c = (1 - Math.abs(2 * l - 1)) * sat, x = c * (1 - Math.abs((hh / 60) % 2 - 1)), m = l - c / 2;
      const t = hh < 60 ? [c, x, 0] : hh < 120 ? [x, c, 0] : hh < 180 ? [0, c, x] : hh < 240 ? [0, x, c] : hh < 300 ? [x, 0, c] : [c, 0, x];
      return hex(t.map((v) => (v + m) * 255));
    };
    const rgba = (h, a) => { const [r, g, b] = rgb(h); return `rgba(${r},${g},${b},${a})`; };
    const prev = document.getElementById('brandPrev'), tb = document.querySelector('#brandContrast tbody');
    const upd = () => {
      const v = {}; bf.querySelectorAll('[data-seed]').forEach((i) => { v[i.dataset.seed] = i.value; const c = bf.querySelector(`[data-hex="${i.dataset.seed}"]`); if (c) c.textContent = i.value; });
      const on2 = bf.querySelector('[data-seed-on]').checked; const ac2 = on2 ? v.accent2 : v.accent;
      const light = lum(v.bg) >= 0.35;
      const hi = light ? shift(v.accent, -0.05) : shift(v.accent, 0.14), lo = shift(v.accent, -0.18);
      const onG = ratio(v.accent, '#14100a') >= ratio(v.accent, '#ffffff') ? '#14100a' : '#ffffff';
      const t = { '--bg': v.bg, '--text': v.text, '--muted': mix(v.text, v.bg, 0.36), '--gold': v.accent, '--gold-hi': hi, '--gold-lo': lo, '--on-gold': onG, '--line-strong': rgba(v.accent, light ? 0.52 : 0.38), '--line': rgba(v.accent, light ? 0.24 : 0.16) };
      Object.entries(t).forEach(([k, val]) => prev.style.setProperty(k, val));
      const rows = [['Yazı / zemin', ratio(v.text, v.bg), 4.5], ['Soluk yazı / zemin', ratio(t['--muted'], v.bg), 4.5], ['Bağlantı & vurgu yazısı / zemin', ratio(hi, v.bg), 4.5], ['Vurgu / zemin (çizgi, ikon)', ratio(v.accent, v.bg), 3], ['Düğme yazısı / vurgu', ratio(onG, v.accent), 4.5]];
      tb.innerHTML = rows.map(([l, r, m]) => `<tr><td>${l}</td><td style="text-align:right">${r.toFixed(2)}:1</td><td style="width:110px;text-align:right"><span class="pill ${r >= m ? 'ok' : 'warn'}">${r >= m ? 'Uygun' : 'Düşük (' + m + ')'}</span></td></tr>`).join('');
    };
    bf.addEventListener('input', upd); upd();
  }
  $$('form[data-guard]').forEach((f) => f.addEventListener('submit', () => { dirty = false; }));
})();
