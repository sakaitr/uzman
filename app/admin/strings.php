<?php
declare(strict_types=1);

function string_groups(): array
{
    return [
        'home' => 'Ana sayfa', 'steps' => 'Süreç adımları (ana sayfa + Private Label)', 'pl' => 'Private Label sayfası', 'products' => 'Ürün sayfaları', 'about' => 'Kurumsal sayfası',
        'contact' => 'İletişim ve form', 'cta' => 'Teklif bandı (sayfa sonları)', 'chrome' => 'Menü, üst ve alt bilgi', 'seo' => 'SEO başlıkları (sistem sayfaları)',
    ];
}

/** Which editor group a string key belongs to (null = managed elsewhere, hidden). */
function string_group(string $k): ?string
{
    if (preg_match('/^(cat_|p_|h_|t_)/', $k) || preg_match('/^desc_(deo|rollon|hair|edp|mist|bamboo|air|room|reed)$/', $k)) {
        return null;
    }
    if (preg_match('/^(title_|desc_index)/', $k)) {
        return 'seo';
    }
    if (preg_match('/^s\d_/', $k)) {
        return 'steps';
    }
    if (preg_match('/^(hero_|cta_start|m\d$|manifesto|coll_|pl_h2|pl_lead|pl_btn|pw_|ex_|val_h2|v\d_)/', $k)) {
        return 'home';
    }
    if (preg_match('/^(pl_h1|pl_page_lead|pl_proc_|tube_|all$)/', $k)) {
        return 'pl';
    }
    if (preg_match('/^(pr_|bc_|hc_|back_tree|sizes|soon|explore)/', $k)) {
        return 'products';
    }
    if (preg_match('/^(ab_|tl)/', $k)) {
        return 'about';
    }
    if (preg_match('/^(ct_|f_)/', $k)) {
        return 'contact';
    }
    if (preg_match('/^cta_/', $k)) {
        return 'cta';
    }
    return 'chrome';
}

function admin_strings(): void
{
    $groups = string_groups();
    $g = (string)($_GET['g'] ?? 'home');
    if (!isset($groups[$g])) {
        $g = 'home';
    }
    $search = trim((string)($_GET['q'] ?? ''));
    $keys = [];
    foreach (rows('SELECT DISTINCT k FROM strings ORDER BY rowid') as $r) {
        $grp = string_group($r['k']);
        if ($grp === $g || ($search !== '' && $grp !== null)) {
            $keys[] = $r['k'];
        }
    }
    $vals = [];
    foreach (rows('SELECT k, lang, v FROM strings') as $r) {
        $vals[$r['k']][$r['lang']] = $r['v'];
    }
    if ($search !== '') {
        $keys = array_values(array_filter($keys, function ($k) use ($vals, $search) {
            return stripos($k, $search) !== false || stripos(implode(' ', $vals[$k] ?? []), $search) !== false;
        }));
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['do'] ?? '') === 'import') {
        $f = $_FILES['csv']['tmp_name'] ?? '';
        if (!$f || !is_uploaded_file($f) && !is_file($f)) {
            flash('CSV dosyası seçin.', 'err');
            redirect_to('strings');
        }
        $raw = (string)file_get_contents($f);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $delim = substr_count(strtok($raw, "\n"), ';') > substr_count(strtok($raw, "\n"), ',') ? ';' : ',';
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $raw);
        rewind($fh);
        $head = csv_get($fh, $delim);
        $cols = array_map('strtolower', array_map('trim', (array)$head));
        $n = $unknown = 0;
        if (($cols[0] ?? '') !== 'key') {
            flash('İlk sütun "key" olmalı (dışa aktarılan dosyanın başlığını değiştirmeyin).', 'err');
            redirect_to('strings');
        }
        $st = db()->prepare('INSERT INTO strings(k, lang, v) VALUES(?,?,?) ON CONFLICT(k, lang) DO UPDATE SET v = excluded.v');
        $cur = [];
        foreach (rows('SELECT k, lang, v FROM strings') as $r) {
            $cur[$r['k']][$r['lang']] = $r['v'];
        }
        while (($row = csv_get($fh, $delim)) !== false) {
            $k = trim((string)($row[0] ?? ''));
            if ($k === '') {
                continue;
            }
            if (!isset($cur[$k]) || string_group($k) === null) {
                $unknown++;
                continue;
            }
            foreach ($cols as $i => $l) {
                if ($i > 0 && in_array($l, LANGS, true) && isset($row[$i])) {
                    $new = clean_html(preg_replace("/^'([=+\\-@])/", '$1', (string)$row[$i]));
                    if ($new !== ($cur[$k][$l] ?? '')) {
                        $st->execute([$k, $l, $new]);
                        $n++;
                    }
                }
            }
        }
        when_saved();
        flash("$n metin güncellendi" . ($unknown ? ", $unknown bilinmeyen/yönetilmeyen anahtar atlandı" : '') . '.');
        redirect_to('strings');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $st = db()->prepare('INSERT INTO strings(k, lang, v) VALUES(?,?,?) ON CONFLICT(k, lang) DO UPDATE SET v = excluded.v');
        $n = 0;
        foreach ((array)($_POST['s'] ?? []) as $k => $langs) {
            if (!isset($vals[$k]) || string_group((string)$k) === null) {
                continue;
            }
            foreach (LANGS as $l) {
                if (isset($langs[$l])) {
                    $new = clean_html((string)$langs[$l]);
                    if ($new !== ($vals[$k][$l] ?? '')) {
                        $st->execute([$k, $l, $new]);
                        $n++;
                    }
                }
            }
        }
        when_saved();
        flash($n . ' metin güncellendi.');
        header('Location: ' . admin_url('strings', ['g' => $g] + ($search !== '' ? ['q' => $search] : [])));
        exit;
    }
    ahead('Site metinleri', 'strings', 'Sayfalardaki tüm sabit metinler — 5 dilde');
    echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">';
    foreach ($groups as $k => $n) {
        echo '<a class="btn sm' . ($k === $g && $search === '' ? ' primary' : '') . '" href="' . admin_url('strings', ['g' => $k]) . '">' . h($n) . '</a>';
    }
    echo '</div><div class="card" style="padding:14px 18px;display:flex;gap:14px;flex-wrap:wrap;align-items:center"><b style="font-size:.9rem">Çeviri dosyası (CSV)</b><a class="btn sm" href="' . admin_url('strings_csv', ['t' => csrf_token()]) . '">Tüm metinleri dışa aktar</a>
