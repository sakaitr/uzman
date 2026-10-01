<?php
declare(strict_types=1);

require_once UZ_APP . '/admin/pages.php';

function seo_tabs(string $active): string
{
    $tabs = ['seo' => 'Genel bakış ve skor', 'seo_pages' => 'Sayfa bazlı durum', 'seo_settings' => 'Ayarlar', 'seo_preview' => 'Önizleme (robots, llms.txt, şema)'];
    $o = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin:-6px 0 22px">';
    foreach ($tabs as $k => $n) {
        $o .= '<a class="btn sm' . ($k === $active ? ' primary' : '') . '" href="' . admin_url($k) . '">' . h($n) . '</a>';
    }
    return $o . '</div>';
}

function score_color(int $s): string
{
    return $s >= 85 ? '#2d7a55' : ($s >= 65 ? '#c58a12' : '#b3382c');
}

function score_grade(int $s): string
{
    return $s >= 90 ? 'A' : ($s >= 75 ? 'B' : ($s >= 60 ? 'C' : 'D'));
}

function score_ring(int $s, int $size = 150): string
{
    $r = 52;
    $c = 2 * M_PI * $r;
    $col = score_color($s);
    return '<svg viewBox="0 0 120 120" width="' . $size . '" height="' . $size . '" role="img" aria-label="Skor ' . $s . '/100"><circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="#ece8df" stroke-width="10"/>
<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="' . $col . '" stroke-width="10" stroke-linecap="round" stroke-dasharray="' . round($c * $s / 100, 1) . ' ' . round($c, 1) . '" transform="rotate(-90 60 60)"/>
<text x="60" y="58" text-anchor="middle" font-size="30" font-weight="700" fill="' . $col . '">' . $s . '</text><text x="60" y="76" text-anchor="middle" font-size="10" fill="#6c6f76">/ 100</text></svg>';
}

function admin_seo(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $na = audit_run();
        audit_save($na);
        $v = actions_verify_with_audit($na);
        if (($_POST['do'] ?? '') === 'to_actions') {
            flash(actions_from_audit($na) . ' öneri aksiyon panosuna eklendi.');
            redirect_to('actions');
        }
        flash('Tarama tamamlandı.' . ($v ? " $v aksiyon doğrulanıp tamamlandı." : ''));
        redirect_to('seo');
    }
    $a = audit_last();
    if (!$a) {
        audit_save($a = audit_run());
    }
    $hist = array_reverse(rows('SELECT ts, score FROM seo_audits ORDER BY id DESC LIMIT 20'));
    ahead('SEO & GEO', 'seo', 'Arama motoru ve yapay zekâ aramaları için durum, skor ve öneriler');
    echo seo_tabs('seo');
    $labels = $a['cat_labels'];
    echo '<div class="card" style="display:grid;grid-template-columns:auto 1fr auto;gap:28px;align-items:center"><div>' . score_ring($a['score'], 150) . '</div>
