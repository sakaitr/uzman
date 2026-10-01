<?php
/**
 * Brand layer: name, logo (processed with GD), colour palette → CSS tokens. Everything is stored in settings,
 * so the same code base can serve any client; nothing here is specific to one brand.
 */
declare(strict_types=1);

// ------------------------------------------------------------------ colour maths
function hex_norm(string $h, string $fallback = '#000000'): string
{
    $h = ltrim(trim($h), '#');
    if (preg_match('/^[0-9a-fA-F]{3}$/', $h)) {
        $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
    }
    return preg_match('/^[0-9a-fA-F]{6}$/', $h) ? '#' . strtolower($h) : $fallback;
}

function hex2rgb(string $h): array
{
    $h = ltrim(hex_norm($h), '#');
    return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
}

function rgb2hex(array $c): string
{
    return sprintf('#%02x%02x%02x', max(0, min(255, (int)round($c[0]))), max(0, min(255, (int)round($c[1]))), max(0, min(255, (int)round($c[2]))));
}

function color_mix(string $a, string $b, float $t): string
{
    $x = hex2rgb($a);
    $y = hex2rgb($b);
    return rgb2hex([$x[0] + ($y[0] - $x[0]) * $t, $x[1] + ($y[1] - $x[1]) * $t, $x[2] + ($y[2] - $x[2]) * $t]);
}

