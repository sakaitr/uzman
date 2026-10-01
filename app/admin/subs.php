<?php
declare(strict_types=1);

function admin_subs(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'read_all') {
            q("UPDATE submissions SET status = 'done' WHERE status = 'new'");
            flash('Tümü işlendi olarak işaretlendi.');
        }
        redirect_to('subs');
    }
    $f = (string)($_GET['s'] ?? '');
    $where = in_array($f, ['new', 'done'], true) ? ' WHERE status = ' . db()->quote($f) : '';
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per = 25;
    $total = (int)val('SELECT COUNT(*) FROM submissions' . $where);
    $list = rows('SELECT * FROM submissions' . $where . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
    ahead('Başvurular', 'subs', $total . ' kayıt — iletişim sayfasındaki teklif / numune formundan gelenler');
    echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"><a class="btn sm' . ($f === '' ? ' primary' : '') . '" href="' . admin_url('subs') . '">Tümü</a><a class="btn sm' . ($f === 'new' ? ' primary' : '') . '" href="' . admin_url('subs', ['s' => 'new']) . '">Yeni</a><a class="btn sm' . ($f === 'done' ? ' primary' : '') . '" href="' . admin_url('subs', ['s' => 'done']) . '">İşlendi</a>
<a class="btn sm" href="' . admin_url('subs_csv') . '">CSV indir</a><form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="do" value="read_all"><button class="btn sm">Tümünü işlendi say</button></form></div>
<div class="card" style="padding:0"><table><thead><tr><th>Tarih</th><th>Firma / kişi</th><th>İlgi</th><th>E-posta / telefon</th><th>Durum</th><th></th></tr></thead><tbody>';
    foreach ($list as $s) {
        echo '<tr><td style="white-space:nowrap">' . h($s['created_at']) . '<div class="hint">' . strtoupper(h($s['lang'])) . '</div></td><td><b>' . h($s['company']) . '</b><div class="hint">' . h($s['name']) . '</div></td><td>' . h($s['category']) . '<div class="hint">' . h($s['market']) . '</div></td>
<td><a href="mailto:' . h($s['email']) . '">' . h($s['email']) . '</a><div class="hint">' . h($s['phone']) . '</div></td><td><span class="pill ' . ($s['status'] === 'new' ? 'new' : 'ok') . '">' . ($s['status'] === 'new' ? 'Yeni' : 'İşlendi') . '</span>' . ($s['mail_ok'] ? '' : ' <span class="pill warn" title="' . h($s['note']) . '">e-posta gitmedi</span>') . '</td>
<td class="actions"><a class="btn sm" href="' . admin_url('sub_view', ['id' => $s['id']]) . '">Aç</a></td></tr>';
    }
    if (!$list) {
        echo '<tr><td colspan="6" class="hint" style="padding:24px">Kayıt yok.</td></tr>';
    }
    echo '</tbody></table></div>';
    $pages = (int)ceil($total / $per);
    if ($pages > 1) {
        echo '<div style="display:flex;gap:6px;flex-wrap:wrap">';
        for ($i = 1; $i <= $pages; $i++) {
            echo '<a class="btn sm' . ($i === $page ? ' primary' : '') . '" href="' . admin_url('subs', ['s' => $f, 'p' => $i]) . '">' . $i . '</a>';
        }
        echo '</div>';
    }
    afoot();
}

function admin_sub_view(): void
{
    $s = row('SELECT * FROM submissions WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$s) {
        redirect_to('subs');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (($_POST['do'] ?? '') === 'delete') {
            q('DELETE FROM submissions WHERE id = ?', [$s['id']]);
            flash('Başvuru silindi.');
            redirect_to('subs');
        }
        q('UPDATE submissions SET status = ? WHERE id = ?', [($_POST['status'] ?? '') === 'done' ? 'done' : 'new', $s['id']]);
        flash('Durum güncellendi.');
        redirect_to('sub_view', ['id' => $s['id']]);
    }
    if ($s['status'] === 'new') {
        q("UPDATE submissions SET status = 'seen' WHERE id = ? AND status = 'new'", [$s['id']]);
        $s['status'] = 'seen';
    }
    ahead($s['company'] ?: 'Başvuru', 'subs', '#' . $s['id'] . ' · ' . $s['created_at']);
    $rowh = function ($k, $v) {
        return '<tr><th style="width:170px">' . h($k) . '</th><td>' . $v . '</td></tr>';
    };
    echo '<div class="card"><table><tbody>' . $rowh('Ad Soyad', h($s['name'])) . $rowh('Firma / Marka', h($s['company'])) . $rowh('E-posta', '<a href="mailto:' . h($s['email']) . '">' . h($s['email']) . '</a>') . $rowh('Telefon', '<a href="tel:' . h(preg_replace('/[^0-9+]/', '', $s['phone'])) . '">' . h($s['phone']) . '</a>')
        . $rowh('Kategori', h($s['category'])) . $rowh('Hedef pazar', h($s['market'])) . $rowh('Tahmini adet', h($s['qty'])) . $rowh('Dil', strtoupper(h($s['lang']))) . $rowh('Proje', nl2br(h($s['brief']))) . $rowh('IP', h($s['ip'])) . '</tbody></table></div>
<form method="post" style="display:flex;gap:10px;flex-wrap:wrap">' . csrf_field() . '<button class="btn primary" name="status" value="done">İşlendi olarak işaretle</button><button class="btn" name="status" value="new">Yeni olarak işaretle</button><a class="btn" href="mailto:' . h($s['email']) . '?subject=' . rawurlencode('Uzman Cosmetic — teklif talebiniz') . '">E-posta ile yanıtla</a><a class="btn" href="' . admin_url('subs') . '">Geri</a>
<button class="btn danger" name="do" value="delete" data-confirm="Başvuru kalıcı olarak silinsin mi?">Sil</button></form>';
    afoot();
}

function admin_subs_csv(): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="basvurular-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['ID', 'Tarih', 'Dil', 'Ad Soyad', 'Firma', 'E-posta', 'Telefon', 'Kategori', 'Pazar', 'Adet', 'Proje', 'Durum']);
    foreach (rows('SELECT * FROM submissions ORDER BY id DESC') as $s) {
        $cells = [$s['id'], $s['created_at'], $s['lang'], $s['name'], $s['company'], $s['email'], $s['phone'], $s['category'], $s['market'], $s['qty'], $s['brief'], $s['status']];
        // neutralise spreadsheet formulas
        $cells = array_map(function ($c) { return is_string($c) && preg_match('/^[=+\-@]/', $c) ? "'" . $c : $c; }, $cells);
        fputcsv($o, $cells);
    }
    fclose($o);
}
