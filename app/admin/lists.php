<?php
declare(strict_types=1);

// ---------------------------------------------------------------- tubes
function tube_row(?array $m, array $groups): string
{
    $id = $m ? (string)$m['id'] : 'n__N__';
    $nm = $m ? "m[$id]" : "new[__N__]";
    $opts = '';
    foreach ($groups as $g) {
        $opts .= '<option value="' . h($g['slug']) . '"' . ($m && $m['grp'] === $g['slug'] ? ' selected' : '') . '>' . h(ml(jd($g['name']), 'tr')) . '</option>';
    }
    return '<tr data-row><td style="width:44px"><input type="hidden" name="ord[]" value="' . h($id) . '"><span class="actions" style="flex-direction:column"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button></span></td>
<td style="min-width:260px">' . img_input($nm . '[image]', $m ? (string)$m['image'] : '', 'Görsel') . '</td>
<td><label class="l">Model adı</label><input type="text" name="' . $nm . '[label]" value="' . h($m['label'] ?? '') . '"></td>
<td><label class="l">Ölçü</label><input type="text" name="' . $nm . '[dims]" value="' . h($m['dims'] ?? '') . '" placeholder="Ø35 × 115 mm"></td>
<td><label class="l">Grup</label><select name="' . $nm . '[grp]">' . $opts . '</select></td>
<td><label><input type="hidden" name="' . $nm . '[active]" value="0"><input type="checkbox" name="' . $nm . '[active]" value="1"' . (!$m || $m['active'] ? ' checked' : '') . '> Yayında</label></td>
<td><button type="button" class="btn sm danger" data-del="tr">Sil</button></td></tr>';
}

function admin_tubes(): void
{
    $groups = rows('SELECT * FROM pl_groups ORDER BY sort');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (($_POST['do'] ?? '') === 'groups') {
            foreach ($groups as $g) {
                q('UPDATE pl_groups SET name = ? WHERE slug = ?', [je(post_ml('g_' . $g['slug'], false)), $g['slug']]);
            }
            flash('Grup adları kaydedildi.');
        } else {
            $keep = [];
            $valid = array_column($groups, 'slug');
            foreach ((array)($_POST['ord'] ?? []) as $pos => $key) {
                $key = (string)$key;
                if (strpos($key, 'n') === 0) {
                    $d = (array)((($_POST['new'] ?? [])[substr($key, 1)]) ?? []);
                    $img = safe_path((string)($d['image'] ?? ''));
                    if ($img === '') {
                        continue;
                    }
                    q('INSERT INTO pl_models(grp,label,dims,image,sort,active) VALUES(?,?,?,?,?,?)', [in_array($d['grp'] ?? '', $valid, true) ? $d['grp'] : $valid[0], post_clip($d['label'] ?? ''), post_clip($d['dims'] ?? ''), $img, $pos, (int)($d['active'] ?? 1)]);
                    $keep[] = (int)db()->lastInsertId();
                } else {
                    $d = (array)((($_POST['m'] ?? [])[$key]) ?? []);
                    if (!$d) {
                        continue;
                    }
                    q('UPDATE pl_models SET grp=?, label=?, dims=?, image=?, sort=?, active=? WHERE id=?', [in_array($d['grp'] ?? '', $valid, true) ? $d['grp'] : $valid[0], post_clip($d['label'] ?? ''), post_clip($d['dims'] ?? ''), safe_path((string)($d['image'] ?? '')), $pos, (int)($d['active'] ?? 1), (int)$key]);
                    $keep[] = (int)$key;
                }
            }
            foreach (array_column(rows('SELECT id FROM pl_models'), 'id') as $id) {
                if (!in_array((int)$id, $keep, true)) {
                    q('DELETE FROM pl_models WHERE id = ?', [$id]);
                }
            }
            flash('Tüp modelleri kaydedildi.');
        }
        when_saved();
        redirect_to('tubes');
    }
    $models = rows('SELECT * FROM pl_models ORDER BY sort, id');
    ahead('Private Label tüpler', 'tubes', count($models) . ' alüminyum tüp modeli — Private Label sayfasındaki galeri');
    echo '<form method="post" data-guard>' . csrf_field() . '<div class="card" style="padding:6px"><table><tbody id="rows-t">';
    foreach ($models as $m) {
        echo tube_row($m, $groups);
    }
    echo '</tbody></table></div><template id="tpl-t">' . tube_row(null, $groups) . '</template>