function color_lum(string $h): float
{
    $c = array_map(function ($v) {
        $v /= 255;
        return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }, hex2rgb($h));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function contrast_ratio(string $a, string $b): float
{
    $l1 = color_lum($a);
    $l2 = color_lum($b);
    return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
}

function hsl_shift(string $hex, float $dl): string
{
    [$r, $g, $b] = array_map(function ($v) { return $v / 255; }, hex2rgb($hex));
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    $d = $max - $min;
    $h = 0.0;
    $s = 0.0;
    if ($d > 0) {
        $s = $d / (1 - abs(2 * $l - 1));
        if ($max === $r) {
            $h = fmod((($g - $b) / $d), 6);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        $h *= 60;
        if ($h < 0) {
            $h += 360;
        }
    }
    $l = max(0.04, min(0.96, $l + $dl));
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    [$r1, $g1, $b1] = $h < 60 ? [$c, $x, 0] : ($h < 120 ? [$x, $c, 0] : ($h < 180 ? [0, $c, $x] : ($h < 240 ? [0, $x, $c] : ($h < 300 ? [$x, 0, $c] : [$c, 0, $x]))));
    return rgb2hex([($r1 + $m) * 255, ($g1 + $m) * 255, ($b1 + $m) * 255]);
}

function rgba_css(string $hex, float $a): string
{
    [$r, $g, $b] = hex2rgb($hex);
    return "rgba($r,$g,$b," . rtrim(rtrim(number_format($a, 3, '.', ''), '0'), '.') . ')';
}

// ------------------------------------------------------------------ palettes
/** Built-in theme presets: seeds only. The first five also exist as hand-tuned CSS themes. */
function brand_presets(): array
{
    return [
        'noir' => ['Noir & Honey', ['bg' => '#0b0c0e', 'text' => '#f4f1ea', 'accent' => '#d4a359', 'accent2' => ''], true],
        'bordeaux' => ['Bordeaux Rose Gold', ['bg' => '#1c080d', 'text' => '#f3ede6', 'accent' => '#e0a487', 'accent2' => '#9b2c3f'], true],
        'emerald' => ['Emerald & Rose Gold', ['bg' => '#03211b', 'text' => '#f3ede6', 'accent' => '#d59473', 'accent2' => '#1f7a66'], true],
        'twotone' => ['Two-Tone Bordeaux', ['bg' => '#021813', 'text' => '#ddd3c6', 'accent' => '#d8707b', 'accent2' => '#7a1e2e'], true],
        'inverse' => ['Bordeaux × Emerald', ['bg' => '#1c080d', 'text' => '#f3ede6', 'accent' => '#4db396', 'accent2' => '#1f7a66'], true],
        'graphite' => ['Graphite & Copper', ['bg' => '#121316', 'text' => '#efeae4', 'accent' => '#c8793f', 'accent2' => ''], false],
        'midnight' => ['Midnight & Sapphire', ['bg' => '#0a1020', 'text' => '#eef1f8', 'accent' => '#6c9bff', 'accent2' => '#2d56b8'], false],
        'forest' => ['Forest & Champagne', ['bg' => '#0f1a14', 'text' => '#f1ede2', 'accent' => '#cdb27a', 'accent2' => '#2f6b4a'], false],
        'ivory' => ['Ivory & Navy (açık)', ['bg' => '#f6f2ea', 'text' => '#1c2430', 'accent' => '#1f3a5f', 'accent2' => ''], false],
        'porcelain' => ['Porcelain & Rose (açık)', ['bg' => '#faf6f3', 'text' => '#2a1f20', 'accent' => '#b4584a', 'accent2' => '#e9b8a8'], false],
    ];
}

function brand_theme(): string
{
    $t = (string)setting('theme', 'noir');
    return $t === 'custom' || isset(THEMES[$t]) ? $t : 'noir';
}

function brand_custom_seeds(): array
{
    $s = jd(setting('brand_custom', '{}'));
    $d = brand_presets()['graphite'][1];
    return ['bg' => hex_norm((string)($s['bg'] ?? ''), $d['bg']), 'text' => hex_norm((string)($s['text'] ?? ''), $d['text']), 'accent' => hex_norm((string)($s['accent'] ?? ''), $d['accent']),
            'accent2' => !empty($s['accent2']) ? hex_norm((string)$s['accent2'], '') : ''];
}

/** Seeds of the palette currently in use (preset or custom). */
function brand_active_seeds(): array
{
    $t = brand_theme();
    return $t === 'custom' ? brand_custom_seeds() : (brand_presets()[$t][1] ?? brand_presets()['noir'][1]);
}

function brand_is_light(array $s): bool
{
    return color_lum($s['bg']) >= 0.35;
}

function brand_tokens(array $s): array
{
    $bg = $s['bg'];
    $text = $s['text'];
    $ac = $s['accent'];
    $ac2 = $s['accent2'] !== '' ? $s['accent2'] : $ac;
    $light = brand_is_light($s);
    $hi = $light ? hsl_shift($ac, -0.05) : hsl_shift($ac, 0.14);
    $lo = $light ? hsl_shift($ac, -0.18) : hsl_shift($ac, -0.18);
    $on = contrast_ratio($ac, '#14100a') >= contrast_ratio($ac, '#ffffff') ? '#14100a' : '#ffffff';
    return [
        '--bg' => $bg, '--bg-2' => color_mix($bg, $text, 0.025), '--surface' => color_mix($bg, $text, 0.06), '--surface-2' => color_mix($bg, $text, 0.10),
        '--line' => rgba_css($ac, $light ? 0.24 : 0.16), '--line-strong' => rgba_css($ac, $light ? 0.52 : 0.38),
        '--text' => $text, '--muted' => color_mix($text, $bg, 0.36), '--faint' => color_mix($text, $bg, 0.56),
        '--gold' => $ac, '--gold-hi' => $hi, '--gold-lo' => $lo, '--on-gold' => $on, '--liquid' => $ac2,
        '--glass' => $light ? color_mix($bg, '#000000', 0.06) : color_mix($bg, '#000000', 0.4),
        '--glow' => rgba_css($ac2, $light ? 0.16 : 0.2), '--shadow' => $light ? 'rgba(40,30,20,.2)' : 'rgba(0,0,0,.55)',
        '--page' => 'radial-gradient(90% 60% at 80% -10%, ' . rgba_css($ac, $light ? 0.12 : 0.09) . ', transparent 60%), ' . $bg,
    ];
}

/** Accessibility report for a palette: [label, ratio, minimum, ok]. */
function brand_contrast(array $s): array
{
    $t = brand_tokens($s);
    $rows = [
        ['Yazı / zemin', contrast_ratio($s['text'], $s['bg']), 4.5], ['Soluk yazı / zemin', contrast_ratio($t['--muted'], $s['bg']), 4.5],
        ['Bağlantı & vurgu yazısı / zemin', contrast_ratio($t['--gold-hi'], $s['bg']), 4.5], ['Vurgu rengi / zemin (çizgi, ikon)', contrast_ratio($s['accent'], $s['bg']), 3.0],
        ['Düğme yazısı / vurgu', contrast_ratio($t['--on-gold'], $s['accent']), 4.5],
    ];
    return array_map(function ($r) { return [$r[0], round($r[1], 2), $r[2], $r[1] >= $r[2]]; }, $rows);
}

// ------------------------------------------------------------------ brand name helpers
function brand_word(): string
{
    $w = trim((string)setting('brand_word', ''));
    if ($w === '') {
        $w = mb_strtoupper(preg_split('/\s+/', trim((string)setting('site_name', 'BRAND')))[0] ?: 'BRAND');
    }
    return $w;
}

function brand_sub_foot(): string
{
    return (string)setting('brand_foot_sub', '');
}

function brand_foot_word(): string
{
    return trim((string)setting('foot_word', '')) ?: brand_word();
}

function brand_path(string $key): string
{
    $p = (string)setting($key, '');
    return $p !== '' && is_file(UZ_ROOT . '/' . $p) ? $p : '';
}

/** <style> block: custom palette tokens + uploaded logo overrides. */
function brand_css(): string
{
    $css = '';
    if (brand_theme() === 'custom') {
        $s = brand_custom_seeds();
        $css .= 'html[data-theme="custom"]{color-scheme:' . (brand_is_light($s) ? 'light' : 'dark') . ';';
        foreach (brand_tokens($s) as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        $css .= '}';
    }
    $mode = (string)setting('brand_logo_mode', 'mask');
    $file = $mode === 'color' ? (brand_path('brand_logo_color') ?: brand_path('brand_logo_mask')) : brand_path('brand_logo_mask');
    if ($file !== '') {
        $A = asset_prefix();
        $url = h($A . $file) . '?v=' . substr(md5((string)filemtime(UZ_ROOT . '/' . $file)), 0, 6);
        $ratio = max(0.4, min(5.0, (float)setting('brand_logo_ratio', '1')));
        if ($mode === 'color' && brand_path('brand_logo_color') !== '') {
            $css .= '.brand-logo,.logo-wm{background:url(' . $url . ') center/contain no-repeat !important;-webkit-mask:none !important;mask:none !important}';
        } else {
            $css .= '.brand-logo,.logo-wm{-webkit-mask:url(' . $url . ') center/contain no-repeat !important;mask:url(' . $url . ') center/contain no-repeat !important}';
        }
        if (abs($ratio - 1) > 0.05) {
            $css .= '.brand-logo{width:auto !important;aspect-ratio:' . round($ratio, 3) . '}.logo-wm{aspect-ratio:' . round($ratio, 3) . ' !important}';
        }
    }
    return $css === '' ? '' : '<style id="brand-css">' . $css . "</style>\n";
}

// ------------------------------------------------------------------ logo processing
function svg_safe(string $svg): ?string
{
    if (!preg_match('/<svg[\s>]/i', $svg) || strlen($svg) > 600000) {
        return null;
    }
    if (preg_match('/<\s*(script|foreignObject|iframe|object|embed|audio|video)\b|\son\w+\s*=|javascript:|data:text\/html|<!ENTITY|xlink:href\s*=\s*["\']\s*https?:|href\s*=\s*["\']\s*https?:/i', $svg)) {
        return null;
    }
    return $svg;
}

function svg_ratio(string $svg): float
{
    if (preg_match('/viewBox\s*=\s*"([\d.\-eE]+)[ ,]+([\d.\-eE]+)[ ,]+([\d.\-eE]+)[ ,]+([\d.\-eE]+)"/', $svg, $m) && (float)$m[4] > 0) {
        return (float)$m[3] / (float)$m[4];
    }
    if (preg_match('/\swidth\s*=\s*"([\d.]+)/', $svg, $w) && preg_match('/\sheight\s*=\s*"([\d.]+)/', $svg, $h) && (float)$h[1] > 0) {
        return (float)$w[1] / (float)$h[1];
    }
    return 1.0;
}

function gd_load(string $file, string $mime)
{
    $fn = ['image/png' => 'imagecreatefrompng', 'image/jpeg' => 'imagecreatefromjpeg', 'image/webp' => 'imagecreatefromwebp', 'image/gif' => 'imagecreatefromgif'][$mime] ?? null;
    if (!$fn || !function_exists($fn)) {
        return null;
    }
    $im = @$fn($file);
    if (!$im) {
        return null;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $t = imagecreatetruecolor($w, $h);
    imagealphablending($t, false);
    imagesavealpha($t, true);
    imagefill($t, 0, 0, imagecolorallocatealpha($t, 0, 0, 0, 127));
    imagecopy($t, $im, 0, 0, 0, 0, $w, $h);
    return $t;
}

function gd_bbox($im, callable $isFg, int $step = 1): ?array
{
    $w = imagesx($im);
    $h = imagesy($im);
    $x0 = $w;
    $y0 = $h;
    $x1 = -1;
    $y1 = -1;
    for ($y = 0; $y < $h; $y += $step) {
        for ($x = 0; $x < $w; $x += $step) {
            if ($isFg(imagecolorat($im, $x, $y))) {
                $x0 = min($x0, $x);
                $x1 = max($x1, $x);
                $y0 = min($y0, $y);
                $y1 = max($y1, $y);
            }
        }
    }
    return $x1 < 0 ? null : [$x0, $y0, $x1 - $x0 + 1, $y1 - $y0 + 1];
}

/**
 * Process an uploaded logo into: tintable mask (white + alpha), original-colour version, favicon and apple-touch icon.
 * @return array [bool ok, string message, array settings]
 */
function brand_process_logo(array $file, string $mode, string $bgHint): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'Yükleme hatası (kod ' . (int)($file['error'] ?? -1) . ').', []];
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        return [false, 'Dosya 8 MB sınırını aşıyor.', []];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $dir = 'uploads/brand';
    if (!is_dir(UZ_ROOT . '/' . $dir) && !@mkdir(UZ_ROOT . '/' . $dir, 0775, true)) {
        return [false, 'uploads klasörü yazılabilir değil.', []];
    }
    $tag = substr(bin2hex(random_bytes(4)), 0, 6);
    $out = ['brand_logo_mode' => $mode === 'color' ? 'color' : 'mask'];
    $raw = (string)file_get_contents($file['tmp_name']);
    if ($mime === 'image/svg+xml' || $mime === 'text/plain' && preg_match('/<svg[\s>]/i', $raw)) {
        $svg = svg_safe($raw);
        if ($svg === null) {
            return [false, 'SVG güvenli değil ya da geçersiz (betik, harici bağlantı veya çok büyük).', []];
        }
        $p = "$dir/logo-$tag.svg";
        file_put_contents(UZ_ROOT . '/' . $p, $svg);
        return [true, 'SVG logo kaydedildi. Favicon için ayrıca bir PNG logo yükleyebilirsiniz (SVG\'den otomatik üretilemez).', $out + ['brand_logo_mask' => $p, 'brand_logo_color' => $p, 'brand_logo_ratio' => (string)round(svg_ratio($svg), 3)]];
    }
    if (!extension_loaded('gd')) {
        return [false, 'GD eklentisi yok; yalnızca SVG logo yüklenebilir.', []];
    }
    $im = gd_load($file['tmp_name'], $mime);
    if (!$im) {
        return [false, 'Desteklenmeyen dosya (' . $mime . '). PNG, JPG, WebP veya SVG yükleyin.', []];
    }
    $w = imagesx($im);
    $h = imagesy($im);
    if (max($w, $h) > 1000) {
        $k = 1000 / max($w, $h);
        $re = imagecreatetruecolor((int)($w * $k), (int)($h * $k));
        imagealphablending($re, false);
        imagesavealpha($re, true);
        imagefill($re, 0, 0, imagecolorallocatealpha($re, 0, 0, 0, 127));
        imagecopyresampled($re, $im, 0, 0, 0, 0, (int)($w * $k), (int)($h * $k), $w, $h);
        $im = $re;
        $w = imagesx($im);
        $h = imagesy($im);
    }
    // does the image carry real transparency?
    $hasAlpha = false;
    for ($y = 0; $y < $h && !$hasAlpha; $y += max(1, (int)($h / 40))) {
        for ($x = 0; $x < $w; $x += max(1, (int)($w / 40))) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) > 20) {
                $hasAlpha = true;
                break;
            }
        }
    }
    $lumAt = function (int $c): float {
        return (0.2126 * (($c >> 16) & 255) + 0.7152 * (($c >> 8) & 255) + 0.0722 * ($c & 255)) / 255;
    };
    // mask: white + alpha
    $mask = imagecreatetruecolor($w, $h);
    imagealphablending($mask, false);
    imagesavealpha($mask, true);
    $bgRgb = [0, 0, 0];
    if (!$hasAlpha) {
        $cs = [imagecolorat($im, 0, 0), imagecolorat($im, $w - 1, 0), imagecolorat($im, 0, $h - 1), imagecolorat($im, $w - 1, $h - 1)];
        foreach ($cs as $c) {
            $bgRgb[0] += (($c >> 16) & 255) / 4;
            $bgRgb[1] += (($c >> 8) & 255) / 4;
            $bgRgb[2] += ($c & 255) / 4;
        }
    }
    $bgLum = (0.2126 * $bgRgb[0] + 0.7152 * $bgRgb[1] + 0.0722 * $bgRgb[2]) / 255;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($im, $x, $y);
            if ($hasAlpha) {
                $a = 1 - ((($c >> 24) & 0x7F) / 127);
            } else {
                $r = (($c >> 16) & 255) - $bgRgb[0];
                $g = (($c >> 8) & 255) - $bgRgb[1];
                $b = ($c & 255) - $bgRgb[2];
                $d = sqrt($r * $r + $g * $g + $b * $b) / 441.7;
                if ($bgHint === 'dark' || ($bgHint === 'auto' && $bgLum < 0.5)) {
                    $d = max(0.0, $lumAt($c) - $bgLum) * 1.2 + $d * 0.3;
                } elseif ($bgHint === 'light' || ($bgHint === 'auto')) {
                    $d = max(0.0, $bgLum - $lumAt($c)) * 1.2 + $d * 0.3;
                }
                $t = max(0.0, min(1.0, ($d - 0.06) / 0.4));
                $a = $t * $t * (3 - 2 * $t);
            }
            imagesetpixel($mask, $x, $y, imagecolorallocatealpha($mask, 255, 255, 255, (int)round((1 - $a) * 127)));
        }
    }
    $bb = gd_bbox($mask, function ($c) { return (127 - (($c >> 24) & 0x7F)) > 12; }, 2);
    if (!$bb) {
        return [false, 'Logoda görünür bir şekil bulunamadı. Arka plan seçeneğini değiştirip tekrar deneyin.', []];
    }
    $pad = (int)round(max($bb[2], $bb[3]) * 0.04);
    $cx = max(0, $bb[0] - $pad);
    $cy = max(0, $bb[1] - $pad);
    $cw = min($w - $cx, $bb[2] + 2 * $pad);
    $ch = min($h - $cy, $bb[3] + 2 * $pad);
    $cropMask = imagecrop($mask, ['x' => $cx, 'y' => $cy, 'width' => $cw, 'height' => $ch]);
    $scale = fn_scale($cw, $ch, 600);
    $cropMask = gd_scale($cropMask, (int)($cw * $scale), (int)($ch * $scale));
    imagesavealpha($cropMask, true);
    $pm = "$dir/logo-mask-$tag.png";
    imagepng($cropMask, UZ_ROOT . '/' . $pm, 6);
    // colour version (transparent source keeps transparency; opaque source is used as is)
    $cropCol = imagecrop($im, ['x' => $cx, 'y' => $cy, 'width' => $cw, 'height' => $ch]);
    $cropCol = gd_scale($cropCol, (int)($cw * fn_scale($cw, $ch, 800)), (int)($ch * fn_scale($cw, $ch, 800)));
    imagesavealpha($cropCol, true);
    $pc = "$dir/logo-color-$tag.png";
    imagepng($cropCol, UZ_ROOT . '/' . $pc, 6);
    $out += ['brand_logo_mask' => $pm, 'brand_logo_color' => $pc, 'brand_logo_ratio' => (string)round($cw / $ch, 3)];
    // favicon + apple-touch from the active accent / palette
    $seeds = brand_active_seeds();
    $useColor = $out['brand_logo_mode'] === 'color';
    $src = $useColor ? $cropCol : $cropMask;
    $out['brand_favicon'] = brand_icon($src, 64, null, $useColor ? null : $seeds['accent'], 0.92, "$dir/favicon-$tag.png");
    $out['brand_apple'] = brand_icon($src, 180, $seeds['bg'], $useColor ? null : $seeds['accent'], 0.66, "$dir/apple-touch-$tag.png");
    return [true, 'Logo işlendi: tema rengiyle boyanabilir sürüm, favicon ve uygulama ikonu üretildi.', $out];
}

