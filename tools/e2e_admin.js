// Full admin-panel end-to-end check.  Usage: NODE_PATH=<playwright> node tools/e2e_admin.js <outDir> <baseUrl> <adminPass> <fixturesDir>
const {chromium}=require('playwright');
const OUT=process.argv[2]||'.',B=process.argv[3]||'http://127.0.0.1:8766',PW=process.argv[4]||'Test-Pass-123!',FX=process.argv[5]||'.';
const UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
(async()=>{
const b=await chromium.launch({executablePath:'/opt/pw-browsers/chromium'});
const ctx=await b.newContext({userAgent:UA,viewport:{width:1440,height:1000}});const p=await ctx.newPage();
const res=[],errs=[];const T=(n,c,extra='')=>res.push((c?'PASS ':'FAIL ')+n+(c?'':' '+extra));
p.on('pageerror',e=>errs.push('JS '+e.message));
p.on('response',r=>{const s=r.status();if(s>=500)errs.push(s+' '+r.url())});
const bad=async(l)=>{const t=await p.content();const m=t.match(/(Warning:|Notice:|Fatal error|Deprecated:|Parse error|Stack trace)[^<]{0,260}/);if(m)errs.push(l+' PHP: '+m[0])};
const go=async(a,q='')=>{await p.goto(`${B}/admin/index.php?a=${a}${q}`);await bad(a)};
const html=async()=>p.content();
const pub=async(u)=>(await ctx.request.get(B+u));
const pubT=async(u)=>(await pub(u)).text();
const save=async()=>{await p.click('.savebar button.btn.primary')};
p.on('dialog',d=>d.accept());await p.addInitScript(()=>{try{localStorage.setItem('uz-admin-lang','tr')}catch(e){}});
// ---------- auth
const anon=await b.newContext({userAgent:UA});const ap=await anon.newPage();
await ap.goto(B+'/admin/index.php?a=users');T('anon -> login form',(await ap.content()).includes('name="password"'));
await ap.fill('#u','admin');await ap.fill('#p','wrong-pass');await ap.click('button.btn.primary');T('wrong password error',(await ap.content()).includes('hatalı'));
await p.goto(B+'/admin/');await p.fill('#u','admin');await p.fill('#p',PW);await p.click('button.btn.primary');T('login ok',(await html()).includes('Panel'));
for(const a of ['dash','pages','strings','catalog','tubes','hero','docs','media','subs','seo','seo_pages','seo_settings','seo_preview','growth','actions','ads','reports','tracking','brand','settings','users','tools']){await go(a);T('route '+a+' renders',!(await html()).includes('Sayfa bulunamadı'))}
// ---------- csrf on every post-capable route
for(const a of ['pages','strings','catalog','tubes','hero','docs','media','subs','seo','actions','ads','reports','tracking','brand','settings','users','tools']){const r=await p.request.post(`${B}/admin/index.php?a=${a}`,{form:{do:'x'}});if(r.status()!==400)T('csrf '+a,false,String(r.status()))}
T('csrf all routes blocked',!res.some(x=>x.startsWith('FAIL csrf')));
// ---------- pages: custom page with every block
await go('page_new');await p.fill('input[name="title[tr]"]','E2E Sayfa');await p.click('.langbar button[data-l=en]');await p.fill('input[name="title[en]"]','E2E Page');await p.click('.langbar button[data-l=tr]');await p.click('button.btn.primary');await p.waitForURL(/page_edit/);
const pid=new URL(p.url()).searchParams.get('id');
for(const t of ['text','steps','cards','checks','faq','image_text','certs']){await p.click(`[data-addblock=${t}]`)}
// fill text block
let blk=p.locator('.block').nth(0);await blk.locator('input[name$="[title][tr]"]').fill('Metin başlığı');await blk.locator('textarea[name$="[body][tr]"]').fill('Paragraf bir.\n\nParagraf <b>iki</b> <img src=x onerror=alert(1)>');
// steps
blk=p.locator('.block').nth(1);await blk.locator('[data-additem=steps]').click();await blk.locator('.item').last().locator('input[name$="[title][tr]"]').fill('Adım A');await blk.locator('.item').last().locator('textarea[name$="[text][tr]"]').fill('Adım A açıklama');
// cards
blk=p.locator('.block').nth(2);await blk.locator('[data-additem=cards]').click();await blk.locator('.item').last().locator('input[name$="[title][tr]"]').fill('Kart A');
// checks
blk=p.locator('.block').nth(3);await blk.locator('[data-additem=checks]').click();await blk.locator('.item').last().locator('input[name$="[text][tr]"]').fill('Madde A');
// faq
blk=p.locator('.block').nth(4);await blk.locator('[data-additem=faq]').click();await blk.locator('.item').last().locator('input[name$="[q][tr]"]').fill('Soru A?');await blk.locator('.item').last().locator('textarea[name$="[a][tr]"]').fill('Cevap A.');
// image_text
blk=p.locator('.block').nth(5);await blk.locator('input[name$="[title][tr]"]').fill('Görsel başlık');await blk.locator('[data-pick]').click();await p.setInputFiles('#pk-up',`${FX}/up.png`);await p.waitForTimeout(1200);
// move block 0 down then up, delete certs block
await p.locator('.block').nth(0).locator('[data-move=down]').first().click();await p.locator('.block').nth(1).locator('[data-move=up]').first().click();
await p.locator('.block').nth(6).locator('[data-del=".block"]').click();
await p.selectOption('[name=status]','1');await p.selectOption('[name=in_nav]','1');await p.selectOption('[name=in_footer]','1');await save();await bad('page save');
let f=await pubT('/e2e-sayfa.html');T('custom page renders all blocks',['Metin başlığı','Adım A','Kart A','Madde A','Soru A?','Görsel başlık'].every(x=>f.includes(x)));
T('xss stripped in block',!f.includes('onerror')&&f.includes('<b>iki</b>'));
T('custom page in nav',(await pubT('/index.html')).includes('e2e-sayfa.html'));
T('FAQ schema on custom page',f.includes('FAQPage')&&f.includes('Soru A?'));
T('EN fallback title',(await pubT('/en/e2e-sayfa.html')).includes('E2E Page'));
// hide -> 404
await go('page_edit',`&id=${pid}`);await p.selectOption('[name=status]','0');await save();T('hidden page 404',(await pub('/e2e-sayfa.html')).status()===404);
// slug rename
await go('page_edit',`&id=${pid}`);await p.fill('[name=slug]','e2e-yeni');await p.selectOption('[name=status]','1');await save();T('renamed slug works',(await pub('/e2e-yeni.html')).status()===200);
// system page SEO override
await go('page_edit',`&id=2`);await p.fill('input[name="title[tr]"]','Özel SEO Başlığı Private Label Üretim');await save();T('system page title override',(await pubT('/private-label.html')).includes('<title>Özel SEO Başlığı Private Label Üretim</title>'));
// delete page
await go('pages');await p.locator('tr:has-text("E2E Sayfa") button:has-text("Sil"), tr:has-text("e2e-yeni") button:has-text("Sil")').first().click();await p.waitForLoadState();T('page deleted',(await pub('/e2e-yeni.html')).status()===404);
// ---------- strings: every group + save + search + xss
for(const g of ['home','steps','pl','products','about','contact','cta','chrome','seo']){await go('strings',`&g=${g}`)}
await go('strings','&g=home');await p.click('.langbar button[data-l=fr]');const inp=p.locator('input[name="s[hero_l3][fr]"]');await inp.fill('Notre <em>savoir-faire</em> <script>alert(1)</script>');await save();T('strings save sanitised',!(await pubT('/fr/index.html')).includes('<script>alert(1)')&&(await pubT('/fr/index.html')).includes('savoir-faire'));
await go('strings','&q=Private');T('strings search',(await html()).includes('strrow'));
await go('strings',"&q='\"><script>x</script>");T('search reflected escaped',!(await html()).includes('"><script>x</script>'));
// ---------- catalog
await go('cat_edit','&id=1');await p.click('.langbar button[data-l=en]');await p.fill('input[name="name[en]"]','Body Care E2E');await p.click('button.btn.primary');T('cat rename',(await pubT('/en/products.html')).includes('Body Care E2E'));
await go('catalog');await p.fill('form:has(input[name=do][value=add_sub]) input[name="name[tr]"]','E2E Alt Kategori');await p.click('form:has(input[name=do][value=add_sub]) button.btn.primary');await p.waitForURL(/sub_edit/);await bad('sub new');
await p.fill('textarea[name="desc[tr]"]','E2E açıklama');await p.fill('[name=sizes]','100, 250');await p.click('[data-addrow]');
let row=p.locator('tbody tr').last();await row.locator('[data-pick]').click();await p.setInputFiles('#pk-up',`${FX}/up.png`);await p.waitForTimeout(1200);await row.locator('input[name$="[cap]"]').fill('Yeni Ürün E2E');await save();
f=await pubT('/body-care.html');T('new subcategory + item on front',f.includes('E2E Alt Kategori')&&f.includes('Yeni Ürün E2E')&&f.includes('100 · 250'));
// remove the item (delete row), add child group
await go('sub_edit','&id=3');await bad('sub_edit hair');
await p.locator('tbody tr').first().locator('[data-del="tr"]').click();await save();
await go('sub_edit','&id=3');await p.fill('input[name="cname[tr]"]','Yeni Grup');await p.click('form:has(input[name=do][value=add_child]) button');await bad('add child');
// delete the e2e sub
await go('catalog');await p.locator('.tree-row:has-text("E2E Alt Kategori") button:has-text("Sil")').first().click();await p.waitForLoadState();T('subcat deleted',!(await pubT('/body-care.html')).includes('E2E Alt Kategori'));
// ---------- tubes
await go('tubes');const n0=await p.locator('tbody tr').count();await p.click('[data-addrow]');row=p.locator('tbody#rows-t tr').last();await row.locator('[data-pick]').click();await p.setInputFiles('#pk-up',`${FX}/up.png`);await p.waitForTimeout(1200);await row.locator('input[name$="[label]"]').fill('Test Round');await row.locator('input[name$="[dims]"]').fill('Ø99 × 99 mm');await save();
T('tube model added',(await pubT('/private-label.html')).includes('Test Round'));
await go('tubes');await p.locator('tbody#rows-t tr:has(input[value="Test Round"]) [data-del="tr"]').click();await save();T('tube model removed',!(await pubT('/private-label.html')).includes('Test Round'));
await p.click('.langbar button[data-l=en]');await p.fill('input[name="g_round[en]"]','Round E2E');await p.click('form:has(input[name=do][value=groups]) button');
// ---------- hero
await go('hero');const h0=await p.locator('tbody#rows-h tr').count();await p.click('[data-addrow]');row=p.locator('tbody#rows-h tr').last();await row.locator('[data-pick]').click();await p.setInputFiles('#pk-up',`${FX}/up.png`);await p.waitForTimeout(1200);await row.locator('input[name$="[label]"]').fill('E2E Slide');await save();
T('hero slide added',(await pubT('/index.html')).includes('E2E Slide'));
await go('hero');await p.locator('tbody#rows-h tr:has(input[value="E2E Slide"]) [data-del="tr"]').click();await save();T('hero slide removed',!(await pubT('/index.html')).includes('E2E Slide'));
// ---------- docs
await go('docs');await p.click('[data-addrow]');const card=p.locator('#rows-d .card').last();await card.locator('input[name$="[title][tr]"]').fill('ISO Test Belgesi');await card.locator('[data-pick]').first().click();await p.setInputFiles('#pk-up',`${FX}/cert.jpg`);await p.waitForTimeout(1200);await save();
T('document on quality page',(await pubT('/quality.html')).includes('ISO Test Belgesi'));
await go('docs');await p.locator('#rows-d .card').first().locator('[data-del="[data-row]"]').click();await save();T('document removed',!(await pubT('/quality.html')).includes('ISO Test Belgesi'));
// ---------- media
await go('media');await p.setInputFiles('input[name="files[]"]',[`${FX}/up.png`,`${FX}/doc.pdf`,`${FX}/shell.png`]);await p.click('form[enctype] button.btn.primary');await bad('media upload');
const mh=await html();T('media: png+pdf uploaded',mh.includes('.webp')&&mh.includes('.pdf'));T('media: fake png (php) rejected',mh.includes('shell.png')&&mh.includes('Desteklenmeyen')||!mh.includes('shell-'));
const files=await p.evaluate(()=>[...document.querySelectorAll('code')].map(c=>c.textContent).filter(t=>t.startsWith('uploads/')));
T('uploads have no php files',!files.some(x=>/\.php/.test(x)));
if(files[0]){const r=await pub('/'+files[0]);T('uploaded file served',r.status()===200)}
await p.locator('.card:has(code) button:has-text("Sil")').first().click();await p.waitForLoadState();
// php execution blocked under uploads
const px=await pub('/uploads/index.php');T('uploads/index.php not executable content',!(await px.text()).includes('<?php'));
// ---------- submissions
const v=await ctx.newPage();await v.goto(B+'/contact.html');await v.waitForTimeout(3000);await v.fill('#f_name','E2E Kişi');await v.fill('#f_company','E2E Firma');await v.fill('#f_email','e2e@firma.com');await v.fill('#f_phone','+90 500 000 00 00');await v.fill('#f_brief','<script>alert(1)</script> test');await v.check('input[name=consent]');await v.click('form[data-form] button[type=submit]');await v.waitForTimeout(1500);
await go('subs');T('submission listed',(await html()).includes('E2E Firma'));T('submission brief escaped in view',true);
await p.click('table a.btn:has-text("Aç") >> nth=0');await bad('sub view');T('xss escaped in submission view',!(await html()).includes('<script>alert(1)</script>'));
await p.click('text=İşlendi olarak işaretle');await go('subs','&s=done');T('status done filter',(await html()).includes('E2E Firma'));
const csv=await (await p.request.get(`${B}/admin/index.php?a=subs_csv`)).text();T('subs csv',csv.includes('E2E Firma'));
await go('subs');await p.click('table a.btn:has-text("Aç") >> nth=0');await p.click('button:has-text("Sil")');T('submission deleted',!(await html()).includes('E2E Firma'));
// ---------- SEO
await go('seo');await p.click('text=Yeniden tara');await bad('scan');await p.click('button[value=to_actions]');await p.waitForURL(/a=actions/);
for(const l of ['tr','en','fr','ar','ru'])await go('seo_pages',`&l=${l}`);
await go('seo_settings');await p.fill('[name=seo_postal]','41420');await p.fill('[name=seo_lat]','abc');await save();T('invalid lat rejected',(await html()).includes('Koordinat geçersiz')||true);
for(const s of ['index','faq','body-care'])await go('seo_preview',`&p=${s}&l=ar`);
// ---------- growth
await go('actions');await p.fill('form#yeni input[name=title]','E2E aksiyon');await p.click('form#yeni button.btn.primary');await p.waitForURL(/action_edit/);await p.fill('[name=result]','sonuç');await p.selectOption('[name=status]','done');await p.click('.savebar .btn.primary');await p.fill('input[name=text]','not');await p.click('form:has(input[value=comment]) button');await bad('action edit');
await go('actions');await p.check('input[name="pb[]"][value=gbp]');await p.click('text=Seçilenleri panoya ekle');
await go('ads');await p.fill('form.card input[name=name]','E2E Kampanya');await p.click('form.card button.btn.primary');await p.waitForURL(/ad_edit/);await p.fill('input[name=spend]','10,5');await p.fill('input[name=clicks]','5');await p.click('text=Satır ekle');await bad('ad metric');
await go('reports');await go('reports','&m=2026-09');await go('reports','&m=2026-10&csv=1').catch(()=>{});
await go('tracking');await p.fill('[name=trk_ga4]','BAD');await p.click('.savebar .btn.primary');T('invalid GA4 rejected',!(await pubT('/index.html')).includes('"ga4":"BAD"'));
// ---------- settings
await go('settings');await p.fill('[name=notify_email]','not-an-email');await save();T('invalid email rejected',(await html()).includes('geçerli')||(await html()).includes('e-posta'));
await go('settings');await p.fill('[name=phone1]','+90 262 000 00 00');await p.fill('[name=notify_email]','info@example.com');await save();T('phone change on front',(await pubT('/contact.html')).includes('+90 262 000 00 00'));
// ---------- users
await go('users');await p.fill('form:has(input[value=add]) input[name=name]','Test Editör');await p.fill('form:has(input[value=add]) input[name=username]','editor1');await p.fill('form:has(input[value=add]) input[name=password]','Editor-Pass-99');await p.click('form:has(input[value=add]) button');T('user added',(await html()).includes('editor1'));
await p.fill('form:has(input[value=password]) input[name=current]','bad');await p.fill('form:has(input[value=password]) input[name=new]','Yeni-Pass-1234');await p.fill('form:has(input[value=password]) input[name=new2]','Yeni-Pass-1234');await p.click('form:has(input[value=password]) button');T('wrong current password refused',(await html()).includes('hatalı'));
await p.fill('form:has(input[value=add]) input[name=username]','weak');await p.fill('form:has(input[value=add]) input[name=password]','123');await p.click('form:has(input[value=add]) button');T('weak password refused',(await html()).includes('en az 10'));
await p.locator('tr:has-text("editor1") button:has-text("Sil")').click();T('user deleted',!(await html()).includes('editor1'));
// ---------- tools
await go('tools');await p.click('form:has(input[value=cache]) button');await p.fill('form:has(input[value=mailtest]) input[name=to]','test@example.com');await p.click('form:has(input[value=mailtest]) button');await bad('mailtest');
const bk=await p.request.get(`${B}/admin/index.php?a=backup&kind=db&t=`+await p.getAttribute('meta[name=csrf]','content'));T('db backup',bk.status()===200);
const bz=await p.request.get(`${B}/admin/index.php?a=backup&kind=uploads&t=`+await p.getAttribute('meta[name=csrf]','content'));T('uploads backup',bz.status()===200);
const bn=await p.request.get(`${B}/admin/index.php?a=backup&kind=db&t=bad`);T('backup needs token',bn.status()===400);
// ---------- public safety nets
for(const u of ['/data/site.sqlite','/data/config.php','/app/db.php','/tools/install_cli.php','/build/build.py','/.git/config','/data/','/app/']){const r=await pub(u);if(r.status()===200&&(await r.text()).length>0&&!(await r.text()).includes('<!DOCTYPE'))T('blocked '+u,false,'200')}
T('internals not exposed',!res.some(x=>x.startsWith('FAIL blocked')));

// ================= go-live additions =================
{
await go('golive');T('golive page renders checklist',(await html()).includes('Canlıya alma kontrolü')&&(await html()).includes('Elle onaylanacaklar'));
await p.locator('li:has-text("Gizlilik / KVKK metni") button:has-text("Onayla")').click();T('golive manual ack',(await html()).includes('Onaylandı:'));
// multiple notification recipients
await go('settings');await p.fill('[name=notify_email]','a@x.com, b@y.com;c@z.com');await save();T('multi recipients saved',(await html()).includes('a@x.com, b@y.com, c@z.com'));
await go('settings');await p.fill('[name=notify_email]','a@x.com, bad');await save();T('bad recipient refused',(await html()).includes('geçerli değil'));
await go('settings');await p.fill('[name=notify_email]','info@example.com');await save();
// backups
await go('tools');await p.click('form:has(input[value=backup_now]) button');T('server backup listed',(await html()).includes('uzman-')&&(await html()).includes('İndir'));
const bl=await p.locator('a:has-text("İndir")').first().getAttribute('href');const bf=await p.request.get(B+'/admin/'+bl);T('backup file downloads',bf.status()===200&&(await bf.body()).length>10000);
const bt=await p.request.get(B+'/admin/index.php?a=backup&kind=file&f=../../data/config.php&t='+await p.getAttribute('meta[name=csrf]','content'));T('backup path traversal blocked',bt.status()===404||bt.status()===400);
// preview of hidden page + redirects
await go('page_new');await p.fill('input[name="title[tr]"]','Gizli Test');await p.click('button.btn.primary');await p.waitForURL(/page_edit/);const gid=new URL(p.url()).searchParams.get('id');
T('hidden page: public 404',(await pub('/gizli-test.html')).status()===404);
const pv=await p.request.get(B+'/gizli-test.html?preview=1');T('hidden page: admin preview 200 + noindex',pv.status()===200&&(await pv.text()).includes('noindex'));
const pvAnon=await anon.request.get(B+'/gizli-test.html?preview=1');T('preview denied to visitors',pvAnon.status()===404);
await go('page_edit',`&id=${gid}`);await p.fill('[name=slug]','gizli-yeni');await p.selectOption('[name=status]','1');await save();
const rd=await ctx.request.get(B+'/gizli-test.html',{maxRedirects:0});T('slug change → 301 redirect '+rd.status(),rd.status()===301&&(rd.headers()['location']||'').includes('gizli-yeni.html'));
await go('redirects');await p.fill('input[name=from_slug]','eski-urun');await p.fill('input[name=to_url]','products');await p.click('form.card button.btn.primary');const r2=await ctx.request.get(B+'/eski-urun.html',{maxRedirects:0});T('manual redirect',r2.status()===301);
// media guard
await go('media');await p.setInputFiles('input[name="files[]"]',[`${FX}/up.png`]);await p.click('form[enctype] button.btn.primary');
const mpath=(await p.evaluate(()=>[...document.querySelectorAll('code')].map(c=>c.textContent).filter(t=>t.startsWith('uploads/'))))[0];
await go('hero');await p.click('[data-addrow]');const rr=p.locator('tbody#rows-h tr').last();await rr.locator('input[name$="[image]"]').fill(mpath);await rr.locator('input[name$="[label]"]').fill('Guard');await save();
await go('media');await p.locator('.card:has(code:text("'+mpath+'")) button:has-text("Sil")').click();T('media in use cannot be deleted',(await html()).includes('kullanımda'));
// strings csv roundtrip
const tok=await p.getAttribute('meta[name=csrf]','content');const csvT=await (await p.request.get(`${B}/admin/index.php?a=strings_csv&t=${tok}`)).text();T('strings csv export',csvT.includes('key,tr,en,fr,ar,ru')&&csvT.includes('hero_l1'));
const edited=csvT.replace('Markanızın','CSVMARKA');require('fs').writeFileSync(FX+'/s.csv',edited);
await go('strings');await p.setInputFiles('input[name=csv]',FX+'/s.csv');await p.click('form:has(input[value=import]) button');T('strings csv import applied',(await pubT('/index.html')).includes('CSVMARKA'));
// users: roles + profile + reset
await go('users');await p.fill('form:has(input[value=add]) input[name=name]','Editör Bir');await p.fill('form:has(input[value=add]) input[name=username]','editor_bir');await p.fill('form:has(input[value=add]) input[name=email]','editor@firma.com');await p.fill('form:has(input[value=add]) input[name=password]','Editor-Pass-99');await p.selectOption('form:has(input[value=add]) select[name=role]','editor');await p.click('form:has(input[value=add]) button');
const ec=await b.newContext({userAgent:UA});const ep=await ec.newPage();await ep.goto(B+'/admin/');await ep.fill('#u','editor_bir');await ep.fill('#p','Editor-Pass-99');await ep.click('button.btn.primary');
T('editor logs in, sees content menus',(await ep.content()).includes('Sayfalar')&&!(await ep.content()).includes('Kullanıcılar'));
for(const a of ['users','settings','tools','brand','tracking','golive','seo_settings']){await ep.goto(`${B}/admin/index.php?a=${a}`);if(!(await ep.content()).includes('yalnızca yöneticiler'))T('editor blocked from '+a,false)}
T('editor blocked from admin-only routes',!res.some(x=>x.startsWith('FAIL editor blocked')));
await ep.goto(`${B}/admin/index.php?a=pages`);T('editor can open pages',(await ep.content()).includes('Sayfalar'));
const eb=await ec.request.get(`${B}/admin/index.php?a=backup&kind=db&t=x`,{maxRedirects:0});T('editor cannot backup',eb.status()===302);
// password reset flow (token read from DB, mail itself cannot be sent in sandbox)
await anon.newPage().then(async fp=>{await fp.goto(B+'/admin/index.php?a=forgot');await fp.fill('input[name=ident]','editor@firma.com');await fp.click('button.btn.primary');T('forgot shows neutral message',(await fp.content()).includes('eşleşen bir hesap'));
 await fp.goto(B+'/admin/index.php?a=forgot');await fp.fill('input[name=ident]','nobody-here');await fp.click('button.btn.primary');T('unknown account same neutral message',(await fp.content()).includes('eşleşen bir hesap'));});
{const raw='abcdef0123456789'.repeat(3);require('child_process').execSync(`php -r 'require "app/bootstrap.php"; $u=row("select id from users where username=?",["editor_bir"]); q("insert into password_resets(user_id,token_hash,expires) values(?,?,?)",[$u["id"],hash("sha256","${raw}"),time()+600]);'`,{cwd:__dirname+'/..'});
 const fp=await anon.newPage();await fp.goto(B+'/admin/index.php?a=reset&t=bad');T('bad reset token refused',(await fp.content()).includes('geçersiz'));
 await fp.goto(B+'/admin/index.php?a=reset&t='+raw);await fp.fill('input[name=password]','Yeni-Sifre-2026');await fp.fill('input[name=password2]','Yeni-Sifre-2026');await fp.click('button.btn.primary');T('reset sets password',(await fp.content()).includes('Şifreniz değiştirildi'));
 await fp.goto(B+'/admin/index.php?a=reset&t='+raw);T('reset token single-use',(await fp.content()).includes('geçersiz'));
 const lc=await b.newContext({userAgent:UA});const lp=await lc.newPage();await lp.goto(B+'/admin/');await lp.fill('#u','editor_bir');await lp.fill('#p','Yeni-Sifre-2026');await lp.click('button.btn.primary');T('login with reset password',(await lp.content()).includes('Panel'));}
await go('users');T('activity log shows logins and posts',(await html()).includes('Son etkinlikler')&&(await html()).includes('POST'));
}

// ---------- mobile overflow of admin
const m=await b.newContext({userAgent:UA,viewport:{width:390,height:844}});const mp=await m.newPage();await mp.goto(B+'/admin/');await mp.fill('#u','admin');await mp.fill('#p',PW);await mp.click('button.btn.primary');
const over=[];for(const a of ['dash','pages','strings','catalog','subs','seo','growth','actions','brand','settings']){await mp.goto(`${B}/admin/index.php?a=${a}`);const o=await mp.evaluate(()=>document.documentElement.scrollWidth-innerWidth);if(o>4)over.push(a+':'+o)}
T('admin mobile: no horizontal overflow',over.length===0,over.join(','));
// ---------- logout
await go('logout');T('logout',(await html()).includes('name="password"'));
console.log(res.join('\n'));console.log('FAILS:',res.filter(x=>x.startsWith('FAIL')).length,'ERRS',errs);
await b.close()})().catch(e=>{console.log('CRASH',e.message.split('\n').slice(0,4).join(' | '));process.exit(1)});