<div><h2 style="margin-bottom:6px">Genel skor: ' . $a['score'] . ' · Not ' . score_grade($a['score']) . '</h2><p class="hint" style="margin:0 0 14px">Son tarama: ' . h($a['ts']) . ' · ' . count($a['checks']) . ' kontrol · ' . count($a['recs']) . ' iyileştirme önerisi</p><div class="grid g4">';
    foreach ($a['cats'] as $k => $s) {
        echo '<div><div style="display:flex;justify-content:space-between;font-size:.85rem"><b>' . h($labels[$k]) . '</b><span style="color:' . score_color((int)$s) . ';font-weight:700">' . $s . '</span></div><div style="height:8px;background:#ece8df;border-radius:6px;margin-top:6px;overflow:hidden"><div style="height:100%;width:' . (int)$s . '%;background:' . score_color((int)$s) . '"></div></div></div>';
    }
    echo '</div></div><form method="post" style="display:flex;flex-direction:column;gap:8px">' . csrf_field() . '<button class="btn primary">Yeniden tara</button><button class="btn" name="do" value="to_actions">Önerileri aksiyona çevir</button><button type="button" class="btn" onclick="window.print()">Raporu yazdır / PDF</button></form></div>';
    if (count($hist) > 1) {
        $w = 520;
        $h = 56;
        $pts = [];
        foreach ($hist as $i => $r) {
            $pts[] = round($i * $w / (count($hist) - 1), 1) . ',' . round($h - ($r['score'] / 100) * $h, 1);
        }
        echo '<div class="card"><h2>Skor geçmişi</h2><svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="64" preserveAspectRatio="none"><polyline fill="none" stroke="#a07a3c" stroke-width="2" points="' . implode(' ', $pts) . '"/></svg><p class="hint" style="margin:6px 0 0">' . h($hist[0]['ts']) . ' → ' . h(end($hist)['ts']) . ' · ' . $hist[0]['score'] . ' → ' . end($hist)['score'] . '</p></div>';
    }
    echo '<div class="card"><h2>Öncelikli öneriler</h2><p class="hint" style="margin-top:-8px">Skora en çok katkı sağlayacak işlerden başlayacak şekilde sıralıdır. "Etki", tamamlandığında genel skora eklenebilecek yaklaşık puandır.</p>';
    if (!$a['recs']) {
        echo '<p>Harika — şu an kritik bir öneri yok.</p>';
    }
    foreach ($a['recs'] as $c) {
        $imp = $c['gain'] >= 3 ? ['Yüksek', 'fail'] : ($c['gain'] >= 1.2 ? ['Orta', 'warn'] : ['Düşük', 'off']);
        echo '<details class="fold"><summary><span><span class="pill ' . $imp[1] . '">' . $imp[0] . ' etki</span> &nbsp;<b>' . h($c['title']) . '</b> <span class="hint">· ' . h($labels[$c['cat']]) . '</span></span><span class="hint">+' . $c['gain'] . ' puan</span></summary><div class="inner"><p style="margin-top:0"><b>Durum:</b> ' . h($c['msg']) . '</p><p><b>Nasıl düzeltilir:</b> ' . h($c['fix']) . '</p>';
        if ($c['details']) {
            echo '<p class="hint"><b>Etkilenen:</b> ' . h(implode(' · ', $c['details'])) . (count($c['details']) >= 12 ? ' …' : '') . '</p>';
        }
        if ($c['link']) {
            $lk = $c['link'];
            echo '<a class="btn sm primary" href="' . admin_url($lk[0], $lk[1] ?? []) . '">Düzelt →</a>';
        }
        echo '</div></details>';
    }
    echo '</div><div class="card"><h2>Tüm kontroller</h2>';
    foreach ($labels as $k => $label) {
        echo '<h3 style="margin:18px 0 8px">' . h($label) . ' <span class="pill" style="margin-left:6px">' . $a['cats'][$k] . '/100</span></h3><table><tbody>';
        foreach ($a['checks'] as $c) {
            if ($c['cat'] !== $k) {
                continue;
            }
            $pill = ['pass' => ['ok', 'Geçti'], 'warn' => ['warn', 'Kısmen'], 'fail' => ['fail', 'Başarısız']][$c['status']];
            echo '<tr><td style="width:96px"><span class="pill ' . $pill[0] . '"' . ($pill[0] === 'fail' ? ' style="background:#fbeceb;color:#8f2a20"' : '') . '>' . $pill[1] . '</span></td><td><b>' . h($c['title']) . '</b><div class="hint">' . h($c['msg']) . '</div></td><td style="text-align:right;white-space:nowrap" class="hint">' . round($c['ratio'] * $c['weight'], 1) . ' / ' . $c['weight'] . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div><div class="card"><h2>Skor nasıl hesaplanır?</h2><p class="hint" style="margin-top:0">Her kontrolün ağırlığı vardır; sonuç 0–1 arası bir orana çevrilir ve kategori puanı ağırlıklı ortalamadır. Genel skor: Teknik SEO %35 · İçerik %20 · GEO (yapay zekâ aramaları) %30 · Coğrafi/yerel %15. Tarama, sitenin tüm yayındaki sayfalarını 5 dilde gerçekten üretip inceler; harici bir hizmete veri göndermez. Skor bir tahmindir: sıralama garantisi vermez, iyileştirme önceliği verir.</p></div>
<style>@media print{.side,.pagehead+div a,form,button,.savebar{display:none!important}.layout{display:block}main.content{padding:0}details.fold:not([open])>.inner{display:block}}</style>';
    afoot();
}

function admin_seo_pages(): void
{
    $a = audit_last();
    if (!$a) {
        audit_save($a = audit_run());
    }
    $lang = in_array($_GET['l'] ?? '', LANGS, true) ? $_GET['l'] : DEFAULT_LANG;
    ahead('SEO & GEO', 'seo', 'Sayfa bazlı durum — ' . LANG_NAME[$lang]);
    echo seo_tabs('seo_pages') . '<div style="display:flex;gap:8px;margin-bottom:16px">';
    foreach (LANGS as $l) {
        echo '<a class="btn sm' . ($l === $lang ? ' primary' : '') . '" href="' . admin_url('seo_pages', ['l' => $l]) . '">' . LANG_LABEL[$l] . '</a>';
    }
    echo '</div><p class="hint" style="margin-top:-4px">Son tarama: ' . h($a['ts']) . '. Değişiklik yaptıysanız <a href="' . admin_url('seo') . '">yeniden tarayın</a>.</p><div class="card" style="padding:0;overflow:auto"><table><thead><tr><th>Sayfa</th><th>Başlık</th><th>Açıklama</th><th>H1</th><th>Kelime</th><th>Görsel / alt</th><th>Şema</th><th></th></tr></thead><tbody>';
    $pg = [];
    foreach (rows('SELECT id, slug, type, noindex, title FROM pages ORDER BY sort, id') as $p) {
        $pg[$p['slug']] = $p;
    }
    $badge = function (int $len, int $lo, int $hi) {
        $ok = $len >= $lo && $len <= $hi;
        return '<span class="pill ' . ($ok ? 'ok' : ($len === 0 ? 'fail' : 'warn')) . '"' . ($len === 0 ? ' style="background:#fbeceb;color:#8f2a20"' : '') . '>' . $len . '</span>';
    };
    foreach ($a['pages'] as $slug => $langs) {
        $f = $langs[$lang] ?? null;
        if (!$f || !isset($pg[$slug])) {
            continue;
        }
        $alt = $f['imgs'] - $f['img_noalt'] - $f['img_emptyalt'];
        echo '<tr><td><b>' . h(page_title_tr($pg[$slug])) . '</b>' . ($f['noindex_flag'] ? ' <span class="pill off">noindex</span>' : '') . '<div class="hint">/' . h($slug) . '.html</div></td><td style="max-width:260px"><div>' . h($f['title']) . '</div>' . $badge(mb_strlen($f['title']), 25, 62) . '</td>
<td style="max-width:300px"><div class="hint">' . h(mb_strimwidth($f['desc'], 0, 120, '…')) . '</div>' . $badge(mb_strlen($f['desc']), 70, 165) . '</td><td>' . ($f['h1'] === 1 ? '<span class="pill ok">1</span>' : '<span class="pill warn">' . $f['h1'] . '</span>') . '</td><td>' . $f['words'] . '</td><td>' . $f['imgs'] . ' / ' . $alt . '</td>
<td class="hint">' . h(implode(', ', array_slice($f['schema'], 0, 5))) . '</td><td class="actions"><a class="btn sm" href="' . admin_url('page_edit', ['id' => $pg[$slug]['id']]) . '">Düzenle</a></td></tr>';
    }
    echo '</tbody></table></div>';
    afoot();
}

function admin_seo_settings(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $errs = [];
        set_setting('seo_schema', ($_POST['seo_schema'] ?? '0') === '1' ? '1' : '0');
        set_setting('seo_org_type', isset(SEO_ORG_TYPES[$_POST['seo_org_type'] ?? '']) ? $_POST['seo_org_type'] : 'Organization');
        foreach (['seo_org_name', 'seo_legal', 'seo_street', 'seo_city', 'seo_region', 'seo_postal'] as $k) {
            set_setting($k, post_str($k, 160));
        }
        set_setting('seo_country', strtoupper(preg_replace('/[^A-Za-z]/', '', post_str('seo_country', 2))));
        $fy = preg_replace('/\D/', '', post_str('seo_founded', 4));
        set_setting('seo_founded', $fy);
        foreach (['seo_logo', 'seo_og_image'] as $k) {
            set_setting($k, safe_path(post_str($k, 200)));
        }
        foreach (['seo_lat' => [-90, 90], 'seo_lng' => [-180, 180]] as $k => [$lo, $hi]) {
            $v = str_replace(',', '.', post_str($k, 20));
            if ($v === '' || (is_numeric($v) && $v >= $lo && $v <= $hi)) {
                set_setting($k, $v);
            } else {
                $errs[] = 'Koordinat geçersiz.';
            }
        }
        $gbp = post_str('seo_gbp', 300);
        set_setting('seo_gbp', $gbp === '' || preg_match('#^https?://#', $gbp) ? $gbp : '');
        foreach (['seo_area', 'seo_knows'] as $k) {
            set_setting($k, implode("\n", lines(mb_substr((string)($_POST[$k] ?? ''), 0, 2000))));
        }
        set_setting('seo_sameas', implode("\n", array_filter(lines(mb_substr((string)($_POST['seo_sameas'] ?? ''), 0, 2000)), function ($u) { return preg_match('#^https?://#', $u); })));
        foreach (['seo_gsc', 'seo_bing'] as $k) {
            $v = post_str($k, 300);
            if (preg_match('/content="([^"]+)"/', $v, $m)) {
                $v = $m[1];
            }
            set_setting($k, preg_replace('/[^A-Za-z0-9_\-]/', '', $v));
        }
        set_setting('seo_desc', je(post_ml('seo_desc', false)));
        set_setting('seo_llms', ($_POST['seo_llms'] ?? '0') === '1' ? '1' : '0');
        set_setting('seo_llms_full', ($_POST['seo_llms_full'] ?? '0') === '1' ? '1' : '0');
        set_setting('seo_llms_lang', in_array($_POST['seo_llms_lang'] ?? '', LANGS, true) ? $_POST['seo_llms_lang'] : 'en');
        set_setting('seo_llms_intro', post_str('seo_llms_intro', 600));
        $bots = [];
        foreach (array_keys(SEO_AI_BOTS) as $b) {
            $bots[$b] = (($_POST['bot'][$b] ?? 'allow') === 'block') ? 'block' : 'allow';
        }
        set_setting('seo_bots', je($bots));
        when_saved();
        $errs ? flash(implode(' ', $errs), 'err') : flash('SEO & GEO ayarları kaydedildi. Yeniden tarayarak skoru güncelleyin.');
        redirect_to('seo_settings');
    }
    ahead('SEO & GEO', 'seo', 'Yapısal veri, harita/adres bilgisi, yapay zekâ botları ve llms.txt');
    $v = function (string $k) { return (string)seo($k); };
    echo seo_tabs('seo_settings') . '<form method="post" data-guard>' . csrf_field() . langbar();
    echo '<div class="card"><h2>Kuruluş (yapısal veri)</h2><p class="hint" style="margin-top:-8px">Google ve yapay zekâ motorları markanızı bu bilgiden tanır (JSON-LD, her sayfaya otomatik eklenir).</p>'
        . checkbox('seo_schema', $v('seo_schema') === '1', 'Yapısal veriyi (JSON-LD) sayfalara ekle') . '<div class="grid g2">'
        . '<div class="field"><label class="l">Kuruluş türü</label><select name="seo_org_type">';
    foreach (SEO_ORG_TYPES as $k => $n) {
        echo '<option value="' . $k . '"' . ($v('seo_org_type') === $k ? ' selected' : '') . '>' . h($n) . '</option>';
    }
    echo '</select></div>' . field('Marka adı', 'seo_org_name', $v('seo_org_name'), 'text', 'Boşsa Ayarlar → Site adı kullanılır.') . field('Resmi unvan', 'seo_legal', $v('seo_legal'), 'text', 'Boşsa Ayarlar → Resmi unvan kullanılır.') . field('Kuruluş yılı', 'seo_founded', $v('seo_founded'), 'text', 'Örn: 1978') . '</div>'
        . ml_input('seo_desc', jd($v('seo_desc')), 'Kuruluş tanımı (2–3 cümle; yapay zekâ bunu alıntılar)', 'area', false) . '<p class="hint" style="margin-top:-8px">Boş bırakılan dilde alt bilgi tanıtım metni kullanılır.</p>'
        . '<div class="grid g2">' . img_input('seo_logo', $v('seo_logo'), 'Logo (kare/yatay, ≥ 112 px)') . img_input('seo_og_image', $v('seo_og_image'), 'Paylaşım görseli (1200×630)') . '</div>'
        . '<div class="grid g2"><div class="field"><label class="l">Resmi profiller (sameAs) — her satıra bir adres</label><textarea name="seo_sameas" placeholder="https://www.linkedin.com/company/…">' . h($v('seo_sameas')) . '</textarea><div class="hint">Instagram (Ayarlar\'dan) otomatik eklenir. LinkedIn, YouTube, Alibaba, Wikipedia/Wikidata…</div></div>
<div class="field"><label class="l">Uzmanlık konuları (knowsAbout) — satır/virgül</label><textarea name="seo_knows">' . h($v('seo_knows')) . '</textarea><div class="hint">Markanın uzman olduğu 4–8 konu / ürün grubu.</div></div></div></div>';
    echo '<div class="card"><h2>Adres ve konum (yerel / coğrafi SEO)</h2><div class="grid g3">' . field('Sokak / adres', 'seo_street', $v('seo_street')) . field('Şehir / ilçe', 'seo_city', $v('seo_city')) . field('Bölge / il', 'seo_region', $v('seo_region'))
        . field('Posta kodu', 'seo_postal', $v('seo_postal')) . field('Ülke kodu (2 harf)', 'seo_country', $v('seo_country'), 'text', 'TR, DE, AE…') . field('Google İşletme Profili / Harita bağlantısı', 'seo_gbp', $v('seo_gbp'), 'url') . field('Enlem (lat)', 'seo_lat', $v('seo_lat'), 'text', 'Örn: 40.8234') . field('Boylam (lng)', 'seo_lng', $v('seo_lng'), 'text', 'Örn: 29.4012') . '</div>
<div class="field"><label class="l">Hizmet verilen ülkeler / bölgeler (areaServed) — satır/virgül</label><textarea name="seo_area" placeholder="Germany&#10;United Arab Emirates&#10;Russia">' . h($v('seo_area')) . '</textarea><div class="hint">İhracat yaptığınız başlıca pazarlar; İngilizce ülke adı yazın.</div></div></div>';
    echo '<div class="card"><h2>Yapay zekâ aramaları (GEO)</h2><p class="hint" style="margin-top:-8px">ChatGPT, Claude, Perplexity, Gemini gibi cevap motorlarının sitenizi okuyup alıntılayabilmesi için. Arama/kullanıcı botlarına izin vermek görünürlük sağlar; eğitim botlarını isteğe göre kapatabilirsiniz.</p>'
        . checkbox('seo_llms', $v('seo_llms') === '1', '/llms.txt yayınla (site özeti + sayfa listesi)') . checkbox('seo_llms_full', $v('seo_llms_full') === '1', '/llms-full.txt yayınla (tüm sayfa metinleri tek dosyada)')
        . '<div class="grid g2"><div class="field"><label class="l">llms.txt dili</label><select name="seo_llms_lang">';
    foreach (LANGS as $l) {
        echo '<option value="' . $l . '"' . ($v('seo_llms_lang') === $l ? ' selected' : '') . '>' . h(LANG_NAME[$l]) . '</option>';
    }
    echo '</select></div>' . field('llms.txt giriş cümlesi (boşsa kuruluş tanımı)', 'seo_llms_intro', $v('seo_llms_intro')) . '</div><table><thead><tr><th>Tarayıcı</th><th>Tür</th><th>İzin</th></tr></thead><tbody>';
    $types = ['search' => 'Arama / cevap', 'user' => 'Kullanıcı isteği', 'train' => 'Model eğitimi'];
    foreach (seo_bots() as $b => $mode) {
        echo '<tr><td><b>' . h($b) . '</b><div class="hint">' . h(SEO_AI_BOTS[$b][0]) . '</div></td><td>' . $types[SEO_AI_BOTS[$b][1]] . '</td><td><select name="bot[' . h($b) . ']" style="max-width:140px"><option value="allow"' . ($mode === 'allow' ? ' selected' : '') . '>İzin ver</option><option value="block"' . ($mode === 'block' ? ' selected' : '') . '>Engelle</option></select></td></tr>';
    }
    echo '</tbody></table></div><div class="card"><h2>Doğrulama kodları</h2><div class="grid g2">' . field('Google Search Console (HTML etiketi içeriği)', 'seo_gsc', $v('seo_gsc'), 'text', 'Etiketin tamamını yapıştırabilirsiniz; yalnızca kod alınır.') . field('Bing Webmaster (msvalidate.01)', 'seo_bing', $v('seo_bing')) . '</div></div>';
    echo '<div class="savebar"><button class="btn primary">Kaydet</button><a class="btn" href="' . admin_url('seo_preview') . '">Önizleme →</a></div></form>';
    afoot();
}

