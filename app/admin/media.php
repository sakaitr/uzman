<?php
declare(strict_types=1);

function admin_media_json(): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(media_list(), JSON_UNESCAPED_UNICODE);
}

function admin_upload(): void
{
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_FILES['file'])) {
        echo json_encode(['error' => 'Dosya yok.']);
        return;
    }
    [$path, $err] = handle_upload($_FILES['file'], 1800, true);
    echo json_encode($path ? ['path' => $path] : ['error' => $err], JSON_UNESCAPED_UNICODE);
}

function admin_media(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (($_POST['do'] ?? '') === 'delete') {
            $m = row('SELECT * FROM media WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
            $used = $m ? media_usage($m['path']) : [];
            if ($m && $used) {
                flash('Bu dosya kullanımda, silinemez: ' . implode(', ', array_slice($used, 0, 5)) . '. Önce o alanlardan kaldırın.', 'err');
            } elseif ($m && strpos($m['path'], 'uploads/') === 0) {
                @unlink(UZ_ROOT . '/' . $m['path']);
                q('DELETE FROM media WHERE id = ?', [$m['id']]);
                flash('Dosya silindi. (Sayfalarda hâlâ kullanılıyorsa görsel kırık görünür.)');
            }
        } elseif (!empty($_FILES['files'])) {
            $n = 0;
            foreach ($_FILES['files']['name'] as $i => $nm) {
                [$path, $err] = handle_upload(['name' => $nm, 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'size' => $_FILES['files']['size'][$i], 'error' => $_FILES['files']['error'][$i]], 1800, true);
                $path ? $n++ : flash($nm . ': ' . $err, 'err');
            }
            if ($n) {
                flash($n . ' dosya yüklendi.');
            }
        }
        redirect_to('media');
    }
    ahead('Medya', 'media', 'Yüklenen görseller ve PDF\'ler');
    echo '<form method="post" enctype="multipart/form-data" class="card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">' . csrf_field() . '<input type="file" name="files[]" multiple accept="image/*,.pdf" required><button class="btn primary">Yükle</button><span class="hint">Görseller otomatik WebP\'ye çevrilir ve 1800 px\'e küçültülür. PDF\'ler olduğu gibi saklanır. Sınır: 12 MB.</span></form>';
    $ms = rows('SELECT * FROM media ORDER BY id DESC LIMIT 300');
    if (!$ms) {
        echo '<p class="hint">Henüz yükleme yok.</p>';
    }
    echo '<div class="grid g4">';
    foreach ($ms as $m) {
        $img = strpos($m['mime'], 'image') === 0;
        echo '<div class="card" style="padding:12px;margin:0"><div style="height:140px;background:#f2f0ec;border-radius:8px;display:grid;place-items:center;overflow:hidden;margin-bottom:10px">' . ($img ? '<img src="../' . h($m['path']) . '" style="max-width:100%;max-height:100%" loading="lazy" alt="">' : '<b>PDF</b>') . '</div>
<div style="font-size:.8rem;word-break:break-all"><b>' . h($m['name']) . '</b><br><code>' . h($m['path']) . '</code><br><span class="hint">' . round($m['size'] / 1024) . ' KB' . ($m['w'] ? ' · ' . $m['w'] . '×' . $m['h'] : '') . '</span></div>
<form method="post" style="margin-top:8px">' . csrf_field() . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . $m['id'] . '"><button class="btn sm danger" data-confirm="Dosya silinsin mi?">Sil</button></form></div>';
    }
    echo '</div>';
    afoot();
}
