<?php
declare(strict_types=1);

const UPLOAD_MAX = 12 * 1024 * 1024;

/**
 * Save an uploaded image (re-encoded to WebP/JPEG/PNG through GD, resized) or PDF.
 * @return array [path|null, error]
 */
function handle_upload(array $file, int $maxW = 1800, bool $allowPdf = false): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, 'Yükleme hatası (kod ' . (int)($file['error'] ?? -1) . ').'];
    }
    if ($file['size'] > UPLOAD_MAX) {
        return [null, 'Dosya 12 MB sınırını aşıyor.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME)) ?: 'dosya';
    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(UZ_ROOT . '/' . $dir) && !@mkdir(UZ_ROOT . '/' . $dir, 0775, true)) {
        return [null, 'uploads klasörü yazılabilir değil.'];
    }
    $name = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
    if ($mime === 'application/pdf') {
        if (!$allowPdf) {
            return [null, 'Burada yalnızca görsel yüklenebilir.'];
        }
        if (strncmp((string)file_get_contents($file['tmp_name'], false, null, 0, 5), '%PDF-', 5) !== 0) {
            return [null, 'Geçersiz PDF.'];
        }
        $path = "$dir/$name.pdf";
        move_uploaded_file($file['tmp_name'], UZ_ROOT . '/' . $path) || copy($file['tmp_name'], UZ_ROOT . '/' . $path);
        q('INSERT OR IGNORE INTO media(path,name,mime,size,created_at) VALUES(?,?,?,?,?)', [$path, $file['name'], $mime, (int)$file['size'], date('Y-m-d H:i:s')]);
        return [$path, ''];
    }
    $loaders = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp', 'image/gif' => 'imagecreatefromgif'];
    if (!isset($loaders[$mime]) || !function_exists($loaders[$mime])) {
        return [null, 'Desteklenmeyen dosya türü (' . $mime . '). JPG, PNG, WebP veya PDF yükleyin.'];
    }
    $im = @$loaders[$mime]($file['tmp_name']);
    if (!$im) {
        return [null, 'Görsel okunamadı.'];
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($file['tmp_name']);
        $o = (int)($ex['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) {
            $im = imagerotate($im, $rot, 0) ?: $im;
        }
    }
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w > $maxW) {
        $nh = (int)round($h * $maxW / $w);
        $re = imagecreatetruecolor($maxW, $nh);
        imagealphablending($re, false);
        imagesavealpha($re, true);
        imagefill($re, 0, 0, imagecolorallocatealpha($re, 0, 0, 0, 127));
        imagecopyresampled($re, $im, 0, 0, 0, 0, $maxW, $nh, $w, $h);
        $im = $re;
        $w = $maxW;
        $h = $nh;
    }
    imagesavealpha($im, true);
    if (function_exists('imagewebp')) {
        $ext = 'webp';
        $ok = imagewebp($im, UZ_ROOT . "/$dir/$name.webp", 86);
    } elseif ($mime === 'image/png' || $mime === 'image/gif') {
        $ext = 'png';
        $ok = imagepng($im, UZ_ROOT . "/$dir/$name.png", 7);
    } else {
        $ext = 'jpg';
        $ok = imagejpeg($im, UZ_ROOT . "/$dir/$name.jpg", 88);
    }
    if (!$ok) {
        return [null, 'Görsel kaydedilemedi.'];
    }
    $path = "$dir/$name.$ext";
    q('INSERT OR IGNORE INTO media(path,name,mime,w,h,size,created_at) VALUES(?,?,?,?,?,?,?)',
        [$path, $file['name'], "image/$ext", $w, $h, (int)filesize(UZ_ROOT . '/' . $path), date('Y-m-d H:i:s')]);
    return [$path, ''];
}

/** Images already shipped with the site (product photos etc.) + uploads, for the picker. */
function media_list(): array
{
    $out = [];
    foreach (rows('SELECT path, name, mime FROM media ORDER BY id DESC LIMIT 400') as $m) {
        if (strpos($m['mime'], 'image') === 0 && is_file(UZ_ROOT . '/' . $m['path'])) {
            $out[] = ['path' => $m['path'], 'name' => $m['name'], 'src' => 'upload'];
        }
    }
    foreach (['assets/img/h', 'assets/img/p', 'assets/img/pl'] as $d) {
        foreach (glob(UZ_ROOT . "/$d/*.webp") ?: [] as $f) {
            $out[] = ['path' => "$d/" . basename($f), 'name' => basename($f), 'src' => 'site'];
        }
    }
    return $out;
}
