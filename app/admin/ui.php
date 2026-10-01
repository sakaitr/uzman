<?php
declare(strict_types=1);

function admin_url(string $a, array $p = []): string
{
    return 'index.php?' . http_build_query(['a' => $a] + $p);
}

function redirect_to(string $a, array $p = []): void
{
    header('Location: ' . admin_url($a, $p));
    exit;
}

function new_submissions(): int
{
    return (int)val("SELECT COUNT(*) FROM submissions WHERE status = 'new'");
}

function ahead(string $title, string $active = '', string $sub = ''): void
{
    $u = admin_user();
    $items = [
        ['dash', 'Panel'], ['pages', 'Sayfalar'], ['strings', 'Site metinleri'], ['catalog', 'Ürünler'], ['tubes', 'Private Label tüpler'],
        ['hero', 'Ana sayfa slider'], ['docs', 'Belgeler / sertifikalar'], ['media', 'Medya'], ['subs', 'Başvurular'], ['seo', 'SEO & GEO'], ['sep'], ['settings', 'Ayarlar'], ['users', 'Kullanıcılar'], ['tools', 'Araçlar'],
    ];
    $n = new_submissions();
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="' . h(csrf_token()) . '"><title>' . h($title) . ' — Uzman Yönetim</title>
<link rel="stylesheet" href="../assets/css/fonts.css"><link rel="stylesheet" href="admin.css?v=' . UZ_VERSION . '"></head><body>
<div class="layout"><aside class="side"><a class="brand" href="' . admin_url('dash') . '"><i>U</i>UZMAN</a><nav>';
    foreach ($items as $it) {
        if ($it[0] === 'sep') {
            echo '<div class="sep"></div>';
            continue;
        }
        echo '<a href="' . admin_url($it[0]) . '" class="' . ($active === $it[0] ? 'on' : '') . '">' . h($it[1]) . ($it[0] === 'subs' && $n ? '<span class="badge">' . $n . '</span>' : '') . '</a>';
    }
    echo '</nav><div class="foot"><div style="margin-bottom:8px"><a href="../" target="_blank" rel="noopener">Siteyi görüntüle ↗</a></div>' . h($u['name'] ?: $u['username']) . '<br><a href="' . admin_url('logout') . '">Çıkış yap</a></div></aside>
<main class="content"><div class="pagehead"><div><h1>' . h($title) . '</h1>' . ($sub ? '<p>' . h($sub) . '</p>' : '') . '</div></div>' . flash_html();
}

function afoot(): void
{
    echo '</main></div>
<div class="modal" id="picker"><div class="box"><header><strong>Görsel seç</strong><input type="text" id="pk-q" placeholder="Ara…" style="max-width:240px"><label class="btn sm">Yeni yükle<input type="file" id="pk-up" accept="image/*,.pdf" hidden></label><button class="btn sm" type="button" data-close>Kapat</button></header><div class="gal" id="pk-gal"></div></div></div>
<script src="admin.js?v=' . UZ_VERSION . '"></script></body></html>';
}

function langbar(): string
{
    $b = '<div class="langbar"><span>Düzenlenen dil:</span>';
    foreach (LANGS as $l) {
        $b .= '<button type="button" data-l="' . $l . '">' . LANG_LABEL[$l] . '</button>';
    }
    return $b . '</div>';
}

/** Multilingual input: $name[tr], $name[en] … */
function ml_input(string $name, $values, string $label = '', string $kind = 'input', bool $required = true): string
{
    $v = is_array($values) ? $values : [];
    $tabs = $panes = '';
    foreach (LANGS as $l) {
        $tabs .= '<button type="button" data-l="' . $l . '">' . LANG_LABEL[$l] . '</button>';
        $nm = $name . '[' . $l . ']';
        $val = h((string)($v[$l] ?? ''));
        $dir = $l === 'ar' ? ' dir="rtl"' : '';
        $panes .= '<div class="ml-pane" data-l="' . $l . '">' . ($kind === 'area'
            ? '<textarea name="' . h($nm) . '"' . $dir . '>' . $val . '</textarea>'
            : '<input type="text" name="' . h($nm) . '" value="' . $val . '"' . $dir . '>') . '</div>';
    }
    return '<div class="ml" data-req="' . ($required ? '1' : '0') . '">' . ($label !== '' ? '<label class="l">' . h($label) . '</label>' : '') . '<div class="ml-tabs">' . $tabs . '</div>' . $panes . '</div>';
}