function admin_seo_preview(): void
{
    $lang = in_array($_GET['l'] ?? '', LANGS, true) ? $_GET['l'] : DEFAULT_LANG;
    $slug = preg_match('/^[a-z0-9-]+$/', (string)($_GET['p'] ?? '')) ? $_GET['p'] : 'index';
    $page = row('SELECT * FROM pages WHERE slug = ?', [$slug]) ?: row("SELECT * FROM pages WHERE slug = 'index'");
    $GLOBALS['UZ_LANG'] = $lang;
    $graph = json_encode(seo_graph($page['slug'], $lang, $page, page_text_title($page, $lang), ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ahead('SEO & GEO', 'seo', 'Arama motorlarının ve yapay zekâ botlarının göreceği çıktılar');
    $pre = function (string $t, int $max = 9000) {
        return '<pre style="background:#14161a;color:#d9dce2;padding:16px;border-radius:8px;overflow:auto;max-height:420px;font-size:.78rem;line-height:1.5;white-space:pre-wrap">' . h(mb_strlen($t) > $max ? mb_substr($t, 0, $max) . "\n… (kısaltıldı)" : $t) . '</pre>';
    };
    echo seo_tabs('seo_preview') . '<div class="card"><h2>robots.txt</h2><p class="hint" style="margin-top:-8px"><a href="../robots.txt" target="_blank" rel="noopener">/robots.txt ↗</a> · <a href="../sitemap.xml" target="_blank" rel="noopener">/sitemap.xml ↗</a></p>' . $pre(robots_txt()) . '</div>';
    echo '<div class="card"><h2>llms.txt</h2>' . (seo('seo_llms') === '1' ? '<p class="hint" style="margin-top:-8px"><a href="../llms.txt" target="_blank" rel="noopener">/llms.txt ↗</a>' . (seo('seo_llms_full') === '1' ? ' · <a href="../llms-full.txt" target="_blank" rel="noopener">/llms-full.txt ↗</a>' : '') . '</p>' . $pre(llms_txt()) : '<p class="hint">Kapalı (Ayarlar\'dan açın).</p>') . '</div>';
    echo '<div class="card"><h2>Yapısal veri (JSON-LD)</h2><form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px"><input type="hidden" name="a" value="seo_preview"><select name="p" style="max-width:240px">';
    foreach (rows('SELECT slug, type FROM pages WHERE status = 1 ORDER BY sort') as $p) {
        echo '<option value="' . h($p['slug']) . '"' . ($p['slug'] === $page['slug'] ? ' selected' : '') . '>' . h($p['slug']) . '</option>';
    }
    echo '</select><select name="l" style="max-width:160px">';
    foreach (LANGS as $l) {
        echo '<option value="' . $l . '"' . ($l === $lang ? ' selected' : '') . '>' . h(LANG_NAME[$l]) . '</option>';
    }
    echo '</select><button class="btn sm">Göster</button></form>' . (seo('seo_schema') === '1' ? $pre($graph, 14000) : '<p class="hint">Yapısal veri kapalı.</p>')
        . '<p class="hint">Canlı sitede doğrulamak için: <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener">Google Rich Results Test ↗</a> · <a href="https://validator.schema.org/" target="_blank" rel="noopener">Schema.org Validator ↗</a></p></div>';
    afoot();
}
