<?php
declare(strict_types=1);

const SYS_PAGE_NAMES = ['index' => 'Ana sayfa', 'private-label' => 'Private Label', 'products' => 'Ürünler (ağaç)', 'body-care' => 'Body Care', 'home-care' => 'Home Care', 'about' => 'Kurumsal', 'contact' => 'İletişim'];

function page_title_tr(array $p): string
{
    return SYS_PAGE_NAMES[$p['slug']] ?? (ml(jd($p['title']), 'tr') ?: $p['slug']);
}

function admin_pages(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['do'] ?? '') === 'delete') {
        $p = row('SELECT * FROM pages WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
        if ($p && $p['type'] === 'custom') {
            q('DELETE FROM pages WHERE id = ?', [$p['id']]);
            when_saved();
            flash('Sayfa silindi.');
        }
        redirect_to('pages');
    }
    ahead('Sayfalar', 'pages', 'Yayındaki sayfalar, menü konumları ve içerikleri');
    echo '<p style="margin:-8px 0 18px"><a class="btn primary" href="' . admin_url('page_new') . '">+ Yeni sayfa</a></p><div class="card" style="padding:0"><table><thead><tr><th>Sayfa</th><th>Adres</th><th>Menü</th><th>Alt bilgi</th><th>Durum</th><th></th></tr></thead><tbody>';
    foreach (rows('SELECT * FROM pages ORDER BY sort, id') as $p) {
        $ft = [0 => '—', 1 => 'Keşfet', 2 => 'Kurumsal'][(int)$p['in_footer']] ?? '—';
        echo '<tr><td><b>' . h(page_title_tr($p)) . '</b> ' . ($p['type'] === 'custom' ? '<span class="pill">özel</span>' : '<span class="pill off">sistem</span>') . '</td><td><code>' . h($p['slug']) . '.html</code></td><td>' . ($p['in_nav'] ? 'Evet' : '—') . '</td><td>' . $ft . '</td><td><span class="pill ' . ($p['status'] ? 'ok' : 'off') . '">' . ($p['status'] ? 'Yayında' : 'Gizli') . '</span></td><td class="actions"><a class="btn sm" href="' . admin_url('page_edit', ['id' => $p['id']]) . '">Düzenle</a>'
            . ($p['type'] === 'custom' ? '<form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . $p['id'] . '"><button class="btn sm danger" data-confirm="Bu sayfa silinsin mi?">Sil</button></form>' : '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
    afoot();
}

function admin_page_new(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $title = post_ml('title', false);
        $slug = slugify(post_str('slug', 60) ?: ($title['tr'] ?: $title['en']));
        if ($slug === '' || in_array($slug, ['index', 'admin', 'api', 'assets', 'app', 'data', 'uploads'], true) || row('SELECT id FROM pages WHERE slug = ?', [$slug])) {
            flash('Bu adres kullanılamıyor ya da zaten var. Başka bir adres seçin.', 'err');
        } elseif ($title['tr'] === '') {
            flash('Türkçe başlık zorunlu.', 'err');
        } else {
            q('INSERT INTO pages(slug,type,status,in_nav,in_footer,sort,title,meta,h1,lead,cta,blocks,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$slug, 'custom', 0, 0, 0, 100, je($title), '{}', je($title), '{}', 1, '[]', date('Y-m-d H:i:s')]);
            when_saved();
            flash('Sayfa oluşturuldu (gizli). İçeriği ekleyip yayınlayın.');
            redirect_to('page_edit', ['id' => (int)db()->lastInsertId()]);
        }
    }
    ahead('Yeni sayfa', 'pages');
    echo '<form method="post" class="card" style="max-width:720px">' . csrf_field() . langbar() . ml_input('title', $_POST['title'] ?? [], 'Sayfa başlığı', 'input')
        . field('Adres (boşsa başlıktan üretilir)', 'slug', $_POST['slug'] ?? '', 'text', 'Örn: sss → /sss.html') . '<button class="btn primary">Oluştur</button> <a class="btn" href="' . admin_url('pages') . '">Vazgeç</a></form>';
    afoot();
}

function admin_page_edit(): void
{
    $p = row('SELECT * FROM pages WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$p) {
        redirect_to('pages');
    }
    $custom = $p['type'] === 'custom';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $slug = $p['slug'];
        if ($custom) {
            $ns = slugify(post_str('slug', 60));
            if ($ns !== '' && $ns !== $slug) {
                if (row('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$ns, $p['id']]) || in_array($ns, ['index', 'admin', 'api', 'assets', 'app', 'data', 'uploads'], true)) {
                    flash('Bu adres kullanılamıyor.', 'err');
                    redirect_to('page_edit', ['id' => $p['id']]);
                }
                $slug = $ns;
            }
        }
        q('UPDATE pages SET slug=?, status=?, in_nav=?, in_footer=?, sort=?, title=?, meta=?, h1=?, lead=?, cta=?, blocks=?, updated_at=? WHERE id=?', [
            $slug, (int)($_POST['status'] ?? 0), (int)($_POST['in_nav'] ?? 0), max(0, min(2, (int)($_POST['in_footer'] ?? 0))), (int)($_POST['sort'] ?? 100),
            je(post_ml('title', false)), je(post_ml('meta', false)),
            $custom ? je(post_ml('h1')) : $p['h1'], $custom ? je(post_ml('lead')) : $p['lead'], $custom ? (int)($_POST['cta'] ?? 0) : $p['cta'],
            $custom ? je(parse_blocks()) : $p['blocks'], date('Y-m-d H:i:s'), $p['id'],
        ]);
        when_saved();
        flash('Sayfa kaydedildi.');
        redirect_to('page_edit', ['id' => $p['id']]);
    }
    $title = page_title_tr($p);
    ahead($title, 'pages', ($custom ? 'Özel sayfa' : 'Sistem sayfası') . ' · /' . $p['slug'] . '.html');
    echo '<form method="post" data-guard>' . csrf_field() . langbar();
    echo '<div class="card"><h2>Yayın ve menü</h2><div class="grid g4">
<div class="field"><label class="l">Durum</label><select name="status"><option value="1"' . ($p['status'] ? ' selected' : '') . '>Yayında</option><option value="0"' . (!$p['status'] ? ' selected' : '') . '>Gizli</option></select></div>
<div class="field"><label class="l">Üst menüde göster</label><select name="in_nav"><option value="1"' . ($p['in_nav'] ? ' selected' : '') . '>Evet</option><option value="0"' . (!$p['in_nav'] ? ' selected' : '') . '>Hayır</option></select></div>
<div class="field"><label class="l">Alt bilgide göster</label><select name="in_footer"><option value="0"' . ($p['in_footer'] == 0 ? ' selected' : '') . '>Hayır</option><option value="1"' . ($p['in_footer'] == 1 ? ' selected' : '') . '>Keşfet sütunu</option><option value="2"' . ($p['in_footer'] == 2 ? ' selected' : '') . '>Alt satır (kurumsal)</option></select></div>
<div class="field"><label class="l">Sıra (küçük önce)</label><input type="number" name="sort" value="' . (int)$p['sort'] . '"></div></div>';
    if ($custom) {
        echo field('Adres', 'slug', $p['slug'], 'text', 'Sayfa adresi: /' . $p['slug'] . '.html — değiştirirseniz eski bağlantılar çalışmaz.');
    }
    echo '</div><div class="card"><h2>Arama motoru (SEO)</h2>'
        . ml_input('title', jd($p['title']), $custom ? 'Sayfa başlığı (menüde ve sekmede görünür)' : 'Sekme başlığı (boş bırakılırsa varsayılan kullanılır)', 'input', $custom)
        . ml_input('meta', jd($p['meta']), 'Meta açıklama (≈150 karakter)', 'area', false) . '</div>';
    if ($custom) {
        echo '<div class="card"><h2>Sayfa üstü</h2>' . ml_input('h1', jd($p['h1']), 'Ana başlık (basit biçim: <em>vurgulu</em> kelime kullanılabilir)', 'input', false) . ml_input('lead', jd($p['lead']), 'Giriş cümlesi', 'area', false)
            . checkbox('cta', (int)$p['cta'] === 1, 'Sayfa sonunda "Teklif al" bandını göster') . '</div>';
        echo '<div class="card"><h2>İçerik blokları</h2><p class="hint" style="margin-top:-8px">Blokları ekleyin, ↑↓ ile sıralayın. Her metin 5 dilde girilir; boş bırakılan dilde Türkçe gösterilir.</p><div id="blocks">';
        foreach (jd($p['blocks'], []) as $i => $b) {
            echo render_block('e' . $i, $b);
        }
        echo '</div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px"><span class="hint" style="align-self:center">Blok ekle:</span>';
        foreach (block_specs() as $k => $s) {
            echo '<button type="button" class="btn sm" data-addblock="' . $k . '">+ ' . h($s[0]) . '</button>';
        }
        echo '</div></div>';
        foreach (block_specs() as $k => $s) {
            echo '<template id="tpl-' . $k . '">' . render_block('__B__', ['type' => $k]) . '</template>';
            if ($s[2] !== null) {
                echo '<template id="tpl-item-' . $k . '">' . render_item($k, '__B__', '__I__', []) . '</template>';
            }
        }
    } else {
        $links = [
            'index' => [['strings', ['g' => 'home'], 'Ana sayfa metinleri'], ['hero', [], 'Ana sayfa slider görselleri'], ['settings', [], 'Ana sayfa görselleri (ayarlar)']],
            'private-label' => [['strings', ['g' => 'pl'], 'Private Label metinleri'], ['tubes', [], 'Alüminyum tüp modelleri']],
            'products' => [['strings', ['g' => 'products'], 'Sayfa metinleri'], ['catalog', [], 'Ürün ağacı']],
            'body-care' => [['catalog', [], 'Body Care ürünleri'], ['strings', ['g' => 'products'], 'Sayfa metinleri']],
            'home-care' => [['catalog', [], 'Home Care ürünleri'], ['strings', ['g' => 'products'], 'Sayfa metinleri']],
            'about' => [['strings', ['g' => 'about'], 'Kurumsal metinleri']], 'contact' => [['strings', ['g' => 'contact'], 'İletişim ve form metinleri'], ['settings', [], 'Telefon / e-posta']],
        ][$p['slug']] ?? [];
        echo '<div class="card"><h2>Sayfa içeriği</h2><p class="hint" style="margin-top:-8px">Bu sistem sayfasının yerleşimi sabittir; içeriği şu bölümlerden düzenlenir:</p><p>';
        foreach ($links as [$a, $q, $t]) {
            echo '<a class="btn" style="margin:0 8px 8px 0" href="' . admin_url($a, $q) . '">' . h($t) . ' →</a>';
        }
        echo '</p></div>';
    }
    echo '<div class="savebar"><button class="btn primary">Kaydet</button><a class="btn" href="../' . ($p['slug'] === 'index' ? '' : h($p['slug']) . '.html') . '" target="_blank" rel="noopener">Sayfayı aç ↗</a></div></form>';
    afoot();
}
