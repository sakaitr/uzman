<?php
declare(strict_types=1);

require_once UZ_APP . '/admin/seo.php';

function growth_tabs(string $active): string
{
    $tabs = ['growth' => 'Büyüme panosu', 'actions' => 'Aksiyonlar', 'ads' => 'Reklamlar', 'reports' => 'Raporlar', 'tracking' => 'İzleme ve otomasyon'];
    $o = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin:-6px 0 22px">';
    foreach ($tabs as $k => $n) {
        $o .= '<a class="btn sm' . ($k === $active ? ' primary' : '') . '" href="' . admin_url($k) . '">' . h($n) . '</a>';
    }
    return $o . '<a class="btn sm" href="' . admin_url('seo') . '">SEO & GEO →</a></div>';
}

function kpi(string $label, string $value, string $sub = ''): string
{
    return '<div class="stat"><span>' . h($label) . '</span><b>' . $value . '</b><div class="hint" style="margin-top:2px">' . $sub . '</div></div>';
}

function channel_bars(array $summary): string
{
    $max = 1;
    foreach ($summary['by'] as $b) {
        $max = max($max, (int)$b['s']);
    }
    $o = '<table><thead><tr><th>Kanal</th><th style="width:48%"></th><th style="text-align:right">Oturum</th><th style="text-align:right">Başvuru</th></tr></thead><tbody>';
    if (!$summary['by']) {
        return '<p class="hint">Bu dönemde ziyaret verisi yok. İzleme ve otomasyon sekmesinden ölçümün çalıştığını doğrulayın.</p>';
    }
    foreach ($summary['by'] as $b) {
        $k = $b['channel'];
        $o .= '<tr><td><b>' . h(CHANNEL_LABELS[$k] ?? $k) . '</b></td><td><div style="height:10px;background:#ece8df;border-radius:6px;overflow:hidden"><div style="height:100%;width:' . round($b['s'] / $max * 100) . '%;background:' . (CHANNEL_COLORS[$k] ?? '#999') . '"></div></div></td><td style="text-align:right">' . num($b['s']) . '</td><td style="text-align:right">' . ($summary['leads_by'][$k] ?? 0) . '</td></tr>';
    }
    return $o . '</tbody></table>';
}

function admin_growth(): void
{
    $to = date('Y-m-d');
    $from = date('Y-m-d', strtotime('-29 days'));
    $pto = date('Y-m-d', strtotime('-30 days'));
    $pfrom = date('Y-m-d', strtotime('-59 days'));
    $c = traffic_summary($from, $to);
    $p = traffic_summary($pfrom, $pto);
    $ai = (int)val("SELECT COALESCE(SUM(sessions),0) FROM visits WHERE channel='ai' AND day BETWEEN ? AND ?", [$from, $to]);
    $aiP = (int)val("SELECT COALESCE(SUM(sessions),0) FROM visits WHERE channel='ai' AND day BETWEEN ? AND ?", [$pfrom, $pto]);
    $audit = audit_last();
    $spend = (float)val('SELECT COALESCE(SUM(spend),0) FROM campaign_metrics WHERE to_date >= ? AND from_date <= ?', [$from, $to]);
    $conv = $c['sessions'] ? round($c['leads'] / $c['sessions'] * 100, 2) : 0;
    $daily = [];
    foreach (rows('SELECT day, SUM(sessions) AS s FROM visits WHERE day BETWEEN ? AND ? GROUP BY day', [$from, $to]) as $r) {
        $daily[$r['day']] = (int)$r['s'];
    }
    ahead('Büyüme panosu', 'growth', 'Son 30 gün — ziyaret, başvuru, kanallar, aksiyonlar');
    echo growth_tabs('growth');
    echo '<div class="grid g4" style="margin-bottom:20px">' . kpi('Ziyaret (oturum)', num($c['sessions']), delta_html($c['sessions'], $p['sessions']) . ' önceki 30 güne göre')
        . kpi('Form başvurusu', (string)$c['leads'], delta_html($c['leads'], $p['leads']) . ' önceki 30 güne göre') . kpi('Dönüşüm oranı', $conv . '%', 'başvuru / oturum')
        . kpi('Yapay zekâdan gelen', num($ai), delta_html($ai, $aiP) . ' ChatGPT, Perplexity vb.') . '</div>';
    echo '<div class="grid g4" style="margin-bottom:20px">' . kpi('SEO & GEO skoru', $audit ? (string)$audit['score'] : '—', $audit ? 'Not ' . score_grade($audit['score']) . ' · <a href="' . admin_url('seo') . '">ayrıntı</a>' : '<a href="' . admin_url('seo') . '">taramayı başlat</a>')
        . kpi('Reklam harcaması', money($spend), 'son 30 gün (girilen sonuçlar)') . kpi('Başvuru başı maliyet', $spend && $c['leads'] ? money($spend / $c['leads']) : '—', 'harcama / tüm başvurular')
        . kpi('Açık aksiyon', (string)(int)val("SELECT COUNT(*) FROM growth_actions WHERE status IN ('todo','doing')"), '<a href="' . admin_url('actions') . '">panoya git</a>') . '</div>';
    // daily sessions chart
    $w = 760;
    $h = 90;
    $max = max(1, $daily ? max($daily) : 1);
    $pts = [];
    $i = 0;
    for ($d = strtotime($from); $d <= strtotime($to); $d += 86400, $i++) {
        $pts[] = round($i * $w / 29, 1) . ',' . round($h - (($daily[date('Y-m-d', $d)] ?? 0) / $max) * ($h - 6) - 3, 1);
    }
    echo '<div class="grid g2"><div class="card"><h2>Günlük ziyaret</h2><svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="110" preserveAspectRatio="none"><polyline fill="none" stroke="#a07a3c" stroke-width="2.2" points="' . implode(' ', $pts) . '"/></svg><p class="hint" style="margin:6px 0 0">' . h($from) . ' → ' . h($to) . ' · en yüksek gün: ' . $max . ' oturum</p></div>
<div class="card"><h2>Kanal kırılımı</h2>' . channel_bars($c) . '</div></div>';
    echo '<div class="grid g2"><div class="card"><h2>Öncelikli açık aksiyonlar</h2>';
    $open = rows("SELECT * FROM growth_actions WHERE status IN ('todo','doing') ORDER BY CASE priority WHEN 'high' THEN 0 WHEN 'med' THEN 1 ELSE 2 END, due = '', due LIMIT 6");
    if (!$open) {
        echo '<p class="hint">Açık aksiyon yok. <a href="' . admin_url('actions') . '">Kütüphaneden ekleyin</a> veya SEO önerilerini aksiyona çevirin.</p>';
    }
    foreach ($open as $a) {
        $late = $a['due'] !== '' && $a['due'] < date('Y-m-d');
        echo '<div class="tree-row"><span class="nm"><a href="' . admin_url('action_edit', ['id' => $a['id']]) . '">' . h($a['title']) . '</a><div class="hint">' . h(GROWTH_CHANNELS[$a['channel']] ?? $a['channel']) . ($a['due'] ? ' · son: ' . h($a['due']) : '') . '</div></span>' . ($late ? '<span class="pill" style="background:#fbeceb;color:#8f2a20">gecikti</span>' : '<span class="pill ' . ($a['status'] === 'doing' ? 'new' : 'off') . '">' . h(GROWTH_STATUS[$a['status']]) . '</span>') . '</div>';
    }
    echo '</div><div class="card"><h2>Sonraki adımlar</h2><ul class="check-list">';
    $firstHit = (int)val('SELECT COUNT(*) FROM visits');
    $steps = [
        [$firstHit > 0, 'Ziyaret ölçümü', $firstHit ? 'Veri geliyor.' : 'Henüz ziyaret verisi yok; yayında sitede bir sayfa açıp birkaç dakika sonra kontrol edin.', 'tracking'],
        [trackers_on(), 'Reklam / analiz etiketleri', trackers_on() ? 'Tanımlı.' : 'GA4/GTM/Google Ads/Meta kimliklerini girin (onay bandı otomatik açılır).', 'tracking'],
        [(int)val("SELECT COUNT(*) FROM campaigns WHERE status = 'active'") > 0, 'Aktif reklam kampanyası', 'Reklam verecekseniz kampanyayı kaydedip UTM bağlantısını kullanın; sonuçlar raporda görünür.', 'ads'],
        [(int)val("SELECT COUNT(*) FROM growth_actions WHERE status = 'done'") > 0, 'İlk aksiyonu tamamlayın', 'Playbook kütüphanesinden Search Console, Google İşletme Profili gibi hızlı kazanımları seçin.', 'actions'],
        [setting('cron_last', '') !== '', 'Otomatik haftalık tarama', setting('cron_last', '') !== '' ? 'Son çalışma: ' . h(setting('cron_last')) : 'cPanel cron kaydı ekleyin (İzleme ve otomasyon).', 'tracking'],
    ];
    foreach ($steps as [$ok, $t, $d, $to]) {
        echo '<li><span class="dot' . ($ok ? ' ok' : '') . '"></span><div><b>' . h($t) . '</b><div class="hint">' . $d . ' <a href="' . admin_url($to) . '">Aç →</a></div></div></li>';
    }
    echo '</ul></div></div>';
    afoot();
}

