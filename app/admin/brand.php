<?php
declare(strict_types=1);

function brand_regen_icons(): void
{
    $mask = brand_path('brand_logo_mask');
    if ($mask === '' || !preg_match('/\.png$/i', $mask) || !extension_loaded('gd') || (string)setting('brand_logo_mode', 'mask') === 'color') {
        return;
    }
    $im = gd_load(UZ_ROOT . '/' . $mask, 'image/png');
    if (!$im) {
        return;
    }
    $seeds = brand_active_seeds();
    $tag = substr(bin2hex(random_bytes(3)), 0, 6);
    set_setting('brand_favicon', brand_icon($im, 64, null, $seeds['accent'], 0.92, "uploads/brand/favicon-$tag.png"));
    set_setting('brand_apple', brand_icon($im, 180, $seeds['bg'], $seeds['accent'], 0.66, "uploads/brand/apple-touch-$tag.png"));
}

function admin_brand(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'name') {
            foreach (['brand_word' => 24, 'brand_foot_sub' => 40, 'foot_word' => 24, 'foot_line' => 80, 'site_name' => 80] as $k => $max) {
                set_setting($k, mb_substr(trim(strip_tags((string)($_POST[$k] ?? ''))), 0, $max));
            }
            $sub = post_ml('brand_sub', false);
            $st = db()->prepare('INSERT INTO strings(k, lang, v) VALUES(?,?,?) ON CONFLICT(k, lang) DO UPDATE SET v = excluded.v');
            foreach ($sub as $l => $v) {
                $st->execute(['brand_sub', $l, mb_substr(strip_tags($v), 0, 60)]);
            }
            flash('Marka adı kaydedildi.');
        } elseif ($do === 'logo') {
            if (!empty($_FILES['logo']['name'])) {
                $mode = ($_POST['logo_mode'] ?? 'mask') === 'color' ? 'color' : 'mask';
                $hint = in_array($_POST['bg_hint'] ?? '', ['auto', 'dark', 'light'], true) ? $_POST['bg_hint'] : 'auto';
                [$ok, $msg, $set] = brand_process_logo($_FILES['logo'], $mode, $hint);
                if ($ok) {
                    foreach ($set as $k => $v) {
                        set_setting($k, (string)$v);
                    }
                    flash($msg);
                } else {
                    flash($msg, 'err');
                }
            } elseif (($_POST['logo_mode'] ?? '') !== '') {
                set_setting('brand_logo_mode', $_POST['logo_mode'] === 'color' ? 'color' : 'mask');
                brand_regen_icons();
                flash('Logo gösterim biçimi güncellendi.');
            }
        } elseif ($do === 'logo_reset') {
            foreach (['brand_logo_mask', 'brand_logo_color', 'brand_logo_ratio', 'brand_favicon', 'brand_apple', 'brand_logo_mode'] as $k) {
                set_setting($k, '');
            }
            flash('Varsayılan logoya dönüldü.');
        } elseif ($do === 'colors') {
            $presets = brand_presets();
            $pick = (string)($_POST['preset'] ?? '');
            if ($pick !== '' && isset($presets[$pick])) {
                if ($presets[$pick][2]) {
                    set_setting('theme', $pick);
                } else {
                    set_setting('brand_custom', je($presets[$pick][1]));
                    set_setting('theme', 'custom');
                }
            } else {
                $cs = [];
                foreach (['bg', 'text', 'accent', 'accent2'] as $k) {
                    $v = trim((string)($_POST['c_' . $k] ?? ''));
                    $cs[$k] = $k === 'accent2' && ($v === '' || ($_POST['c_accent2_on'] ?? '') !== '1') ? '' : hex_norm($v, brand_custom_seeds()[$k]);
                }
                set_setting('brand_custom', je($cs));
                set_setting('theme', 'custom');
            }
            brand_regen_icons();
            flash('Renk paleti kaydedildi.');
        }
        when_saved();
        redirect_to('brand');
    }
    $theme = brand_theme();
    $seeds = brand_active_seeds();
    $custom = brand_custom_seeds();
    ahead('Marka ve görünüm', 'brand', 'Marka adı, logo ve renk paleti — site ve yönetim paneli buna göre boyanır');
    // ---- name
    echo '<form method="post" class="card" data-guard>' . csrf_field() . '<input type="hidden" name="do" value="name"><h2>Marka adı</h2><div class="grid g2">'
        . field('Üst menüdeki marka yazısı', 'brand_word', brand_word(), 'text', 'Logonun yanında büyük harflerle görünür. Örn: UZMAN')
        . field('Alt bilgideki küçük yazı', 'brand_foot_sub', (string)setting('brand_foot_sub', ''), 'text', 'Alt bilgideki logonun altında. Örn: COSMETIC')
        . field('Alt bilgideki dev yazı', 'foot_word', (string)setting('foot_word', ''), 'text', 'Boşsa marka yazısı kullanılır.') . field('Alt bilgi satırı', 'foot_line', (string)setting('foot_line', ''), 'text', 'Telif satırının yanında: şehir · ülke')
        . field('Site adı (sekme başlığı, e-posta, SEO)', 'site_name', (string)setting('site_name', '')) . '</div>' . langbar() . ml_input('brand_sub', (function () { $o = []; foreach (LANGS as $l) { $o[$l] = (string)val("SELECT v FROM strings WHERE k = 'brand_sub' AND lang = ?", [$l]); } return $o; })(), 'Marka altı yazısı (üst menü, dil bazlı)', 'input', false)
        . '<p class="hint">Sayfa metinlerindeki marka geçişleri (örn. "Uzman Kozmetik") <a href="' . admin_url('strings') . '">Site metinleri</a> bölümünden düzenlenir.</p><button class="btn primary">Kaydet</button></form>';
    // ---- logo
    $mask = brand_path('brand_logo_mask');
    $col = brand_path('brand_logo_color');
    $mode = (string)setting('brand_logo_mode', 'mask');
    $acc = $seeds['accent'];
    echo '<form method="post" enctype="multipart/form-data" class="card" data-guard>' . csrf_field() . '<input type="hidden" name="do" value="logo"><h2>Logo</h2><div class="grid g2"><div>';
    echo '<p class="hint" style="margin-top:-6px">En iyi sonuç: arka planı <b>şeffaf PNG</b> ya da <b>SVG</b>. Opak (JPG/PNG) logolarda arka plan otomatik ayıklanır. Favicon ve uygulama ikonu otomatik üretilir.</p>