function fn_scale(int $w, int $h, int $max): float
{
    return max($w, $h) > $max ? $max / max($w, $h) : 1.0;
}

function gd_scale($im, int $w, int $h)
{
    if ($w === imagesx($im) && $h === imagesy($im)) {
        return $im;
    }
    $t = imagecreatetruecolor(max(1, $w), max(1, $h));
    imagealphablending($t, false);
    imagesavealpha($t, true);
    imagefill($t, 0, 0, imagecolorallocatealpha($t, 0, 0, 0, 127));
    imagecopyresampled($t, $im, 0, 0, 0, 0, max(1, $w), max(1, $h), imagesx($im), imagesy($im));
    return $t;
}

function brand_icon($src, int $size, ?string $bgHex, ?string $tintHex, float $fill, string $path): string
{
    $c = imagecreatetruecolor($size, $size);
    imagealphablending($c, false);
    imagesavealpha($c, true);
    if ($bgHex) {
        [$r, $g, $b] = hex2rgb($bgHex);
        imagefill($c, 0, 0, imagecolorallocate($c, $r, $g, $b));
    } else {
        imagefill($c, 0, 0, imagecolorallocatealpha($c, 0, 0, 0, 127));
    }
    $sw = imagesx($src);
    $sh = imagesy($src);
    $k = min($size * $fill / $sw, $size * $fill / $sh);
    $dw = max(1, (int)round($sw * $k));
    $dh = max(1, (int)round($sh * $k));
    $logo = gd_scale($src, $dw, $dh);
    if ($tintHex) {
        [$tr, $tg, $tb] = hex2rgb($tintHex);
        for ($y = 0; $y < $dh; $y++) {
            for ($x = 0; $x < $dw; $x++) {
                $a = (imagecolorat($logo, $x, $y) >> 24) & 0x7F;
                imagesetpixel($logo, $x, $y, imagecolorallocatealpha($logo, $tr, $tg, $tb, $a));
            }
        }
    }
    imagealphablending($c, true);
    imagecopy($c, $logo, (int)(($size - $dw) / 2), (int)(($size - $dh) / 2), 0, 0, $dw, $dh);
    imagesavealpha($c, true);
    imagepng($c, UZ_ROOT . '/' . $path, 6);
    return $path;
}