// ------------------------------------------------------------------ actions board
function level_pill(string $lvl, string $label): string
{
    $cls = ['high' => 'ok', 'med' => 'warn', 'low' => 'off'][$lvl] ?? 'off';
    return '<span class="pill ' . $cls . '">' . h($label) . ': ' . h(GROWTH_LEVEL[$lvl] ?? $lvl) . '</span>';
}

function admin_actions(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'status') {
            action_set_status((int)$_POST['id'], (string)$_POST['status']);
        } elseif ($do === 'add_playbooks') {
            $n = 0;
            $lib = [];
            foreach (growth_playbooks() as $p) {
                $lib[$p['id']] = $p;
            }
            foreach ((array)($_POST['pb'] ?? []) as $id) {
                if (isset($lib[$id]) && !val('SELECT COUNT(*) FROM growth_actions WHERE source = ?', ['playbook:' . $id])) {
                    $p = $lib[$id];
                    action_add(['title' => $p['title'], 'channel' => $p['channel'], 'impact' => $p['impact'], 'effort' => $p['effort'], 'priority' => $p['impact'], 'why' => $p['why'], 'steps' => $p['steps'], 'source' => 'playbook:' . $id]);
                    $n++;
                }
            }
            flash($n . ' aksiyon eklendi.');
        } elseif ($do === 'from_audit') {
            $a = audit_last() ?: audit_run();
            flash(actions_from_audit($a) . ' SEO & GEO önerisi aksiyona çevrildi.');
        } elseif ($do === 'new') {
            $t = post_str('title', 200);
            if ($t !== '') {
                $id = action_add(['title' => $t, 'channel' => $_POST['channel'] ?? 'seo', 'priority' => in_array($_POST['priority'] ?? '', array_keys(GROWTH_LEVEL), true) ? $_POST['priority'] : 'med', 'due' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['due'] ?? '')) ? $_POST['due'] : '']);
                redirect_to('action_edit', ['id' => $id]);
            }
        }
        redirect_to('actions', array_filter(['c' => $_GET['c'] ?? '']));
    }
    $fc = isset(GROWTH_CHANNELS[$_GET['c'] ?? '']) ? $_GET['c'] : '';
    $where = $fc ? ' WHERE channel = ' . db()->quote($fc) : '';
    $acts = rows('SELECT * FROM growth_actions' . $where . " ORDER BY CASE priority WHEN 'high' THEN 0 WHEN 'med' THEN 1 ELSE 2 END, id DESC");
    ahead('Aksiyonlar', 'actions', 'SEO, GEO, reklam ve diğer büyüme işleri — planla, takip et, raporla');
    echo growth_tabs('actions');
    echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px"><a class="btn sm' . ($fc === '' ? ' primary' : '') . '" href="' . admin_url('actions') . '">Tümü</a>';
    foreach (GROWTH_CHANNELS as $k => $n) {
        echo '<a class="btn sm' . ($fc === $k ? ' primary' : '') . '" href="' . admin_url('actions', ['c' => $k]) . '">' . h($n) . '</a>';
    }
    echo '</div><div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px"><form method="post">' . csrf_field() . '<input type="hidden" name="do" value="from_audit"><button class="btn sm">SEO & GEO önerilerini aksiyona çevir</button></form><a class="btn sm" href="#kutuphane">Aksiyon kütüphanesi ↓</a><a class="btn sm" href="#yeni">+ Yeni aksiyon ↓</a></div>';
    echo '<div class="grid g3" style="align-items:start">';
    foreach (['todo', 'doing', 'done'] as $st) {
        $col = array_values(array_filter($acts, function ($a) use ($st) { return $a['status'] === $st; }));
        echo '<div><h3 style="display:flex;justify-content:space-between">' . h(GROWTH_STATUS[$st]) . ' <span class="pill">' . count($col) . '</span></h3>';
        foreach ($col as $a) {
            $late = $a['status'] !== 'done' && $a['due'] !== '' && $a['due'] < date('Y-m-d');
            $src = strpos($a['source'], 'seo:') === 0 ? 'SEO taraması' : (strpos($a['source'], 'playbook:') === 0 ? 'kütüphane' : '');
            echo '<div class="card" style="padding:14px;margin-bottom:12px"><div style="font-weight:600;margin-bottom:6px"><a href="' . admin_url('action_edit', ['id' => $a['id']]) . '" style="color:inherit;text-decoration:none">' . h($a['title']) . '</a></div>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px"><span class="pill new">' . h(GROWTH_CHANNELS[$a['channel']] ?? $a['channel']) . '</span>' . level_pill($a['impact'], 'etki') . level_pill($a['effort'], 'emek') . ($src ? '<span class="pill off">' . $src . '</span>' : '') . ($late ? '<span class="pill" style="background:#fbeceb;color:#8f2a20">gecikti ' . h($a['due']) . '</span>' : ($a['due'] ? '<span class="pill off">' . h($a['due']) . '</span>' : '')) . '</div>
<form method="post" style="display:flex;gap:6px;flex-wrap:wrap">' . csrf_field() . '<input type="hidden" name="do" value="status"><input type="hidden" name="id" value="' . $a['id'] . '">';
            foreach (['todo' => '← Planlandı', 'doing' => 'Yapılıyor', 'done' => '✓ Tamamla'] as $k => $l) {
                if ($k !== $st) {
                    echo '<button class="btn sm" name="status" value="' . $k . '">' . $l . '</button>';
                }
            }
            echo '</form></div>';
        }
        if (!$col) {
            echo '<p class="hint">—</p>';
        }
        echo '</div>';
    }
    echo '</div>';
    $skip = array_filter($acts, function ($a) { return $a['status'] === 'skip'; });
    if ($skip) {
        echo '<p class="hint">İptal edilen: ' . count($skip) . ' aksiyon (düzenleyip yeniden planlayabilirsiniz).</p>';
    }
    // library
    $have = array_column(rows("SELECT source FROM growth_actions WHERE source LIKE 'playbook:%'"), 'source');
    echo '<form method="post" class="card" id="kutuphane" style="margin-top:28px">' . csrf_field() . '<input type="hidden" name="do" value="add_playbooks"><h2>Aksiyon kütüphanesi</h2><p class="hint" style="margin-top:-8px">B2B üretici/ihracatçı siteler için hazır büyüme işleri. Seçip ekleyin; her birinde adım adım yapılacaklar bulunur.</p>';
    foreach (GROWTH_CHANNELS as $ck => $cn) {
        $items = array_filter(growth_playbooks(), function ($p) use ($ck) { return $p['channel'] === $ck; });
        if (!$items) {
            continue;
        }
        echo '<h3 style="margin:16px 0 6px">' . h($cn) . '</h3>';
        foreach ($items as $p) {
            $added = in_array('playbook:' . $p['id'], $have, true);
            echo '<label style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);align-items:flex-start' . ($added ? ';opacity:.55' : '') . '"><input type="checkbox" name="pb[]" value="' . h($p['id']) . '"' . ($added ? ' disabled' : '') . ' style="margin-top:4px"><span><b>' . h($p['title']) . '</b> ' . level_pill($p['impact'], 'etki') . ' ' . level_pill($p['effort'], 'emek') . ($added ? ' <span class="pill ok">eklendi</span>' : '') . '<div class="hint">' . h($p['why']) . '</div></span></label>';
        }
    }
    echo '<p style="margin:16px 0 0"><button class="btn primary">Seçilenleri panoya ekle</button></p></form>';
    echo '<form method="post" class="card" id="yeni">' . csrf_field() . '<input type="hidden" name="do" value="new"><h2>Yeni aksiyon</h2><div class="grid g4">' . field('Başlık', 'title', '')
        . '<div class="field"><label class="l">Kanal</label><select name="channel">';
    foreach (GROWTH_CHANNELS as $k => $n) {
        echo '<option value="' . $k . '">' . h($n) . '</option>';
    }
    echo '</select></div><div class="field"><label class="l">Öncelik</label><select name="priority"><option value="high">Yüksek</option><option value="med" selected>Orta</option><option value="low">Düşük</option></select></div>' . field('Son tarih', 'due', '', 'date') . '</div><button class="btn primary">Oluştur</button></form>';
    afoot();
}