<form method="post" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">' . csrf_field() . '<input type="hidden" name="do" value="import"><input type="file" name="csv" accept=".csv,text/csv" required><button class="btn sm">İçe aktar</button></form><span class="hint">Çevirmene verip geri alabilirsiniz; yalnızca mevcut anahtarlar güncellenir.</span></div>
<form method="get" style="display:flex;gap:8px;margin-bottom:16px;max-width:420px"><input type="hidden" name="a" value="strings"><input type="text" name="q" value="' . h($search) . '" placeholder="Tüm metinlerde ara…"><button class="btn">Ara</button></form>';
    echo '<form method="post" data-guard>' . csrf_field() . langbar() . '<div class="card" style="padding:6px 22px"><p class="hint" style="margin:14px 0 0">Basit biçim kullanılabilir: <code>&lt;em&gt;vurgu&lt;/em&gt;</code> (altın renkli italik), <code>&lt;br&gt;</code> (satır sonu), <code>&amp;amp;</code> (&amp; işareti).</p>';
    if (!$keys) {
        echo '<p class="hint">Sonuç yok.</p>';
    }
    foreach ($keys as $k) {
        $tr = plain($vals[$k]['tr'] ?? '');
        $long = mb_strlen((string)($vals[$k]['tr'] ?? '')) > 70;
        echo '<div class="strrow"><div><b style="font-size:.88rem">' . h(mb_strimwidth($tr, 0, 46, '…')) . '</b><br><code>' . h($k) . '</code></div><div>';
        $v = [];
        foreach (LANGS as $l) {
            $v[$l] = $vals[$k][$l] ?? '';
        }
        $html = ml_input('s[' . $k . ']', $v, '', $long ? 'area' : 'input', false);
        echo $html . '</div></div>';
    }
    echo '</div><div class="savebar"><button class="btn primary">Kaydet</button></div></form>';
    afoot();
}

function admin_strings_csv(): void
{
    if (!hash_equals(csrf_token(), (string)($_GET['t'] ?? ''))) {
        http_response_code(400);
        exit('Geçersiz istek.');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="metinler-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    csv_put($o, array_merge(['key'], LANGS));
    $vals = [];
    foreach (rows('SELECT k, lang, v FROM strings') as $r) {
        $vals[$r['k']][$r['lang']] = $r['v'];
    }
    foreach ($vals as $k => $langs) {
        if (string_group((string)$k) === null) {
            continue;
        }
        csv_put($o, array_merge([$k], array_map(function ($l) use ($langs) { $v = (string)($langs[$l] ?? ''); return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }, LANGS)));
    }
    fclose($o);
}