<div class="savebar"><button class="btn primary">Kaydet</button><button type="button" class="btn" data-addrow="tpl-t" data-into="rows-t">+ Yeni model</button></div></form>
<form method="post" class="card" style="margin-top:24px" data-guard>' . csrf_field() . '<input type="hidden" name="do" value="groups"><h2>Filtre grupları</h2>' . langbar() . '<div class="grid g3">';
    foreach ($groups as $g) {
        echo '<div>' . ml_input('g_' . $g['slug'], jd($g['name']), $g['slug'], 'input', false) . '</div>';
    }
    echo '</div><button class="btn sm primary">Grup adlarını kaydet</button></form>';
    afoot();
}

function post_clip($v, int $n = 120): string
{
    return mb_substr(trim((string)$v), 0, $n);
}

// ---------------------------------------------------------------- hero slides
function hero_row(?array $s): string
{
    $id = $s ? (string)$s['id'] : 'n__N__';
    $nm = $s ? "s[$id]" : "new[__N__]";
    return '<tr data-row><td style="width:44px"><input type="hidden" name="ord[]" value="' . h($id) . '"><span class="actions" style="flex-direction:column"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button></span></td>
<td style="min-width:280px">' . img_input($nm . '[image]', $s ? (string)$s['image'] : '', 'Görsel (şeffaf PNG/WebP, dikey 4:5 önerilir)') . '</td>
<td><label class="l">Alt yazı (noktaların üstünde görünür)</label><input type="text" name="' . $nm . '[label]" value="' . h($s['label'] ?? '') . '"></td>
<td><label><input type="hidden" name="' . $nm . '[active]" value="0"><input type="checkbox" name="' . $nm . '[active]" value="1"' . (!$s || $s['active'] ? ' checked' : '') . '> Yayında</label></td>
<td><button type="button" class="btn sm danger" data-del="tr">Sil</button></td></tr>';
}

function admin_hero(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $keep = [];
        foreach ((array)($_POST['ord'] ?? []) as $pos => $key) {
            $key = (string)$key;
            if (strpos($key, 'n') === 0) {
                $d = (array)((($_POST['new'] ?? [])[substr($key, 1)]) ?? []);
                $img = safe_path((string)($d['image'] ?? ''));
                if ($img === '') {
                    continue;
                }
                q('INSERT INTO hero_slides(image,label,sort,active) VALUES(?,?,?,?)', [$img, post_clip($d['label'] ?? ''), $pos, (int)($d['active'] ?? 1)]);
                $keep[] = (int)db()->lastInsertId();
            } else {
                $d = (array)((($_POST['s'] ?? [])[$key]) ?? []);
                if ($d) {
                    q('UPDATE hero_slides SET image=?, label=?, sort=?, active=? WHERE id=?', [safe_path((string)($d['image'] ?? '')), post_clip($d['label'] ?? ''), $pos, (int)($d['active'] ?? 1), (int)$key]);
                    $keep[] = (int)$key;
                }
            }
        }
        foreach (array_column(rows('SELECT id FROM hero_slides'), 'id') as $id) {
            if (!in_array((int)$id, $keep, true)) {
                q('DELETE FROM hero_slides WHERE id = ?', [$id]);
            }
        }
        when_saved();
        flash('Slider kaydedildi.');
        redirect_to('hero');
    }
    ahead('Ana sayfa slider', 'hero', 'Hero bölümünde dönen şişe görselleri (yaklaşık 5 sn aralıkla)');
    echo '<form method="post" data-guard>' . csrf_field() . '<div class="card" style="padding:6px"><table><tbody id="rows-h">';
    foreach (rows('SELECT * FROM hero_slides ORDER BY sort, id') as $s) {
        echo hero_row($s);
    }
    echo '</tbody></table></div><template id="tpl-h">' . hero_row(null) . '</template>