function admin_action_edit(): void
{
    $a = row('SELECT * FROM growth_actions WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$a) {
        redirect_to('actions');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? 'save');
        if ($do === 'delete') {
            q('DELETE FROM growth_actions WHERE id = ?', [$a['id']]);
            flash('Aksiyon silindi.');
            redirect_to('actions');
        }
        if ($do === 'comment') {
            if (post_str('text', 1000) !== '') {
                action_log((int)$a['id'], post_str('text', 1000));
            }
            redirect_to('action_edit', ['id' => $a['id']]);
        }
        $lvl = function (string $k) { return in_array($_POST[$k] ?? '', array_keys(GROWTH_LEVEL), true) ? $_POST[$k] : 'med'; };
        $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['due'] ?? '')) ? $_POST['due'] : '';
        q('UPDATE growth_actions SET title=?, channel=?, priority=?, impact=?, effort=?, due=?, owner=?, why=?, steps=?, notes=?, result=?, campaign_id=?, updated_at=? WHERE id=?', [
            post_str('title', 200) ?: $a['title'], isset(GROWTH_CHANNELS[$_POST['channel'] ?? '']) ? $_POST['channel'] : $a['channel'], $lvl('priority'), $lvl('impact'), $lvl('effort'), $due, post_str('owner', 80),
            post_str('why', 1500), mb_substr((string)($_POST['steps'] ?? ''), 0, 4000), mb_substr((string)($_POST['notes'] ?? ''), 0, 4000), mb_substr((string)($_POST['result'] ?? ''), 0, 2000), ((int)($_POST['campaign_id'] ?? 0)) ?: null, date('Y-m-d H:i:s'), $a['id'],
        ]);
        action_set_status((int)$a['id'], (string)($_POST['status'] ?? $a['status']), '');
        flash('Kaydedildi.');
        redirect_to('action_edit', ['id' => $a['id']]);
    }
    ahead($a['title'], 'actions', 'Aksiyon · ' . GROWTH_STATUS[$a['status']] . ($a['source'] ? ' · kaynak: ' . $a['source'] : ''));
    echo growth_tabs('actions') . '<form method="post" data-guard>' . csrf_field() . '<div class="card"><div class="grid g2">' . field('Başlık', 'title', $a['title']) . field('Sorumlu', 'owner', $a['owner'], 'text', 'Kişi veya ekip adı') . '</div><div class="grid g4">';
    $sel = function (string $name, string $label, array $opts, string $cur) {
        $o = '<div class="field"><label class="l">' . h($label) . '</label><select name="' . $name . '">';
        foreach ($opts as $k => $n) {
            $o .= '<option value="' . h($k) . '"' . ($k === $cur ? ' selected' : '') . '>' . h($n) . '</option>';
        }
        return $o . '</select></div>';
    };
    echo $sel('status', 'Durum', GROWTH_STATUS, $a['status']) . $sel('channel', 'Kanal', GROWTH_CHANNELS, $a['channel']) . $sel('priority', 'Öncelik', GROWTH_LEVEL, $a['priority']) . field('Son tarih', 'due', $a['due'], 'date')
        . $sel('impact', 'Beklenen etki', GROWTH_LEVEL, $a['impact']) . $sel('effort', 'Emek', GROWTH_LEVEL, $a['effort']);
    $camps = ['0' => '—'];
    foreach (rows('SELECT id, name FROM campaigns ORDER BY id DESC') as $c) {
        $camps[(string)$c['id']] = $c['name'];
    }
    echo $sel('campaign_id', 'Bağlı kampanya', $camps, (string)(int)$a['campaign_id']) . '</div>