function img_input(string $name, string $value, string $label = 'Görsel', bool $pdf = false): string
{
    $pv = $value !== '' ? (preg_match('/\.pdf$/i', $value) ? 'PDF' : '<img src="../' . h($value) . '" alt="">') : '';
    return '<div class="field"><label class="l">' . h($label) . '</label><div class="img-in"><span class="pv">' . $pv . '</span>
<input type="text" name="' . h($name) . '" value="' . h($value) . '" placeholder="assets/img/… veya uploads/…">
<button type="button" class="btn sm" data-pick>Seç / yükle</button><button type="button" class="btn sm" data-clear>Temizle</button></div></div>';
}

function field(string $label, string $name, $value = '', string $type = 'text', string $hint = ''): string
{
    return '<div class="field"><label class="l" for="f_' . h($name) . '">' . h($label) . '</label><input type="' . $type . '" id="f_' . h($name) . '" name="' . h($name) . '" value="' . h((string)$value) . '">' . ($hint ? '<div class="hint">' . h($hint) . '</div>' : '') . '</div>';
}

function checkbox(string $name, bool $on, string $label): string
{
    return '<label style="display:flex;gap:8px;align-items:center;margin-bottom:10px"><input type="hidden" name="' . h($name) . '" value="0"><input type="checkbox" name="' . h($name) . '" value="1"' . ($on ? ' checked' : '') . '> ' . h($label) . '</label>';
}

/** Collect {lang: text} from POST array; text optionally sanitised. */
function post_ml(string $name, bool $html = true): array
{
    $out = [];
    foreach (LANGS as $l) {
        $v = (string)(($_POST[$name] ?? [])[$l] ?? '');
        $out[$l] = $html ? clean_html($v) : trim($v);
    }
    return $out;
}

function post_str(string $k, int $max = 500): string
{
    return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
}

function safe_path(string $p): string
{
    $p = trim($p);
    return preg_match('#^(assets|uploads)/[A-Za-z0-9_./-]+$#', $p) && strpos($p, '..') === false ? $p : '';
}

function when_saved(): void
{
    cache_clear();
}

// ------------------------------------------------------------------ block editor
function block_specs(): array
{
    return [
        'text' => ['Metin bölümü', [['title', 'ml', 'Başlık'], ['body', 'area', 'Metin (paragraflar boş satırla ayrılır)'], ['band', 'check', 'Arka planı vurgula']], null],
        'steps' => ['Adımlar / süreç', [['title', 'ml', 'Başlık (boş bırakılabilir)'], ['lead', 'ml', 'Alt metin (boş bırakılabilir)']], [['title', 'ml', 'Adım başlığı'], ['text', 'area', 'Açıklama']]],
        'cards' => ['Kartlar (3\'lü ızgara)', [['title', 'ml', 'Başlık']], [['title', 'ml', 'Kart başlığı'], ['text', 'area', 'Açıklama']]],
        'checks' => ['Madde listesi', [['title', 'ml', 'Başlık']], [['text', 'ml', 'Madde']]],
        'faq' => ['Sık sorulan sorular', [['title', 'ml', 'Başlık (boş bırakılabilir)']], [['q', 'ml', 'Soru'], ['a', 'area', 'Cevap']]],
        'image_text' => ['Görsel + metin', [['title', 'ml', 'Başlık'], ['body', 'area', 'Metin'], ['image', 'img', 'Görsel'], ['side', 'side', 'Görsel konumu']], null],
        'certs' => ['Belgeler / sertifikalar (Belgeler menüsünden gelir)', [['title', 'ml', 'Başlık']], null],
    ];
}

