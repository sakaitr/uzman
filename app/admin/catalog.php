<?php
declare(strict_types=1);

function admin_catalog(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'order') {
            foreach ((array)($_POST['order'] ?? []) as $i => $id) {
                q('UPDATE subcats SET sort = ? WHERE id = ? AND parent_id IS NULL', [(int)$i, (int)$id]);
            }
            flash('Sıra kaydedildi.');
        } elseif ($do === 'add_sub') {
            $cat = (int)($_POST['cat_id'] ?? 0);
            $name = post_ml('name', false);
            if ($name['tr'] === '' || !row('SELECT id FROM categories WHERE id = ?', [$cat])) {
                flash('Türkçe ad zorunlu.', 'err');
            } else {
                q('INSERT INTO subcats(cat_id,slug,name,desc,sizes,art,sort) VALUES(?,?,?,?,?,?,?)', [$cat, slugify($name['tr']) ?: 'kategori', je($name), '{}', '', 'aerosol', 999]);
                flash('Alt kategori eklendi.');
                when_saved();
                redirect_to('sub_edit', ['id' => (int)db()->lastInsertId()]);
            }
        } elseif ($do === 'del_sub') {
            q('DELETE FROM items WHERE sub_id IN (SELECT id FROM subcats WHERE id = ? OR parent_id = ?)', [(int)$_POST['id'], (int)$_POST['id']]);
            q('DELETE FROM subcats WHERE id = ? OR parent_id = ?', [(int)$_POST['id'], (int)$_POST['id']]);
            flash('Alt kategori silindi.');
        }
        when_saved();
        redirect_to('catalog');
    }
    ahead('Ürünler', 'catalog', 'Ürün ağacı: kategori → alt kategori → ürün görselleri');
    foreach (rows('SELECT * FROM categories ORDER BY sort, id') as $c) {
        echo '<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><h2 style="margin:0">' . h(ml(jd($c['name']), 'tr')) . ' <span class="pill off">/' . h($c['page']) . '.html</span></h2><a class="btn sm" href="' . admin_url('cat_edit', ['id' => $c['id']]) . '">Adları düzenle</a></div>
<form method="post">' . csrf_field() . '<input type="hidden" name="do" value="order"><div data-sortable>';
        foreach (rows('SELECT s.*, (SELECT COUNT(*) FROM items i WHERE i.sub_id = s.id OR i.sub_id IN (SELECT id FROM subcats c2 WHERE c2.parent_id = s.id)) AS n FROM subcats s WHERE cat_id = ? AND parent_id IS NULL ORDER BY sort, id', [$c['id']]) as $s) {
            echo '<div class="tree-row" data-row><input type="hidden" name="order[]" value="' . $s['id'] . '"><span class="nm">' . h(ml(jd($s['name']), 'tr')) . ' <span class="hint">· ' . (int)$s['n'] . ' görsel' . ($s['sizes'] ? ' · ' . h($s['sizes']) . ' ml' : '') . '</span></span>
<button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button><a class="btn sm" href="' . admin_url('sub_edit', ['id' => $s['id']]) . '">Düzenle</a>
<button class="btn sm danger" name="do" value="del_sub" formaction="' . admin_url('catalog') . '" data-confirm="Alt kategori ve içindeki tüm ürünler silinsin mi?" onclick="this.form.querySelector(\'[name=id]\')&&this.form.querySelector(\'[name=id]\').remove();var i=document.createElement(\'input\');i.type=\'hidden\';i.name=\'id\';i.value=\'' . $s['id'] . '\';this.form.appendChild(i)">Sil</button></div>';
        }
        echo '</div><p style="margin:14px 0 0"><button class="btn sm">Sırayı kaydet</button></p></form>
<form method="post" style="margin-top:18px;padding-top:16px;border-top:1px solid var(--line)">' . csrf_field() . '<input type="hidden" name="do" value="add_sub"><input type="hidden" name="cat_id" value="' . $c['id'] . '"><strong style="font-size:.9rem">Yeni alt kategori</strong>' . langbar() . ml_input('name', [], 'Ad', 'input', false) . '<button class="btn sm primary">Ekle</button></form></div>';
    }
    afoot();
}

function admin_cat_edit(): void
{
    $c = row('SELECT * FROM categories WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$c) {
        redirect_to('catalog');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        q('UPDATE categories SET name = ? WHERE id = ?', [je(post_ml('name', false)), $c['id']]);
        when_saved();
        flash('Kategori adı kaydedildi.');
        redirect_to('catalog');
    }
    ahead('Kategori adı', 'catalog');
    echo '<form method="post" class="card" style="max-width:720px" data-guard>' . csrf_field() . langbar() . ml_input('name', jd($c['name']), 'Kategori adı') . '<button class="btn primary">Kaydet</button> <a class="btn" href="' . admin_url('catalog') . '">Geri</a></form>';
    afoot();
}