<div class="field"><label class="l">Neden önemli</label><textarea name="why" style="min-height:64px">' . h($a['why']) . '</textarea></div>
<div class="field"><label class="l">Yapılacak adımlar (her satıra bir adım)</label><textarea name="steps" style="min-height:120px">' . h($a['steps']) . '</textarea></div>
<div class="grid g2"><div class="field"><label class="l">Notlar</label><textarea name="notes">' . h($a['notes']) . '</textarea></div><div class="field"><label class="l">Sonuç / çıktı (rapora girer)</label><textarea name="result" placeholder="Ne yapıldı, hangi sonuç alındı, bağlantılar…">' . h($a['result']) . '</textarea></div></div></div>
<div class="savebar"><button class="btn primary">Kaydet</button><a class="btn" href="' . admin_url('actions') . '">Panoya dön</a><button class="btn danger" name="do" value="delete" data-confirm="Aksiyon silinsin mi?">Sil</button></div></form>';
    echo '<div class="card"><h2>Hareket geçmişi</h2><form method="post" style="display:flex;gap:8px;margin-bottom:14px">' . csrf_field() . '<input type="hidden" name="do" value="comment"><input type="text" name="text" placeholder="Not ekle…"><button class="btn">Ekle</button></form>';
    foreach (rows('SELECT * FROM growth_log WHERE action_id = ? ORDER BY id DESC LIMIT 60', [$a['id']]) as $l) {
        echo '<div class="tree-row"><span class="nm" style="font-weight:400">' . h($l['text']) . '</span><span class="hint">' . h($l['who']) . ' · ' . h($l['ts']) . '</span></div>';
    }
    echo '</div>';
    afoot();
}

// ------------------------------------------------------------------ ads
function admin_ads(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $name = post_str('name', 120);
        $slug = slugify(post_str('slug', 60) ?: $name);
        if ($name === '' || $slug === '') {
            flash('Kampanya adı gerekli.', 'err');
        } elseif (row('SELECT id FROM campaigns WHERE slug = ?', [$slug])) {
            flash('Bu kampanya kodu (utm_campaign) zaten var.', 'err');
        } else {
            q('INSERT INTO campaigns(platform,name,slug,objective,status,start,end,budget,landing,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)', [isset(AD_PLATFORMS[$_POST['platform'] ?? '']) ? $_POST['platform'] : 'google', $name, $slug, post_str('objective', 200), 'draft', date('Y-m-d'), '', 0, 'index', date('Y-m-d H:i:s')]);
            flash('Kampanya oluşturuldu.');
            redirect_to('ad_edit', ['id' => (int)db()->lastInsertId()]);
        }
        redirect_to('ads');
    }
    ahead('Reklamlar', 'ads', 'Kampanya kaydı, UTM bağlantıları, harcama ve sonuçlar');
    echo growth_tabs('ads');
    $camps = rows('SELECT * FROM campaigns ORDER BY id DESC');
    echo '<div class="card" style="padding:0;overflow:auto"><table><thead><tr><th>Kampanya</th><th>Platform</th><th>Durum</th><th style="text-align:right">Harcama</th><th style="text-align:right">Tıklama</th><th style="text-align:right">Başvuru</th><th style="text-align:right">Başvuru başı</th><th></th></tr></thead><tbody>';
    foreach ($camps as $c) {
        $s = campaign_stats((int)$c['id']);
        echo '<tr><td><b>' . h($c['name']) . '</b><div class="hint"><code>' . h($c['slug']) . '</code> · ' . h($c['start']) . ($c['end'] ? ' → ' . h($c['end']) : '') . '</div></td><td>' . h(AD_PLATFORMS[$c['platform']] ?? $c['platform']) . '</td><td><span class="pill ' . ($c['status'] === 'active' ? 'ok' : 'off') . '">' . h(AD_STATUS[$c['status']]) . '</span></td>
<td style="text-align:right">' . money($s['spend']) . '</td><td style="text-align:right">' . num($s['clicks']) . '</td><td style="text-align:right">' . $s['best_leads'] . '</td><td style="text-align:right">' . ($s['best_leads'] && $s['spend'] ? money($s['cpl']) : '—') . '</td><td class="actions"><a class="btn sm" href="' . admin_url('ad_edit', ['id' => $c['id']]) . '">Aç</a></td></tr>';
    }
    if (!$camps) {
        echo '<tr><td colspan="8" class="hint" style="padding:24px">Henüz kampanya yok. Aşağıdan ekleyin; her kampanya için UTM bağlantısı otomatik üretilir ve gelen başvurular kampanyaya bağlanır.</td></tr>';
    }
    echo '</tbody></table></div><form method="post" class="card" style="margin-top:20px">' . csrf_field() . '<h2>Yeni kampanya</h2><div class="grid g4">' . field('Kampanya adı', 'name', '') . field('Kampanya kodu (utm_campaign)', 'slug', '', 'text', 'Boşsa addan üretilir')
        . '<div class="field"><label class="l">Platform</label><select name="platform">';
    foreach (AD_PLATFORMS as $k => $n) {
        echo '<option value="' . $k . '">' . h($n) . '</option>';
    }
    echo '</select></div>' . field('Amaç', 'objective', '', 'text', 'Örn: Körfez private label deodorant') . '</div><button class="btn primary">Oluştur</button></form>';
    afoot();
}

