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
  $$('form[data-guard]').forEach((f) => f.addEventListener('submit', () => { dirty = false; }));
})();