function render_fields(string $prefix, array $fields, array $data): string
{
    $o = '';
    foreach ($fields as [$k, $kind, $label]) {
        $nm = $prefix . '[' . $k . ']';
        $v = $data[$k] ?? ($kind === 'ml' || $kind === 'area' ? [] : '');
        if ($kind === 'ml') {
            $o .= ml_input($nm, $v, $label, 'input', false);
        } elseif ($kind === 'area') {
            $o .= ml_input($nm, $v, $label, 'area', false);
        } elseif ($kind === 'img') {
            $o .= img_input($nm, (string)$v, $label);
        } elseif ($kind === 'check') {
            $o .= checkbox($nm, !empty($v), $label);
        } elseif ($kind === 'side') {
            $o .= '<div class="field"><label class="l">' . h($label) . '</label><select name="' . h($nm) . '"><option value="right"' . ($v !== 'left' ? ' selected' : '') . '>Sağda</option><option value="left"' . ($v === 'left' ? ' selected' : '') . '>Solda</option></select></div>';
        }
    }
    return $o;
}

function render_item(string $type, string $bid, string $iid, array $data): string
{
    $spec = block_specs()[$type][2];
    return '<div class="item" data-row><header><span>Öğe</span><span class="actions"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button><button type="button" class="btn sm danger" data-del=".item">Sil</button></span></header>'
        . render_fields("blocks[$bid][items][$iid]", $spec, $data) . '</div>';
}

function render_block(string $bid, array $b): string
{
    $type = $b['type'] ?? 'text';
    $spec = block_specs()[$type] ?? null;
    if (!$spec) {
        return '';
    }
    $items = '';
    if ($spec[2] !== null) {
        $n = 0;
        foreach ($b['items'] ?? [] as $it) {
            $items .= render_item($type, $bid, 'e' . $n++, $it);
        }
        $items = '<div class="items">' . $items . '</div><button type="button" class="btn sm" data-additem="' . $type . '">+ Öğe ekle</button>';
    }
    return '<div class="block" data-row data-bid="' . h($bid) . '"><header><strong>' . h($spec[0]) . '</strong><span class="actions"><button type="button" class="btn sm" data-move="up">↑</button><button type="button" class="btn sm" data-move="down">↓</button><button type="button" class="btn sm danger" data-del=".block">Sil</button></span></header>
<div class="bbody"><input type="hidden" name="blocks[' . h($bid) . '][type]" value="' . h($type) . '">' . render_fields("blocks[$bid]", $spec[1], $b) . $items . '</div></div>';
}

/** Parse POST blocks into the stored structure. */
function parse_blocks(): array
{
    $out = [];
    foreach ((array)($_POST['blocks'] ?? []) as $b) {
        $type = (string)($b['type'] ?? '');
        $spec = block_specs()[$type] ?? null;
        if (!$spec) {
            continue;
        }
        $nb = ['type' => $type];
        foreach ($spec[1] as [$k, $kind]) {
            if ($kind === 'ml' || $kind === 'area') {
                $nb[$k] = array_map(function ($x) { return clean_html((string)$x); }, array_intersect_key((array)($b[$k] ?? []), array_flip(LANGS)));
            } elseif ($kind === 'img') {
                $nb[$k] = safe_path((string)($b[$k] ?? ''));
            } elseif ($kind === 'check') {
                $nb[$k] = (int)($b[$k] ?? 0);
            } else {
                $nb[$k] = ($b[$k] ?? '') === 'left' ? 'left' : 'right';
            }
        }
        if ($spec[2] !== null) {
            $nb['items'] = [];
            foreach ((array)($b['items'] ?? []) as $it) {
                $ni = [];
                foreach ($spec[2] as [$k]) {
                    $ni[$k] = array_map(function ($x) { return clean_html((string)$x); }, array_intersect_key((array)($it[$k] ?? []), array_flip(LANGS)));
                }
                if (array_filter($ni, function ($m) { return array_filter($m, 'strlen'); })) {
                    $nb['items'][] = $ni;
                }
            }
        }
        $out[] = $nb;
    }
    return $out;
}