function admin_ad_edit(): void
{
    $c = row('SELECT * FROM campaigns WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$c) {
        redirect_to('ads');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? 'save');
        $date = function (string $k) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST[$k] ?? '')) ? $_POST[$k] : ''; };
        if ($do === 'delete') {
            q('DELETE FROM campaigns WHERE id = ?', [$c['id']]);
            flash('Kampanya silindi.');
            redirect_to('ads');
        }
        if ($do === 'metric') {
            $f = $date('from_date');
            $t = $date('to_date') ?: $f;
            if ($f !== '') {
                q('INSERT INTO campaign_metrics(campaign_id, from_date, to_date, spend, impressions, clicks, conversions, note) VALUES(?,?,?,?,?,?,?,?)', [$c['id'], $f, $t, (float)str_replace(',', '.', (string)$_POST['spend']), (int)$_POST['impressions'], (int)$_POST['clicks'], (int)$_POST['conversions'], post_str('note', 200)]);
                flash('Sonuç satırı eklendi.');
            } else {
                flash('Başlangıç tarihi gerekli.', 'err');
            }
            redirect_to('ad_edit', ['id' => $c['id']]);
        }
        if ($do === 'del_metric') {
            q('DELETE FROM campaign_metrics WHERE id = ? AND campaign_id = ?', [(int)$_POST['mid'], $c['id']]);
            redirect_to('ad_edit', ['id' => $c['id']]);
        }
        $slug = slugify(post_str('slug', 60)) ?: $c['slug'];
        if ($slug !== $c['slug'] && row('SELECT id FROM campaigns WHERE slug = ?', [$slug])) {
            $slug = $c['slug'];
            flash('Bu kampanya kodu başka kampanyada kullanılıyor; eskisi korundu.', 'err');
        }
        q('UPDATE campaigns SET platform=?, name=?, slug=?, objective=?, status=?, start=?, end=?, budget=?, landing=?, notes=? WHERE id=?', [
            isset(AD_PLATFORMS[$_POST['platform'] ?? '']) ? $_POST['platform'] : $c['platform'], post_str('name', 120) ?: $c['name'], $slug, post_str('objective', 200), isset(AD_STATUS[$_POST['status'] ?? '']) ? $_POST['status'] : $c['status'],
            $date('start'), $date('end'), (float)str_replace(',', '.', (string)($_POST['budget'] ?? 0)), preg_match('/^[a-z0-9-]+$/', (string)($_POST['landing'] ?? '')) ? $_POST['landing'] : 'index', mb_substr((string)($_POST['notes'] ?? ''), 0, 3000), $c['id'],
        ]);
        flash('Kampanya kaydedildi.');
        redirect_to('ad_edit', ['id' => $c['id']]);
    }
    $s = campaign_stats((int)$c['id']);
    ahead($c['name'], 'ads', (AD_PLATFORMS[$c['platform']] ?? '') . ' · kod: ' . $c['slug']);
    echo growth_tabs('ads');
    echo '<div class="grid g4" style="margin-bottom:20px">' . kpi('Harcama', money($s['spend']), 'bütçe: ' . money($c['budget'])) . kpi('Tıklama / gösterim', num($s['clicks']) . ' / ' . num($s['imp']), 'TO: ' . round($s['ctr'], 2) . '% · TBM: ' . money($s['cpc']))
        . kpi('Başvuru', (string)$s['best_leads'], 'siteden otomatik: ' . $s['leads'] . ' · girilen dönüşüm: ' . $s['conv']) . kpi('Başvuru başı maliyet', $s['best_leads'] && $s['spend'] ? money($s['cpl']) : '—', 'oturum (site ölçümü): ' . num($s['sessions'])) . '</div>';
    echo '<form method="post" data-guard class="card">' . csrf_field() . '<h2>Kampanya bilgisi</h2><div class="grid g4">' . field('Ad', 'name', $c['name']) . field('Kod (utm_campaign)', 'slug', $c['slug'], 'text', 'Değiştirirseniz reklam bağlantılarını da güncelleyin.');
    $sel = function (string $name, string $label, array $opts, string $cur) {
        $o = '<div class="field"><label class="l">' . h($label) . '</label><select name="' . $name . '">';
        foreach ($opts as $k => $n) {
            $o .= '<option value="' . h($k) . '"' . ((string)$k === $cur ? ' selected' : '') . '>' . h($n) . '</option>';
        }
        return $o . '</select></div>';
    };
    $lp = [];
    foreach (rows('SELECT slug FROM pages WHERE status = 1 ORDER BY sort') as $p) {
        $lp[$p['slug']] = $p['slug'] . '.html';
    }
    echo $sel('platform', 'Platform', AD_PLATFORMS, $c['platform']) . $sel('status', 'Durum', AD_STATUS, $c['status']) . field('Başlangıç', 'start', $c['start'], 'date') . field('Bitiş', 'end', $c['end'], 'date') . field('Toplam bütçe (' . growth_currency() . ')', 'budget', (string)$c['budget'], 'text')
        . $sel('landing', 'Açılış sayfası', $lp, $c['landing']) . field('Amaç', 'objective', $c['objective']) . '</div><div class="field"><label class="l">Notlar (hedef kitle, anahtar kelimeler, mesaj…)</label><textarea name="notes">' . h($c['notes']) . '</textarea></div>