function item_row(int $gid, ?array $it): string
{
    $id = $it ? (string)$it['id'] : 'n__N__';
    $nm = $it ? "items[$id]" : "new[$gid][__N__]";
    $cap = $it ? (string)$it['cap'] : '';
    $isMl = $it && $cap !== '' && $cap[0] === '{';
    $capHtml = $isMl ? ml_input($nm . '[cap]', jd($cap), '', 'input', false) . '<input type="hidden" name="' . $nm . '[capml]" value="1">'
        : '<input type="text" name="' . $nm . '[cap]" value="' . h($cap) . '" placeholder="Ürün / marka adı">';
    return '<tr data-row><td style="width:44px"><input type="hidden" name="ord[' . $gid . '][]" value="' . h($id) . '"><span class="actions" style="flex-direction:column"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button></span></td>
<td style="min-width:270px">' . img_input($nm . '[image]', $it ? (string)$it['image'] : '', 'Görsel') . '</td><td style="min-width:200px">' . $capHtml . '</td>
<td><label><input type="hidden" name="' . $nm . '[active]" value="0"><input type="checkbox" name="' . $nm . '[active]" value="1"' . (!$it || $it['active'] ? ' checked' : '') . '> Yayında</label></td>
<td><button type="button" class="btn sm danger" data-del="tr">Sil</button></td></tr>';
}

function items_table(int $gid, array $items): string
{
    $rows = '';
    foreach ($items as $it) {
        $rows .= item_row($gid, $it);
    }
    return '<input type="hidden" name="groups[]" value="' . $gid . '"><table><thead><tr><th></th><th>Görsel</th><th>Başlık</th><th></th><th></th></tr></thead><tbody id="rows-' . $gid . '">' . $rows . '</tbody></table>
<template id="tpl-item-' . $gid . '">' . item_row($gid, null) . '</template><p style="margin:10px 0 0"><button type="button" class="btn sm" data-addrow="tpl-item-' . $gid . '" data-into="rows-' . $gid . '">+ Ürün görseli ekle</button></p>';
}