<div class="field"><label class="l">Logo dosyası (PNG, JPG, WebP, SVG — en fazla 8 MB)</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"></div>
<div class="field"><label class="l">Gösterim biçimi</label><label style="display:flex;gap:8px;margin-bottom:6px"><input type="radio" name="logo_mode" value="mask"' . ($mode !== 'color' ? ' checked' : '') . '> Tek renk — seçili temanın vurgu rengiyle boyanır (önerilir)</label><label style="display:flex;gap:8px"><input type="radio" name="logo_mode" value="color"' . ($mode === 'color' ? ' checked' : '') . '> Orijinal renkleriyle göster</label></div>
<div class="field"><label class="l">Opak görsellerde arka plan</label><select name="bg_hint"><option value="auto">Otomatik algıla</option><option value="dark">Koyu arka plan (logo açık renkli)</option><option value="light">Açık arka plan (logo koyu renkli)</option></select></div>
<button class="btn primary">Yükle / uygula</button> ';
    if ($mask !== '') {
        echo '<button class="btn" name="do" value="logo_reset" data-confirm="Varsayılan logoya dönülsün mü?">Varsayılana dön</button>';
    }
    echo '</div><div><label class="l">Önizleme</label><div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
    $mk = function (string $bg, string $fg) use ($mask, $col, $mode) {
        $url = $mask !== '' ? '../' . ($mode === 'color' && $col !== '' ? $col : $mask) : '../assets/img/logo-mask.png';
        $style = $mode === 'color' && $col !== '' ? 'background:url(' . h($url) . ') center/contain no-repeat' : 'background:' . $fg . ';-webkit-mask:url(' . h($url) . ') center/contain no-repeat;mask:url(' . h($url) . ') center/contain no-repeat';
        return '<div style="height:120px;border-radius:10px;background:' . $bg . ';display:grid;place-items:center"><i style="display:block;width:90px;height:90px;' . $style . '"></i></div>';
    };
    echo $mk('#0e0f12', $acc) . $mk('#f4f1ea', $acc) . '</div>';
    if ($mask === '') {
        echo '<p class="hint">Şu an varsayılan (şablon) logo kullanılıyor.</p>';
    }
    $fv = brand_path('brand_favicon');
    if ($fv !== '') {
        echo '<p class="hint" style="display:flex;align-items:center;gap:10px">Favicon: <img src="../' . h($fv) . '" width="32" height="32" alt="" style="background:#eee;border-radius:6px"> <img src="../' . h(brand_path('brand_apple')) . '" width="48" height="48" alt="" style="border-radius:10px"> uygulama ikonu</p>';
    }
    echo '</div></div></form>';
    // ---- colours
    echo '<div class="card"><h2>Renkler</h2><p class="hint" style="margin-top:-8px">Hazır bir palet seçin ya da kendi renklerinizi girin. Ara tonlar (kart, çizgi, soluk yazı, düğme gradyanı…) otomatik türetilir. Açık zeminli paletlerde arka plan videoları gizlenir.</p><form method="post">' . csrf_field() . '<input type="hidden" name="do" value="colors"><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-bottom:22px">';
    foreach (brand_presets() as $k => [$name, $sd, $native]) {
        $on = ($theme === $k) || ($theme === 'custom' && !$native && brand_custom_seeds() == $sd + ['accent2' => $sd['accent2']]);
        echo '<button name="preset" value="' . h($k) . '" class="preset' . ($on ? ' on' : '') . '" style="--b:' . $sd['bg'] . ';--t:' . $sd['text'] . ';--a:' . $sd['accent'] . ';--a2:' . ($sd['accent2'] ?: $sd['accent']) . '"><span class="sw"><i style="background:' . $sd['bg'] . '"></i><i style="background:' . $sd['accent'] . '"></i><i style="background:' . ($sd['accent2'] ?: $sd['text']) . '"></i></span><b>' . h($name) . '</b><small>' . ($on ? 'Kullanımda' : 'Uygula') . '</small></button>';
    }
    echo '</div></form><form method="post" data-guard id="brandForm">' . csrf_field() . '<input type="hidden" name="do" value="colors"><h3>Özel renkler' . ($theme === 'custom' ? ' <span class="pill ok">kullanımda</span>' : '') . '</h3><div class="grid g2"><div>