<div class="savebar"><button class="btn primary">Kaydet</button><a class="btn" href="' . admin_url('ads') . '">Geri</a><button class="btn danger" name="do" value="delete" data-confirm="Kampanya ve sonuç kayıtları silinsin mi?">Sil</button></div></form>';
    echo '<div class="card"><h2>Reklam bağlantıları (UTM)</h2><p class="hint" style="margin-top:-8px">Reklamda "son URL" olarak kullanın. Bu bağlantıyla gelen ziyaretler ve form başvuruları bu kampanyaya otomatik bağlanır.</p>';
    foreach (LANGS as $l) {
        $u = campaign_url($c, $l);
        echo '<div class="field"><label class="l">' . h(LANG_NAME[$l]) . '</label><input type="text" readonly value="' . h($u) . '" onclick="this.select()"></div>';
    }
    echo '<p class="hint">Not: alan adı test ortamındaysa bu bağlantılar test adresini gösterir; canlıya alınca SEO & GEO → Site adresi doğru olmalıdır.</p></div>';
    echo '<div class="card"><h2>Sonuçlar (platformdan girin)</h2><p class="hint" style="margin-top:-8px">Google Ads / Meta raporundan dönemlik toplamları girin (haftalık ya da aylık).</p><table><thead><tr><th>Dönem</th><th style="text-align:right">Harcama</th><th style="text-align:right">Gösterim</th><th style="text-align:right">Tıklama</th><th style="text-align:right">Dönüşüm</th><th>Not</th><th></th></tr></thead><tbody>';
    foreach (rows('SELECT * FROM campaign_metrics WHERE campaign_id = ? ORDER BY from_date DESC', [$c['id']]) as $m) {
        echo '<tr><td>' . h($m['from_date']) . ' → ' . h($m['to_date']) . '</td><td style="text-align:right">' . money($m['spend']) . '</td><td style="text-align:right">' . num($m['impressions']) . '</td><td style="text-align:right">' . num($m['clicks']) . '</td><td style="text-align:right">' . num($m['conversions']) . '</td><td>' . h($m['note']) . '</td>
<td><form method="post">' . csrf_field() . '<input type="hidden" name="do" value="del_metric"><input type="hidden" name="mid" value="' . $m['id'] . '"><button class="btn sm danger" data-confirm="Satır silinsin mi?">Sil</button></form></td></tr>';
    }
    echo '</tbody></table><form method="post" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">' . csrf_field() . '<input type="hidden" name="do" value="metric"><div class="grid g4">' . field('Başlangıç', 'from_date', date('Y-m-01'), 'date') . field('Bitiş', 'to_date', date('Y-m-d'), 'date') . field('Harcama', 'spend', '0') . field('Gösterim', 'impressions', '0', 'number')
        . field('Tıklama', 'clicks', '0', 'number') . field('Dönüşüm (platform)', 'conversions', '0', 'number') . field('Not', 'note', '') . '</div><button class="btn primary">Satır ekle</button></form></div>';
    $leads = rows('SELECT id, created_at, name, company, email FROM submissions WHERE utm_campaign = ? ORDER BY id DESC LIMIT 10', [$c['slug']]);
    echo '<div class="card"><h2>Bu kampanyadan gelen başvurular</h2>';
    foreach ($leads as $l) {
        echo '<div class="tree-row"><span class="nm"><a href="' . admin_url('sub_view', ['id' => $l['id']]) . '">' . h($l['company']) . '</a> <span class="hint">· ' . h($l['name']) . ' · ' . h($l['email']) . '</span></span><span class="hint">' . h($l['created_at']) . '</span></div>';
    }
    if (!$leads) {
        echo '<p class="hint">Henüz yok.</p>';
    }
    echo '</div>';
    afoot();
}