function admin_sub_edit(): void
{
    $s = row('SELECT * FROM subcats WHERE id = ? AND parent_id IS NULL', [(int)($_GET['id'] ?? 0)]);
    if (!$s) {
        redirect_to('catalog');
    }
    $children = rows('SELECT * FROM subcats WHERE parent_id = ? ORDER BY sort, id', [$s['id']]);
    $groups = $children ?: [$s];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? 'save');
        if ($do === 'add_child') {
            $name = post_ml('cname', false);
            if ($name['tr'] !== '') {
                if (!$children) {
                    // move existing direct items into a first group
                    $first = q('INSERT INTO subcats(cat_id,parent_id,slug,name,desc,sizes,art,sort) VALUES(?,?,?,?,?,?,?,?)', [$s['cat_id'], $s['id'], '', je(jd($s['name'])), '{}', '', $s['art'], 0]);
                    $fid = (int)db()->lastInsertId();
                    q('UPDATE items SET sub_id = ? WHERE sub_id = ?', [$fid, $s['id']]);
                }
                q('INSERT INTO subcats(cat_id,parent_id,slug,name,desc,sizes,art,sort) VALUES(?,?,?,?,?,?,?,?)', [$s['cat_id'], $s['id'], '', je($name), '{}', '', $s['art'], 99]);
                flash('Grup eklendi.');
            }
            when_saved();
            redirect_to('sub_edit', ['id' => $s['id']]);
        }
        if ($do === 'del_child') {
            q('DELETE FROM subcats WHERE id = ? AND parent_id = ?', [(int)$_POST['child'], $s['id']]);
            when_saved();
            flash('Grup silindi.');
            redirect_to('sub_edit', ['id' => $s['id']]);
        }
        q('UPDATE subcats SET name=?, desc=?, sizes=? WHERE id=?', [je(post_ml('name', false)), je(post_ml('desc')), preg_replace('/[^0-9, ]/', '', post_str('sizes', 80)), $s['id']]);
        foreach ($children as $ch) {
            q('UPDATE subcats SET name = ? WHERE id = ?', [je(post_ml('cn' . $ch['id'], false)), $ch['id']]);
        }
        foreach ((array)($_POST['groups'] ?? []) as $gid) {
            $gid = (int)$gid;
            if (!in_array($gid, array_column($groups, 'id'), true)) {
                continue;
            }
            $posted = (array)($_POST['items'] ?? []);
            $order = (array)(($_POST['ord'] ?? [])[$gid] ?? []);
            $keep = [];
            foreach ($order as $pos => $key) {
                $key = (string)$key;
                if (strpos($key, 'n') === 0) { // new
                    $d = (array)((($_POST['new'] ?? [])[$gid] ?? [])[substr($key, 1)] ?? []);
                    $cap = is_array($d['cap'] ?? null) ? '' : trim((string)($d['cap'] ?? ''));
                    $img = safe_path((string)($d['image'] ?? ''));
                    if ($cap === '' && $img === '') {
                        continue;
                    }
                    q('INSERT INTO items(sub_id,cap,image,sort,active) VALUES(?,?,?,?,?)', [$gid, mb_substr($cap, 0, 160), $img, $pos, (int)($d['active'] ?? 1)]);
                    $keep[] = (int)db()->lastInsertId();
                } else {
                    $d = (array)($posted[$key] ?? []);
                    if (!$d) {
                        continue;
                    }
                    $cap = !empty($d['capml']) ? je(array_map(function ($x) { return trim((string)$x); }, array_intersect_key((array)$d['cap'], array_flip(LANGS)))) : mb_substr(trim((string)($d['cap'] ?? '')), 0, 160);
                    q('UPDATE items SET cap=?, image=?, sort=?, active=? WHERE id=? AND sub_id=?', [$cap, safe_path((string)($d['image'] ?? '')), $pos, (int)($d['active'] ?? 1), (int)$key, $gid]);
                    $keep[] = (int)$key;
                }
            }
            $all = array_column(rows('SELECT id FROM items WHERE sub_id = ?', [$gid]), 'id');
            foreach ($all as $iid) {
                if (!in_array((int)$iid, $keep, true)) {
                    q('DELETE FROM items WHERE id = ?', [$iid]);
                }
            }
        }
        when_saved();
        flash('Kaydedildi.');
        redirect_to('sub_edit', ['id' => $s['id']]);
    }
    ahead(ml(jd($s['name']), 'tr'), 'catalog', 'Alt kategori — ürün görselleri ve metinleri');
    echo '<form method="post" data-guard>' . csrf_field() . langbar() . '<div class="card"><h2>Kategori bilgisi</h2>' . ml_input('name', jd($s['name']), 'Ad') . ml_input('desc', jd($s['desc']), 'Açıklama', 'area', false)
        . field('Boyutlar (ml, virgülle)', 'sizes', $s['sizes'], 'text', 'Örn: 150, 200, 250 — boş bırakılırsa gösterilmez.') . '</div>';
    if ($children) {
        foreach ($children as $ch) {
            $items = rows('SELECT * FROM items WHERE sub_id = ? ORDER BY sort, id', [$ch['id']]);
            echo '<div class="card"><div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start"><div style="flex:1">' . ml_input('cn' . $ch['id'], jd($ch['name']), 'Grup adı', 'input', false) . '</div>
<button class="btn sm danger" name="do" value="del_child" onclick="var i=document.createElement(\'input\');i.type=\'hidden\';i.name=\'child\';i.value=\'' . $ch['id'] . '\';this.form.appendChild(i)" data-confirm="Bu grup ve görselleri silinsin mi?">Grubu sil</button></div>' . items_table((int)$ch['id'], $items) . '</div>';
        }
    } else {
        $items = rows('SELECT * FROM items WHERE sub_id = ? ORDER BY sort, id', [$s['id']]);
        echo '<div class="card"><h2>Ürün görselleri</h2>' . items_table((int)$s['id'], $items) . '</div>';
    }
    echo '<div class="savebar"><button class="btn primary">Kaydet</button><a class="btn" href="' . admin_url('catalog') . '">Geri</a></div></form>
<form method="post" class="card" style="margin-top:24px">' . csrf_field() . '<h2>Alt gruplar</h2><p class="hint" style="margin-top:-8px">Görselleri başlıklı gruplara ayırmak için (örn. Saç ürünleri → Wax, Sprey). Önce yukarıdaki değişiklikleri kaydedin.</p><input type="hidden" name="do" value="add_child">' . langbar() . ml_input('cname', [], 'Yeni grup adı', 'input', false) . '<button class="btn sm">Grup ekle</button></form>';
    afoot();
}
