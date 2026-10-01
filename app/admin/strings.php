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
    echo '</div><form method="get" style="display:flex;gap:8px;margin-bottom:16px;max-width:420px"><input type="hidden" name="a" value="strings"><input type="text" name="q" value="' . h($search) . '" placeholder="Tüm metinlerde ara…"><button class="btn">Ara</button></form>';
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