<div class="grid g2">';
    $ci = function (string $k, string $label, string $val, string $hint = '') {
        return '<div class="field"><label class="l">' . h($label) . '</label><div style="display:flex;gap:8px;align-items:center"><input type="color" data-seed="' . $k . '" name="c_' . $k . '" value="' . h($val ?: '#888888') . '" style="width:56px;height:40px;padding:2px;border-radius:8px"><code data-hex="' . $k . '">' . h($val) . '</code></div>' . ($hint ? '<div class="hint">' . h($hint) . '</div>' : '') . '</div>';
    };
    echo $ci('bg', 'Zemin', $custom['bg'], 'Sayfa arka planı') . $ci('text', 'Yazı', $custom['text'], 'Ana metin rengi') . $ci('accent', 'Vurgu', $custom['accent'], 'Düğme, çizgi, logo, bağlantı') . $ci('accent2', 'İkinci vurgu', $custom['accent2'] ?: $custom['accent'], 'Parıltı ve şişe sıvısı tonu');
    echo '</div><label style="display:flex;gap:8px;margin:2px 0 14px"><input type="checkbox" name="c_accent2_on" value="1" data-seed-on="accent2"' . ($custom['accent2'] !== '' ? ' checked' : '') . '> İkinci vurguyu kullan</label><button class="btn primary">Özel paleti uygula</button></div>
<div><div id="brandPrev" class="bp"><div class="bp-h"><i class="bp-logo"></i><b>' . h(brand_word()) . '</b></div><h4>Markanızın imzası, bizim ustalığımız.</h4><p>Önizleme — vurgulu <em>kelime</em> ve ikincil metin.</p><div class="bp-b"><span class="bp-solid">Teklif al</span><span class="bp-out">İncele</span></div></div>
<table id="brandContrast" style="margin-top:12px"><tbody></tbody></table></div></div></form>';
    $rows = brand_contrast($seeds);
    echo '<h3 style="margin-top:22px">Kullanımdaki paletin erişilebilirlik kontrolü</h3><table><tbody>';
    foreach ($rows as [$lab, $r, $min, $okk]) {
        echo '<tr><td>' . h($lab) . '</td><td style="text-align:right">' . $r . ':1</td><td style="width:110px;text-align:right"><span class="pill ' . ($okk ? 'ok' : 'warn') . '">' . ($okk ? 'Uygun' : 'Düşük (' . $min . ' gerekli)') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<p class="hint">Not: yazı tipleri (başlık/gövde) bu sürümde sabittir. Tema değiştirici ve arka plan videosu <a href="' . admin_url('settings') . '">Ayarlar</a> sayfasındadır.</p>';
    afoot();
}