// ------------------------------------------------------------------ reports
function admin_reports(): void
{
    $ym = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['m'] ?? '')) ? $_GET['m'] : date('Y-m');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['do'] ?? '') === 'mail') {
        $r = growth_report($ym);
        $sent = [];
        $fail = '';
        foreach (notify_recipients() as $to) {
            [$ok, $err] = mail_send($to, setting('site_name', 'Site') . ' — büyüme raporu (' . $ym . ')', report_text($r));
            $ok ? $sent[] = $to : $fail = $err;
        }
        $sent ? flash('Rapor e-postayla gönderildi: ' . implode(', ', $sent)) : flash('Gönderilemedi: ' . ($fail ?: 'alıcı yok'), 'err');
        redirect_to('reports', ['m' => $ym]);
    }
    $r = growth_report($ym);
    if (($_GET['csv'] ?? '') === '1') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="buyume-raporu-' . $ym . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        csv_put($o, ['Kanal', 'Oturum', 'Görüntüleme', 'Başvuru']);
        foreach ($r['cur']['by'] as $b) {
            csv_put($o, [CHANNEL_LABELS[$b['channel']] ?? $b['channel'], $b['s'], $b['v'], $r['cur']['leads_by'][$b['channel']] ?? 0]);
        }
        csv_put($o, []);
        csv_put($o, ['Kampanya', 'Harcama', 'Tıklama', 'Başvuru', 'Başvuru başı']);
        foreach ($r['camps'] as $s) {
            csv_put($o, [$s['campaign']['name'], $s['spend'], $s['clicks'], $s['best_leads'], round($s['cpl'], 2)]);
        }
        csv_put($o, []);
        csv_put($o, ['Tamamlanan aksiyon', 'Kanal', 'Tarih', 'Sonuç']);
        foreach ($r['done'] as $a) {
            csv_put($o, [$a['title'], GROWTH_CHANNELS[$a['channel']] ?? '', substr((string)$a['done_at'], 0, 10), $a['result']]);
        }
        fclose($o);
        return;
    }
    $c = $r['cur'];
    $p = $r['prev'];
    ahead('Raporlar', 'reports', 'Aylık büyüme raporu — ziyaret, başvuru, kanallar, reklam, aksiyonlar');
    echo growth_tabs('reports') . '<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"><input type="hidden" name="a" value="reports"><select name="m" style="max-width:200px" onchange="this.form.submit()">';
    for ($i = 0; $i < 18; $i++) {
        $v = date('Y-m', strtotime("first day of -$i month"));
        echo '<option value="' . $v . '"' . ($v === $ym ? ' selected' : '') . '>' . $v . '</option>';
    }
    echo '</select><a class="btn sm" href="' . admin_url('reports', ['m' => $ym, 'csv' => 1]) . '">CSV indir</a><button type="button" class="btn sm" onclick="window.print()">Yazdır / PDF</button></form>';
    echo '<form method="post" style="margin:-4px 0 16px">' . csrf_field() . '<input type="hidden" name="do" value="mail"><button class="btn sm">Bu raporu e-postayla gönder</button> <span class="hint">→ ' . h(implode(', ', notify_recipients())) . '</span></form>';
    echo '<div class="grid g4" style="margin-bottom:20px">' . kpi('Ziyaret (oturum)', num($c['sessions']), delta_html($c['sessions'], $p['sessions']) . ' önceki aya göre') . kpi('Form başvurusu', (string)$c['leads'], delta_html($c['leads'], $p['leads']) . ' önceki aya göre')
        . kpi('Yapay zekâdan gelen', num($r['ai']), delta_html($r['ai'], $r['ai_prev']) . ' önceki aya göre') . kpi('SEO & GEO skoru', $r['seo']['end'] !== null ? (string)$r['seo']['end'] : '—', $r['seo']['start'] !== null && $r['seo']['end'] !== null ? 'ay başı ' . $r['seo']['start'] . ' → ay sonu ' . $r['seo']['end'] : 'tarama yok') . '</div>';
    echo '<div class="grid g2"><div class="card"><h2>Kanal kırılımı</h2>' . channel_bars($c) . '</div><div class="card"><h2>En çok görüntülenen sayfalar</h2><table><tbody>';
    foreach ($r['pages'] as $pg) {
        echo '<tr><td><code>/' . h($pg['slug']) . '.html</code></td><td style="text-align:right">' . num($pg['v']) . ' görüntüleme</td><td style="text-align:right" class="hint">' . num($pg['s']) . ' giriş</td></tr>';
    }
    if (!$r['pages']) {
        echo '<tr><td class="hint">Veri yok.</td></tr>';
    }
    echo '</tbody></table></div></div><div class="card"><h2>Reklam kampanyaları</h2>';
    if ($r['camps']) {
        echo '<table><thead><tr><th>Kampanya</th><th style="text-align:right">Harcama</th><th style="text-align:right">Tıklama</th><th style="text-align:right">Başvuru</th><th style="text-align:right">Başvuru başı</th></tr></thead><tbody>';
        foreach ($r['camps'] as $s) {
            echo '<tr><td><b>' . h($s['campaign']['name']) . '</b><div class="hint">' . h(AD_PLATFORMS[$s['campaign']['platform']] ?? '') . '</div></td><td style="text-align:right">' . money($s['spend']) . '</td><td style="text-align:right">' . num($s['clicks']) . '</td><td style="text-align:right">' . $s['best_leads'] . '</td><td style="text-align:right">' . ($s['best_leads'] && $s['spend'] ? money($s['cpl']) : '—') . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="hint">Bu ay kayıtlı reklam sonucu yok.</p>';
    }
    echo '</div><div class="card"><h2>Bu ay tamamlanan aksiyonlar (' . count($r['done']) . ')</h2>';
    foreach ($r['done'] as $a) {
        echo '<div class="tree-row"><span class="nm" style="font-weight:400"><b>✓ ' . h($a['title']) . '</b> <span class="pill new">' . h(GROWTH_CHANNELS[$a['channel']] ?? '') . '</span>' . ($a['result'] !== '' ? '<div class="hint">' . h($a['result']) . '</div>' : '') . '</span><span class="hint">' . h(substr((string)$a['done_at'], 0, 10)) . '</span></div>';
    }
    if (!$r['done']) {
        echo '<p class="hint">Bu ay tamamlanan aksiyon yok.</p>';
    }
    echo '<p class="hint" style="margin-top:12px">Açık aksiyon: <b>' . $r['open'] . '</b>';
    foreach ($r['overdue'] as $o) {
        echo ' · <span style="color:#b3382c">gecikmiş: ' . h($o['title']) . ' (' . h($o['due']) . ')</span>';
    }
    echo '</p></div><style>@media print{.side,.savebar,form,.pagehead+div{display:none!important}.layout{display:block}main.content{padding:0}}</style>';
    afoot();
}