<div class="savebar"><button class="btn primary">Kaydet</button><button type="button" class="btn" data-addrow="tpl-h" data-into="rows-h">+ Yeni görsel</button></div></form>';
    afoot();
}

// ---------------------------------------------------------------- documents / certificates
function doc_row(?array $d): string
{
    $id = $d ? (string)$d['id'] : 'n__N__';
    $nm = $d ? "d[$id]" : "new[__N__]";
    return '<div class="card" data-row><input type="hidden" name="ord[]" value="' . h($id) . '"><div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:8px"><strong>Belge</strong><span class="actions"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button><button type="button" class="btn sm danger" data-del="[data-row]">Sil</button></span></div>
<div class="grid g2"><div>' . ml_input($nm . '[title]', $d ? jd($d['title']) : [], 'Belge adı', 'input', false) . ml_input($nm . '[note]', $d ? jd($d['note']) : [], 'Not (geçerlilik tarihi vb.)', 'input', false) . '</div>
<div>' . img_input($nm . '[image]', $d['image'] ?? '', 'Önizleme görseli') . img_input($nm . '[file]', $d['file'] ?? '', 'PDF dosyası (isteğe bağlı)', true)
        . '<label><input type="hidden" name="' . $nm . '[active]" value="0"><input type="checkbox" name="' . $nm . '[active]" value="1"' . (!$d || $d['active'] ? ' checked' : '') . '> Yayında</label></div></div></div>';
}

function admin_docs(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $keep = [];
        foreach ((array)($_POST['ord'] ?? []) as $pos => $key) {
            $key = (string)$key;
            $isNew = strpos($key, 'n') === 0;
            $d = (array)((($isNew ? ($_POST['new'] ?? []) : ($_POST['d'] ?? []))[$isNew ? substr($key, 1) : $key]) ?? []);
            $title = post_ml_from((array)($d['title'] ?? []));
            $image = safe_path((string)($d['image'] ?? ''));
            $file = safe_path((string)($d['file'] ?? ''));
            if (!$d || ($title['tr'] === '' && $image === '' && $file === '')) {
                continue;
            }
            if ($isNew) {
                q('INSERT INTO docs(title,note,file,image,sort,active) VALUES(?,?,?,?,?,?)', [je($title), je(post_ml_from((array)($d['note'] ?? []))), $file, $image, $pos, (int)($d['active'] ?? 1)]);
                $keep[] = (int)db()->lastInsertId();
            } else {
                q('UPDATE docs SET title=?, note=?, file=?, image=?, sort=?, active=? WHERE id=?', [je($title), je(post_ml_from((array)($d['note'] ?? []))), $file, $image, $pos, (int)($d['active'] ?? 1), (int)$key]);
                $keep[] = (int)$key;
            }
        }
        foreach (array_column(rows('SELECT id FROM docs'), 'id') as $id) {
            if (!in_array((int)$id, $keep, true)) {
                q('DELETE FROM docs WHERE id = ?', [$id]);
            }
        }
        when_saved();
        flash('Belgeler kaydedildi.');
        redirect_to('docs');
    }
    ahead('Belgeler / sertifikalar', 'docs', 'Kalite sayfasındaki belge galerisi');
    echo '<form method="post" data-guard>' . csrf_field() . langbar() . '<div id="rows-d">';
    foreach (rows('SELECT * FROM docs ORDER BY sort, id') as $d) {
        echo doc_row($d);
    }
    echo '</div><template id="tpl-d">' . doc_row(null) . '</template><p class="hint">Yalnızca geçerli (süresi dolmamış) belgeleri yayınlayın. Liste boşsa Kalite sayfasında "belgeler talep üzerine paylaşılır" notu görünür.</p>
<div class="savebar"><button class="btn primary">Kaydet</button><button type="button" class="btn" data-addrow="tpl-d" data-into="rows-d">+ Belge ekle</button></div></form>';
    afoot();
}

function post_ml_from(array $a): array
{
    $o = [];
    foreach (LANGS as $l) {
        $o[$l] = clean_html((string)($a[$l] ?? ''));
    }
    return $o;
}