// ------------------------------------------------------------------ tracking & automation
function admin_tracking(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $errs = [];
        $id = function (string $k, string $re, string $label) use (&$errs) {
            $v = strtoupper(trim((string)($_POST[$k] ?? '')));
            if ($v !== '' && !preg_match($re, $v)) {
                $errs[] = "$label biçimi geçersiz.";
                return (string)setting($k, '');
            }
            return $v;
        };
        set_setting('trk_gtm', $id('trk_gtm', '/^GTM-[A-Z0-9]{4,12}$/', 'GTM kimliği'));
        set_setting('trk_ga4', $id('trk_ga4', '/^G-[A-Z0-9]{6,14}$/', 'GA4 kimliği'));
        set_setting('trk_gads', $id('trk_gads', '/^AW-\d{6,14}$/', 'Google Ads kimliği'));
        $lab = trim((string)($_POST['trk_gads_label'] ?? ''));
        set_setting('trk_gads_label', preg_match('/^[A-Za-z0-9_\-]{4,40}$/', $lab) ? $lab : '');
        $meta = preg_replace('/\D/', '', (string)($_POST['trk_meta'] ?? ''));
        set_setting('trk_meta', strlen($meta) >= 6 && strlen($meta) <= 20 ? $meta : '');
        set_setting('trk_consent', ($_POST['trk_consent'] ?? '0') === '1' ? '1' : '0');
        set_setting('trk_first_party', ($_POST['trk_first_party'] ?? '0') === '1' ? '1' : '0');
        set_setting('growth_currency', in_array($_POST['growth_currency'] ?? '', ['EUR', 'USD', 'TRY', 'GBP'], true) ? $_POST['growth_currency'] : 'EUR');
        when_saved();
        $errs ? flash(implode(' ', $errs), 'err') : flash('İzleme ayarları kaydedildi.');
        redirect_to('tracking');
    }
    $v = function (string $k, string $d = '') { return (string)setting($k, $d); };
    $last = row('SELECT day, SUM(views) AS v, SUM(sessions) AS s FROM visits GROUP BY day ORDER BY day DESC LIMIT 1');
    $w7 = (int)val('SELECT COALESCE(SUM(sessions),0) FROM visits WHERE day >= ?', [date('Y-m-d', strtotime('-6 days'))]);
    ahead('İzleme ve otomasyon', 'tracking', 'Ölçüm etiketleri, çerez onayı, otomatik tarama ve rapor');
    echo growth_tabs('tracking');
    echo '<div class="card"><h2>Ölçüm durumu</h2><ul class="check-list"><li><span class="dot' . ($last ? ' ok' : '') . '"></span><div><b>Kendi ziyaret sayacı (çerezsiz)</b><div class="hint">' . ($last ? 'Son veri: ' . h($last['day']) . ' · son 7 gün: ' . $w7 . ' oturum.' : 'Henüz veri yok — yayındaki sitede bir sayfa açın, yönetici olarak girişliyken yapılan ziyaretler sayılmaz.') . '</div></div></li>
<li><span class="dot' . (trackers_on() ? ' ok' : '') . '"></span><div><b>Reklam / analiz etiketleri</b><div class="hint">' . (trackers_on() ? 'Tanımlı: ' . h(implode(', ', array_keys(trackers()))) . (setting('trk_consent', '1') === '1' ? ' · çerez onay bandı açık.' : ' · onay bandı kapalı (yalnızca ülkenizin kurallarına uygunsa).') : 'Tanımlı değil — kendi sayaç yeterliyse boş bırakın.') . '</div></div></li>
<li><span class="dot' . (setting('cron_last', '') !== '' ? ' ok' : '') . '"></span><div><b>Otomatik görevler</b><div class="hint">' . (setting('cron_last', '') !== '' ? 'Son çalışma: ' . h(setting('cron_last')) : 'Henüz çalışmadı — aşağıdaki cron komutunu cPanel\'e ekleyin.') . '</div></div></li></ul></div>';
    echo '<form method="post" data-guard class="card">' . csrf_field() . '<h2>Ayarlar</h2>' . checkbox('trk_first_party', $v('trk_first_party', '1') === '1', 'Kendi ziyaret sayacını kullan (çerezsiz, kişisel veri tutmaz; Do-Not-Track\'e saygılı)')
        . '<div class="grid g4">' . field('Google Tag Manager', 'trk_gtm', $v('trk_gtm'), 'text', 'GTM-XXXXXXX') . field('Google Analytics 4', 'trk_ga4', $v('trk_ga4'), 'text', 'G-XXXXXXXXXX') . field('Google Ads kimliği', 'trk_gads', $v('trk_gads'), 'text', 'AW-123456789')
        . field('Ads dönüşüm etiketi', 'trk_gads_label', $v('trk_gads_label'), 'text', 'Form gönderiminde tetiklenir') . field('Meta Pixel kimliği', 'trk_meta', $v('trk_meta'), 'text', 'Yalnızca rakam') . '<div class="field"><label class="l">Para birimi (reklam raporları)</label><select name="growth_currency">';
    foreach (['EUR' => 'Euro (EUR)', 'USD' => 'Dolar (USD)', 'TRY' => 'Türk Lirası (TRY)', 'GBP' => 'Sterlin (GBP)'] as $k => $n) {
        echo '<option value="' . $k . '"' . (growth_currency() === $k ? ' selected' : '') . '>' . h($n) . '</option>';
    }
    echo '</select></div></div>' . checkbox('trk_consent', $v('trk_consent', '1') === '1', 'Çerez onay bandı göster; etiketler yalnızca "Kabul" sonrası çalışsın (KVKK / GDPR için önerilir)')
        . '<p class="hint">Etiketler girildiğinde Gizlilik sayfasına otomatik bir çerez paragrafı eklenir. Form gönderimleri GA4/Google Ads/Meta\'ya "generate_lead / conversion / Lead" olayı olarak iletilir.</p><div class="savebar"><button class="btn primary">Kaydet</button></div></form>';
    $base = canonical_base();
    $key = cron_key();
    echo '<div class="card"><h2>Otomatik tarama ve rapor (cron)</h2><p class="hint" style="margin-top:-8px">cPanel → <b>Cron Jobs</b> → yeni kayıt. Haftalık: SEO & GEO taraması yapılır, çözülen öneriler aksiyon panosunda otomatik "tamamlandı" olur, skor geçmişi oluşur. Ayın 1\'inde bir önceki ayın raporu bildirim e-postasına gider.</p>
<div class="field"><label class="l">Önerilen komut (her gün 07:00)</label><input type="text" readonly onclick="this.select()" value="0 7 * * * curl -s &quot;' . h($base) . 'api/cron.php?key=' . h($key) . '&amp;task=all&quot; >/dev/null 2>&1"></div>
<p class="hint">Anahtar bu siteye özeldir; paylaşmayın. Elle çalıştırmak için: <a href="' . h($base) . 'api/cron.php?key=' . h($key) . '&task=weekly" target="_blank" rel="noopener">haftalık görevi şimdi çalıştır ↗</a></p></div>';
    afoot();
}
