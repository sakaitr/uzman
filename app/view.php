<?php
/** Front-end rendering — a faithful PHP port of the approved static design (build/build.py). */
declare(strict_types=1);

const SYS_NAV_KEY = ['private-label' => 'nav_pl', 'products' => 'nav_products', 'about' => 'nav_about', 'contact' => 'nav_contact',
                     'body-care' => 'cat_body', 'home-care' => 'cat_home'];
const SYS_TITLE_KEY = ['index' => ['title_index', 'desc_index'], 'products' => ['title_products', 'pr_lead'], 'body-care' => ['title_body', 'bc_lead'],
                       'home-care' => ['title_home', 'hc_lead'], 'private-label' => ['title_pl', 'pl_page_lead'], 'about' => ['title_about', 'ab_lead'],
                       'contact' => ['title_contact', 'ct_lead']];

function cur_lang(): string
{
    return $GLOBALS['UZ_LANG'] ?? DEFAULT_LANG;
}

function asset_prefix(): string
{
    if (!empty($GLOBALS['UZ_ABS'])) {
        return base_url();
    }
    return cur_lang() === DEFAULT_LANG ? '' : '../';
}

/** URL of a page (slug) in $lang as seen from the current page. */
function page_url(string $lang, string $slug, ?string $from = null): string
{
    $from = $from ?? cur_lang();
    $file = $slug . '.html';
    if (!empty($GLOBALS['UZ_ABS'])) {
        return base_url() . ($lang === DEFAULT_LANG ? '' : $lang . '/') . $file;
    }
    if ($lang === $from) {
        return $file;
    }
    if ($from === DEFAULT_LANG) {
        return $lang . '/' . $file;
    }
    return $lang === DEFAULT_LANG ? '../' . $file : '../' . $lang . '/' . $file;
}

function u(string $slug): string
{
    return page_url(cur_lang(), $slug);
}

function ml_sizes(string $sizes): string
{
    $a = array_filter(array_map('trim', explode(',', $sizes)), 'strlen');
    return $a ? implode(' · ', $a) . ' ' . UNIT[cur_lang()] : '';
}

function bdi(string $s): string
{
    return '<bdi dir="ltr">' . $s . '</bdi>';
}

function picture(string $path, string $alt = '', string $cls = '', bool $eager = false): string
{
    [$w, $h] = img_size($path);
    $dim = $w ? ' width="' . $w . '" height="' . $h . '"' : '';
    return '<img src="' . h(asset_prefix() . $path) . '" alt="' . attr($alt) . '"' . $dim . ($eager ? '' : ' loading="lazy"') . ' decoding="async" class="' . h($cls) . '">';
}

function vbg(string $name, string $opacity = '.5', string $cls = ''): string
{
    if (setting('hero_video', '1') !== '1') {
        return '';
    }
    $A = asset_prefix();
    return '<div class="vbg ' . $cls . '" style="--vo:' . h($opacity) . '" aria-hidden="true"><video muted loop playsinline preload="none" poster="' . $A . 'assets/video/' . $name . '.jpg" data-src="' . $A . 'assets/video/' . $name . '.mp4" data-webm="' . $A . 'assets/video/' . $name . '.webm"></video></div>';
}

function nav_pages(string $col, int $value = 1): array
{
    static $all = null;
    if ($all === null) {
        $all = rows('SELECT * FROM pages WHERE status = 1 ORDER BY sort, id');
    }
    return array_values(array_filter($all, function ($p) use ($col, $value) { return (int)$p[$col] === $value; }));
}

function page_label(array $p): string
{
    if (isset(SYS_NAV_KEY[$p['slug']])) {
        return t(SYS_NAV_KEY[$p['slug']]);
    }
    return ml(jd($p['title']));
}

function preload_fonts(string $A): string
{
    $lang = cur_lang();
    $names = ['cormorant-garamond-latin', 'manrope-latin'];
    if ($lang === 'ru') {
        $names = ['cormorant-garamond-cyrillic', 'manrope-cyrillic'];
    }
    if ($lang === 'ar') {
        $names = ['noto-naskh-arabic-arabic', 'tajawal-arabic'];
    }
    $out = '';
    foreach ($names as $n) {
        $f = glob(UZ_ROOT . '/assets/fonts/' . $n . '-*.woff2');
        if ($f) {
            sort($f);
            $out .= '<link rel="preload" href="' . $A . 'assets/fonts/' . basename($f[0]) . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }
    }
    return $out;
}

function head(string $slug, string $title, string $desc, bool $noindex = false, string $extra = ''): string
{
    $lang = cur_lang();
    $A = asset_prefix();
    $site = canonical_base();
    $theme = THEMES[setting('theme', 'noir')] ?? THEMES['noir'];
    $themeKey = isset(THEMES[setting('theme', 'noir')]) ? setting('theme', 'noir') : 'noir';
    $switcher = setting('theme_switcher', '0') === '1';
    $alt = '';
    foreach (LANGS as $l) {
        $alt .= '<link rel="alternate" hreflang="' . $l . '" href="' . $site . ($l === DEFAULT_LANG ? '' : $l . '/') . $slug . '.html">' . "\n";
    }
    $alt .= '<link rel="alternate" hreflang="x-default" href="' . $site . $slug . '.html">';
    $canon = $site . ($lang === DEFAULT_LANG ? '' : $lang . '/') . $slug . '.html';
    $d = attr(plain($desc));
    $ti = h(plain($title));
    $robots = ($noindex || setting('robots_index', '1') !== '1') ? '<meta name="robots" content="noindex,nofollow">' . "\n" : '';
    $verify = (seo('seo_gsc') !== '' ? '<meta name="google-site-verification" content="' . h(seo('seo_gsc')) . '">' . "\n" : '') . (seo('seo_bing') !== '' ? '<meta name="msvalidate.01" content="' . h(seo('seo_bing')) . '">' . "\n" : '');
    $themeScript = $switcher
        ? "<script>try{var s=new URLSearchParams(location.search).get('theme')||localStorage.getItem('uzman-theme');if(['noir','bordeaux','emerald','twotone','inverse'].indexOf(s)>-1)document.documentElement.setAttribute('data-theme',s)}catch(e){}</script>\n"
        : '';
    return '<!DOCTYPE html>
<html lang="' . $lang . '" dir="' . (in_array($lang, RTL_LANGS, true) ? 'rtl' : 'ltr') . '" class="no-js" data-theme="' . $themeKey . '" data-switcher="' . ($switcher ? '1' : '0') . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="' . $theme[1] . '">
<title>' . $ti . '</title>
<meta name="description" content="' . $d . '">
' . $robots . '<link rel="canonical" href="' . $canon . '">
' . $alt . '
' . preload_fonts($A) . '<link rel="stylesheet" href="' . $A . 'assets/css/fonts.css">
<meta property="og:type" content="website">
<meta property="og:site_name" content="' . h(setting('site_name', 'Uzman Cosmetic')) . '">
<meta property="og:title" content="' . $ti . '">
<meta property="og:description" content="' . $d . '">
<meta property="og:image" content="' . h(abs_url(seo('seo_og_image') ?: 'assets/img/og.jpg')) . '">
<meta property="og:url" content="' . $canon . '">
<meta property="og:locale" content="' . $lang . '">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . $ti . '">
<meta name="twitter:image" content="' . h(abs_url(seo('seo_og_image') ?: 'assets/img/og.jpg')) . '">
<link rel="icon" type="image/png" href="' . $A . 'assets/img/favicon.png">
<link rel="apple-touch-icon" href="' . $A . 'assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="' . $A . 'assets/css/style.css?v=' . UZ_VERSION . '">
' . $verify . $themeScript . $extra . '</head>
<body data-page="' . h($slug) . '">
<a class="skip" href="#main">' . t('skip') . '</a>
<div class="progress" aria-hidden="true"></div>
';
}

function lang_switch(string $slug, string $cls = 'lang'): string
{
    $items = '';
    foreach (LANGS as $l) {
        if ($l === cur_lang()) {
            $items .= '<b aria-current="true">' . LANG_LABEL[$l] . '</b>';
        } else {
            $items .= '<a href="' . h(page_url($l, $slug)) . '" hreflang="' . $l . '" lang="' . $l . '" title="' . h(LANG_NAME[$l]) . '">' . LANG_LABEL[$l] . '</a>';
        }
    }
    return '<div class="' . $cls . '" role="group" aria-label="' . attr(t('aria_lang')) . '">' . $items . '</div>';
}

function theme_switch(): string
{
    if (setting('theme_switcher', '0') !== '1') {
        return '';
    }
    $b = '';
    foreach (THEMES as $k => $v) {
        $b .= '<button data-set="' . $k . '" aria-label="' . h($v[0]) . '" title="' . h($v[0]) . '"></button>';
    }
    return '<div class="theme-switch" role="group" aria-label="' . attr(t('aria_theme')) . '">' . $b . '</div>';
}

function tel_href(string $n): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $n);
}

function header_html(string $slug): string
{
    $active = in_array($slug, ['body-care', 'home-care'], true) ? 'products' : $slug;
    $nav = $mobile = '';
    foreach (nav_pages('in_nav') as $p) {
        $nav .= '<a href="' . h(u($p['slug'])) . '" class="' . ($p['slug'] === $active ? 'active' : '') . '">' . page_label($p) . '</a>';
        $mobile .= '<a class="big" href="' . h(u($p['slug'])) . '">' . page_label($p) . '</a>';
    }
    $email = setting('email');
    $ph = setting('phone1');
    $quote = u('contact') . '#quote';
    return '<div class="site-header" id="siteHeader">
  <div class="topline"><div class="wrap">
    <div class="left"><span class="dot"></span>' . t('top_loc') . '<span class="hide-sm"> &nbsp;·&nbsp; ' . t('top_since') . ' &nbsp;·&nbsp; ' . t('top_export') . '</span></div>
    <div class="right">' . ($email ? '<a href="mailto:' . h($email) . '" class="hide-sm">' . h($email) . '</a>' : '') . ($ph ? '<a href="' . h(tel_href($ph)) . '" dir="ltr">' . h($ph) . '</a>' : '') . '</div>
  </div></div>
  <div class="wrap navbar">
    <a href="' . h(u('index')) . '" class="brand" aria-label="' . attr(t('aria_home')) . '">
      <span class="brand-logo" aria-hidden="true"></span>
      <span class="brand-name">UZMAN<span class="brand-sub">' . t('brand_sub') . '</span></span>
    </a>
    <nav class="nav">' . $nav . '</nav>
    <div class="nav-right">
      ' . theme_switch() . '
      ' . lang_switch($slug) . '
      <a href="' . h($quote) . '" class="btn nav-cta"><span>' . t('nav_quote') . '</span></a>
      <button class="burger" id="burger" aria-label="' . attr(t('aria_menu')) . '"><i></i><i></i></button>
    </div>
  </div>
</div>
<div class="mobile-menu" id="mobileMenu">
  ' . $mobile . '
  <a class="big" href="' . h($quote) . '" style="color:var(--gold-hi)">' . t('nav_quote') . '</a>
  <div class="mm-tools">' . theme_switch() . lang_switch($slug) . '</div>
</div>
<main id="main">
';
}

function footer_html(): string
{
    $A = asset_prefix();
    $disc = '';
    foreach (nav_pages('in_footer') as $p) {
        $disc .= '<li><a href="' . h(u($p['slug'])) . '">' . page_label($p) . '</a></li>';
    }
    $legal = '';
    foreach (nav_pages('in_footer', 2) as $p) {
        $legal .= '<a href="' . h(u($p['slug'])) . '">' . page_label($p) . '</a>';
    }
    $prods = '';
    foreach (rows('SELECT slug, page, name FROM categories ORDER BY sort') as $c) {
        $prods .= '<li><a href="' . h(u($c['page'])) . '">' . ml(jd($c['name'])) . '</a></li>';
    }
    $email = setting('email');
    $ph = setting('phone1');
    $ig = setting('instagram');
    $wa = preg_replace('/\D/', '', (string)setting('whatsapp'));
    $waBtn = $wa ? '<a class="wa" href="https://wa.me/' . $wa . '" rel="noopener" target="_blank" aria-label="' . attr(t('wa_label')) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.2a8.7 8.7 0 0 0-7.5 13.1L3.3 20.8l4.6-1.2A8.7 8.7 0 1 0 12 3.2Zm0 1.7a7 7 0 1 1-3.7 13l-.3-.2-2.7.7.7-2.6-.2-.3A7 7 0 0 1 12 4.9Zm-2.4 3.3c-.2 0-.4.1-.6.3-.2.3-.8.8-.8 1.9s.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.4 1.9.7 2.3.6 2.7.5.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.4-.2-1.3-.6c-.2-.1-.4-.1-.5.1l-.6.8c-.1.2-.3.2-.5.1-.9-.4-1.9-1.1-2.6-2.1-.1-.2 0-.3.1-.4l.4-.5.2-.4v-.4l-.6-1.5c-.1-.4-.3-.4-.4-.4Z"/></svg></a>' : '';
    return '</main>
<footer class="site-footer">
  <div class="wrap">
    <div class="foot-top">
      <div>
        <a href="' . h(u('index')) . '" class="brand" style="margin-bottom:24px"><span class="brand-logo lg" aria-hidden="true"></span><span class="brand-name">UZMAN<span class="brand-sub">COSMETIC</span></span></a>
        <p style="max-width:38ch">' . t('foot_blurb') . '</p>
      </div>
      <div><h3>' . t('foot_discover') . '</h3><ul>' . $disc . '</ul></div>
      <div><h3>' . t('foot_products') . '</h3><ul>' . $prods . '</ul></div>
      <div><h3>' . t('foot_hq') . '</h3><p>' . t('foot_addr') . '<br>' . ($ph ? '<a href="' . h(tel_href($ph)) . '" dir="ltr">' . h($ph) . '</a><br>' : '')
        . ($email ? '<a href="mailto:' . h($email) . '">' . h($email) . '</a><br>' : '')
        . ($ig ? '<a href="' . h($ig) . '" rel="noopener">@' . h(trim(parse_url($ig, PHP_URL_PATH) ?: 'uzmancosmetic', '/')) . '</a>' : '') . '</p></div>
    </div>
    <div class="foot-word" aria-hidden="true">UZMAN</div>
    <div class="foot-bot"><span>' . t('foot_rights') . '</span>' . ($legal ? '<nav class="foot-legal" aria-label="' . attr(t('foot_legal')) . '">' . $legal . '</nav>' : '<span>ÇAYIROVA · KOCAELİ · TÜRKİYE</span>') . '</div>
  </div>
</footer>
' . $waBtn . '<script src="' . $A . 'assets/js/main.js?v=' . UZ_VERSION . '" defer></script>
</body>
</html>
';
}

function cta_band(bool $video = false): string
{
    $bg = $video ? vbg('hero', '.4') : '';
    return '<section class="cta-band' . ($bg ? ' has-vbg' : '') . '">' . $bg . '<span class="logo-wm" aria-hidden="true"></span><div class="wrap">
  <h2 class="rv">' . t('cta_h2') . '</h2>
  <p class="lead rv">' . t('cta_lead') . '</p>
  <div class="cta-row rv"><a href="' . h(u('contact')) . '#quote" class="btn btn-solid"><span>' . t('cta_btn') . '</span><i class="arrow"></i></a></div>
</div></section>';
}

function page_hero(string $h1, string $lead): string
{
    return '<header class="page-hero">
  <div class="wrap"><h1>' . $h1 . '</h1>
  <p class="lead">' . $lead . '</p></div>
</header>';
}

function values_html(bool $band = false): string
{
    $cells = '';
    for ($i = 1; $i <= 3; $i++) {
        $cells .= '<div class="value rv" style="--d:' . number_format(($i - 1) * .1, 1) . 's"><h3>' . t("v{$i}_t") . '</h3><p>' . t("v{$i}_p") . '</p></div>';
    }
    return '<section class="section' . ($band ? ' band' : '') . '"><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>' . t('val_h2') . '</h2></div></div>
  <div class="values">' . $cells . '</div>
</div></section>';
}

function steps_html(): string
{
    $rows = '';
    for ($i = 1; $i <= 5; $i++) {
        $rows .= '<div class="step rv"><div class="n">0' . $i . '</div><div><h3>' . t("s{$i}_t") . '</h3><p>' . t("s{$i}_p") . '</p></div></div>';
    }
    return '<div class="steps">' . $rows . '</div>';
}

function fan_html(array $paths, string $alt = '', bool $eager = false): string
{
    $imgs = '';
    foreach (array_values($paths) as $i => $p) {
        $imgs .= '<span class="f' . ($i + 1) . '">' . picture($p, $i === 0 ? plain($alt) : '', '', $eager) . '</span>';
    }
    return '<div class="fan">' . $imgs . '</div>';
}

function setting_fan(string $key): array
{
    $a = jd(setting($key, '[]'));
    return array_slice(array_values(array_filter($a, 'strlen')), 0, 3);
}

// ------------------------------------------------------------------ catalog data
function catalog(): array
{
    static $tree = null;
    if ($tree !== null) {
        return $tree;
    }
    $items = [];
    foreach (rows('SELECT * FROM items WHERE active = 1 ORDER BY sort, id') as $it) {
        $items[$it['sub_id']][] = $it;
    }
    $subs = rows('SELECT * FROM subcats ORDER BY sort, id');
    $children = [];
    foreach ($subs as $s) {
        if ($s['parent_id']) {
            $children[$s['parent_id']][] = $s;
        }
    }
    $tree = [];
    foreach (rows('SELECT * FROM categories ORDER BY sort, id') as $c) {
        $c['subs'] = [];
        foreach ($subs as $s) {
            if ((int)$s['cat_id'] === (int)$c['id'] && !$s['parent_id']) {
                $s['items'] = $items[$s['id']] ?? [];
                $s['children'] = [];
                foreach ($children[$s['id']] ?? [] as $ch) {
                    $ch['items'] = $items[$ch['id']] ?? [];
                    $s['children'][] = $ch;
                }
                $c['subs'][] = $s;
            }
        }
        $tree[] = $c;
    }
    return $tree;
}

function item_caption(array $it): string
{
    $c = (string)$it['cap'];
    if ($c !== '' && $c[0] === '{') {
        return ml(jd($c));
    }
    return $c;
}

// ------------------------------------------------------------------ pages
function page_index(): string
{
    $facts = '';
    for ($i = 1; $i <= 6; $i++) {
        $facts .= '<li>' . t("m{$i}") . '</li>';
    }
    $cards = '';
    foreach (catalog() as $i => $c) {
        $names = implode(' · ', array_map(function ($s) { return ml(jd($s['name'])); }, $c['subs']));
        $cards .= '<a href="' . h(u($c['page'])) . '" class="coll rv" style="--d:' . number_format($i * .12, 2) . 's">' . fan_html(setting_fan($c['slug'] === 'home' ? 'fan_home' : 'fan_body'), ml(jd($c['name'])))
            . '<h3>' . ml(jd($c['name'])) . '</h3><p>' . $names . '</p><span class="link-arrow more">' . t('explore') . ' <i class="arrow"></i></span></a>';
    }
    $slides = rows('SELECT * FROM hero_slides WHERE active = 1 ORDER BY sort, id');
    $A = asset_prefix();
    $sl = $dots = '';
    foreach ($slides as $i => $s) {
        $sl .= '<span class="slide' . ($i === 0 ? ' on' : '') . '" data-m="' . h($A . $s['image']) . '">' . picture($s['image'], $s['label'] . ' — ' . t('hero_alt'), '', $i === 0) . '</span>';
        $dots .= '<button role="tab" class="dot' . ($i === 0 ? ' on' : '') . '" aria-label="' . attr($s['label']) . '" aria-selected="' . ($i === 0 ? 'true' : 'false') . '"><b>' . h($s['label']) . '</b></button>';
    }
    $stage = $sl ? '<div class="hero-stage">
      <i class="arc a3"></i><i class="arc"></i><i class="arc a2"></i>
      <span class="hero-bottle"><span class="hero-float">
        <span class="slides" aria-roledescription="carousel">' . $sl . '</span>
        ' . vbg('hero', '.2', 'vbg-fg') . '
      </span></span>
      <span class="slide-nav" role="tablist">' . $dots . '</span>
    </div>' : '';
    $regions = '';
    for ($i = 1; $i <= 4; $i++) {
        $regions .= '<div>' . t("ex_f{$i}") . '<small>' . t("ex_f{$i}s") . '</small></div>';
    }
    $out = '
<section class="hero has-vbg">
  ' . vbg('hero', '.85') . '
  <div class="wrap hero-grid">
    <div>
      <h1>
        <span class="line"><span>' . t('hero_l1') . '</span></span>
        <span class="line"><span>' . t('hero_l2') . '</span></span>
        <span class="line"><span>' . t('hero_l3') . '</span></span>
      </h1>
      <p class="lead">' . t('hero_lead') . '</p>
      <div class="cta-row">
        <a href="' . h(u('contact')) . '#quote" class="btn btn-solid"><span>' . t('cta_start') . '</span><i class="arrow"></i></a>
        <a href="' . h(u('private-label')) . '" class="btn"><span>' . t('nav_pl') . '</span><i class="arrow"></i></a>
      </div>
    </div>
    ' . $stage . '
  </div>
</section>

<div class="facts"><ul>' . $facts . '</ul></div>

<section class="section manifesto has-vbg">' . vbg('petals', '.5') . '<div class="wrap">
  <p>' . t('manifesto') . '</p>
</div></section>

<section class="section has-vbg">' . vbg('fill', '.42', 'vbg-top') . '<div class="wrap">
  <div class="sec-head">
    <div class="rv"><h2>' . t('coll_h2') . '</h2></div>
    <p class="lead rv">' . t('coll_lead') . '</p>
  </div>
  <div class="collections two">' . $cards . '</div>
</div></section>

<section class="section"><div class="wrap process-grid">
  <div class="process-sticky rv">
    <h2>' . t('pl_h2') . '</h2>
    <p class="lead" style="margin-bottom:36px">' . t('pl_lead') . '</p>
    <a href="' . h(u('private-label')) . '" class="btn"><span>' . t('pl_btn') . '</span><i class="arrow"></i></a>
  </div>
  ' . steps_html() . '
</div></section>

<section class="section band has-vbg">' . vbg('line', '.5') . '<div class="wrap split">
  <div class="split-media rv">' . fan_html(setting_fan('fan_pw'), t('pw_cap')) . '<span class="cap">' . t('pw_cap') . '</span></div>
  <div class="rv" style="--d:.1s">
    <h2>' . t('pw_h2') . '</h2>
    <p class="lead">' . t('pw_lead') . '</p>
    <ul class="checks"><li>' . t('pw_c1') . '</li><li>' . t('pw_c2') . '</li><li>' . t('pw_c3') . '</li></ul>
    <a href="' . h(u('about')) . '" class="link-arrow">' . t('pw_link') . ' <i class="arrow"></i></a>
  </div>
</div></section>

' . values_html() . '

<section class="section band export"><div class="wrap export-grid">
  <div class="rv">
    <h2>' . t('ex_h2') . '</h2>
    <p class="lead">' . t('ex_lead') . '</p>
    <div class="regions">' . $regions . '</div>
  </div>
  <div class="globe rv" style="--d:.15s">
    <svg viewBox="0 0 200 200" aria-hidden="true">
      <circle class="ring" cx="100" cy="100" r="92"/><circle class="ring" cx="100" cy="100" r="70" stroke-dasharray="2 3"/>
      <ellipse class="ring" cx="100" cy="100" rx="92" ry="34"/><ellipse class="ring" cx="100" cy="100" rx="34" ry="92"/><ellipse class="ring" cx="100" cy="100" rx="64" ry="92" opacity=".5"/>
      <line class="ring" x1="8" y1="100" x2="192" y2="100"/>
      <g><path class="ring" d="M112 70 Q150 40 168 82" style="stroke:var(--gold)"/><path class="ring" d="M112 70 Q80 30 52 58" style="stroke:var(--gold)"/><path class="ring" d="M112 70 Q140 110 128 148" style="stroke:var(--gold)"/></g>
      <circle class="pulse" cx="112" cy="70" r="3"/><circle class="pin" cx="112" cy="70" r="3"/>
      <circle class="pin" cx="168" cy="82" r="2"/><circle class="pin" cx="52" cy="58" r="2"/><circle class="pin" cx="128" cy="148" r="2"/>
    </svg>
  </div>
</div></section>

' . cta_band(true);
    return $out;
}

function tree_card(array $cat, int $idx): string
{
    $rows = '';
    foreach ($cat['subs'] as $s) {
        $leaf = '';
        if ($s['children']) {
            $leaf = '<ul class="leaf">' . implode('', array_map(function ($ch) { return '<li>' . ml(jd($ch['name'])) . '</li>'; }, $s['children'])) . '</ul>';
        }
        $sz = ml_sizes((string)$s['sizes']);
        $rows .= '<li><a href="' . h(u($cat['page'])) . '#' . h($s['slug']) . '"><span class="nm">' . ml(jd($s['name'])) . '</span><span class="sz">' . ($sz ? bdi($sz) : '') . '</span></a>' . $leaf . '</li>';
    }
    return '<article class="tree rv" style="--d:' . number_format($idx * .12, 2) . 's">' . fan_html(setting_fan($cat['slug'] === 'home' ? 'fan_home' : 'fan_body'), ml(jd($cat['name']))) . '
  <header><h2><a href="' . h(u($cat['page'])) . '">' . ml(jd($cat['name'])) . '</a></h2></header>
  <ul class="branch">' . $rows . '</ul>
  <a href="' . h(u($cat['page'])) . '" class="link-arrow">' . t('explore') . ' <i class="arrow"></i></a>
</article>';
}

function page_products(): string
{
    $cards = '';
    foreach (catalog() as $i => $c) {
        $cards .= tree_card($c, $i);
    }
    return page_hero(t('pr_h1'), t('pr_lead')) . '<section class="section"><div class="wrap"><div class="tree-grid">' . $cards . '</div></div></section>' . cta_band();
}

function pcard(array $it): string
{
    $cap = item_caption($it);
    if ($it['image'] !== '') {
        return '<a class="pcard" href="' . h(asset_prefix() . $it['image']) . '" data-lb data-cap="' . attr($cap) . '">' . picture($it['image'], $cap) . '<span class="cap">' . h($cap) . '</span></a>';
    }
    return '<div class="pcard ph"><span class="ph-frame" aria-hidden="true"></span><span class="cap">' . h($cap) . '</span><span class="soon">' . t('soon') . '</span></div>';
}

function sub_section(array $s): string
{
    $sz = ml_sizes((string)$s['sizes']);
    $chips = $sz ? '<span class="chip">' . t('sizes') . ': ' . bdi($sz) . '</span>' : '';
    $html = '<div class="sub-head rv"><div><h2>' . ml(jd($s['name'])) . '</h2><p class="lead">' . ml(jd($s['desc'])) . '</p></div>' . $chips . '</div>';
    if ($s['children']) {
        foreach ($s['children'] as $ch) {
            $html .= '<h3 class="mini rv">' . ml(jd($ch['name'])) . '</h3><div class="pgrid">' . implode('', array_map('pcard', $ch['items'])) . '</div>';
        }
    } else {
        $html .= '<div class="pgrid">' . implode('', array_map('pcard', $s['items'])) . '</div>';
    }
    return '<section class="section sub" id="' . h($s['slug']) . '"><div class="wrap">' . $html . '</div></section>';
}

function page_category(string $slug): ?string
{
    $cat = null;
    foreach (catalog() as $c) {
        if ($c['page'] === $slug) {
            $cat = $c;
        }
    }
    if (!$cat) {
        return null;
    }
    $pre = $cat['slug'] === 'body' ? 'bc' : 'hc';
    $tabs = implode('', array_map(function ($s) { return '<a href="#' . h($s['slug']) . '">' . ml(jd($s['name'])) . '</a>'; }, $cat['subs']));
    return page_hero(t($pre . '_h1'), t($pre . '_lead'))
        . '<nav class="subnav"><div class="wrap"><a href="' . h(u('products')) . '" class="back">' . t('back_tree') . '</a>' . $tabs . '</div></nav>'
        . implode('', array_map('sub_section', $cat['subs'])) . cta_band();
}

function page_private_label(): string
{
    $models = rows('SELECT * FROM pl_models WHERE active = 1 ORDER BY sort, id');
    $groups = rows('SELECT * FROM pl_groups ORDER BY sort');
    $used = array_unique(array_column($models, 'grp'));
    $filters = '<button class="on" data-f="all">' . t('all') . '</button>';
    foreach ($groups as $g) {
        if (in_array($g['slug'], $used, true)) {
            $filters .= '<button data-f="' . h($g['slug']) . '">' . ml(jd($g['name'])) . '</button>';
        }
    }
    $cards = '';
    foreach ($models as $m) {
        $cards .= '<a class="tcard" data-cat="' . h($m['grp']) . '" href="' . h(asset_prefix() . $m['image']) . '" data-lb data-cap="' . attr($m['label'] . ' ' . $m['dims']) . '">'
            . picture($m['image'], $m['label']) . '<span class="cap"><b>' . h($m['label']) . '</b>' . ($m['dims'] !== '' ? bdi(h($m['dims'])) : '') . '</span></a>';
    }
    return page_hero(t('pl_h1'), t('pl_page_lead')) . '<section class="section"><div class="wrap process-grid">
  <div class="process-sticky rv"><h2>' . t('pl_proc_h2') . '</h2><p class="lead">' . t('pl_proc_lead') . '</p></div>
  ' . steps_html() . '
</div></section>
<section class="section band" id="tubes"><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>' . t('tube_h2') . '</h2></div><p class="lead rv">' . t('tube_lead') . '</p></div>
  <div class="filters rv">' . $filters . '</div>
  <div class="tgrid">' . $cards . '</div>
</div></section>' . cta_band();
}

function page_about(): string
{
    return page_hero(t('ab_h1'), t('ab_lead')) . '<section class="section manifesto"><div class="wrap"><p>' . t('ab_manifesto') . '</p></div></section>
<section class="section"><div class="wrap split">
  <div class="rv"><h2>' . t('ab_journey_h2') . '</h2>
    <div class="timeline" style="margin-top:48px">
      <div class="tl"><div class="yr">1978</div><p>' . t('tl1') . '</p></div>
      <div class="tl"><div class="yr">1980</div><p>' . t('tl2') . '</p></div>
      <div class="tl"><div class="yr">' . t('tl_today') . '</div><p>' . t('tl3') . '</p></div>
    </div>
  </div>
  <div class="split-media rv" style="--d:.1s">' . fan_html(setting_fan('fan_about'), t('ab_caption')) . '<span class="cap">' . t('ab_caption') . '</span></div>
</div></section>
' . values_html(true) . '
<section class="section"><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>' . t('ab_team_h2') . '</h2></div><p class="lead rv">' . t('ab_team_lead') . '</p></div>
</div></section>' . cta_band();
}

function page_contact(): string
{
    $opts = '';
    foreach (catalog() as $c) {
        foreach ($c['subs'] as $s) {
            $opts .= '<option>' . ml(jd($s['name'])) . '</option>';
        }
    }
    $opts .= '<option>' . t('f_pl_tube') . '</option><option>' . t('f_other') . '</option>';
    $A = asset_prefix();
    $ph1 = setting('phone1');
    $ph2 = setting('phone2');
    $fax = setting('fax');
    $email = setting('email');
    $ig = setting('instagram');
    $phones = ($ph1 ? '<a href="' . h(tel_href($ph1)) . '" dir="ltr">' . h($ph1) . '</a>' : '') . ($ph2 ? '<br><a href="' . h(tel_href($ph2)) . '" dir="ltr">' . h($ph2) . '</a>' : '')
        . ($fax ? '<p style="font-size:14px;color:var(--muted);margin-top:6px">' . t('ct_fax') . ': <bdi dir="ltr">' . h($fax) . '</bdi></p>' : '');
    return page_hero(t('ct_h1'), t('ct_lead')) . '<section class="section" id="quote"><div class="wrap contact-grid">
  <aside class="contact-info rv">
    <div><h2>' . t('ct_addr') . '</h2><p>' . t('foot_addr') . '</p></div>
    <div><h2>' . t('ct_phone') . '</h2>' . $phones . '</div>
    <div><h2>' . t('ct_email') . '</h2><a href="mailto:' . h($email) . '">' . h($email) . '</a></div>
    <div style="border-bottom:1px solid var(--line)"><h2>' . t('ct_social') . '</h2><a href="' . h($ig) . '" rel="noopener">@' . h(trim(parse_url($ig, PHP_URL_PATH) ?: 'uzmancosmetic', '/')) . '</a></div>
  </aside>
  <form class="form-card rv" data-form data-action="' . h($A) . 'api/quote.php" data-lang="' . cur_lang() . '" data-err="' . attr(t('f_err')) . '" data-ok="' . attr(t('f_ok')) . '" data-fail="' . attr(t('f_fail')) . '" novalidate style="--d:.1s">
    <div class="grid-2">
      <div class="field"><label for="f_name">' . t('f_name') . '</label><input id="f_name" name="name" required autocomplete="name" maxlength="120" placeholder="' . attr(t('f_name_ph')) . '"></div>
      <div class="field"><label for="f_company">' . t('f_company') . '</label><input id="f_company" name="company" required autocomplete="organization" maxlength="160" placeholder="' . attr(t('f_company_ph')) . '"></div>
    </div>
    <div class="grid-2">
      <div class="field"><label for="f_email">' . t('f_email') . '</label><input id="f_email" name="email" type="email" required autocomplete="email" maxlength="160" placeholder="name@brand.com" dir="ltr"></div>
      <div class="field"><label for="f_phone">' . t('f_phone') . '</label><input id="f_phone" name="phone" type="tel" required autocomplete="tel" maxlength="40" placeholder="+90 5XX XXX XX XX" dir="ltr"></div>
    </div>
    <div class="field"><label for="f_cat">' . t('f_cat') . '</label><select id="f_cat" name="category">' . $opts . '</select></div>
    <div class="grid-2">
      <div class="field"><label for="f_market">' . t('f_market') . '</label><input id="f_market" name="market" maxlength="120" placeholder="' . attr(t('f_market_ph')) . '"></div>
      <div class="field"><label for="f_qty">' . t('f_qty') . '</label><input id="f_qty" name="qty" maxlength="80" placeholder="' . attr(t('f_qty_ph')) . '"></div>
    </div>
    <div class="field"><label for="f_brief">' . t('f_brief') . '</label><textarea id="f_brief" name="brief" maxlength="4000" placeholder="' . attr(t('f_brief_ph')) . '"></textarea></div>
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
    <label class="consent"><input type="checkbox" name="consent" value="1" required> <span>' . t('f_consent') . '</span></label>
    <button class="btn btn-solid" type="submit"><span>' . t('f_submit') . '</span><i class="arrow"></i></button>
    <p class="form-status" role="status" aria-live="polite"></p>
  </form>
</div></section>';
}

// ---- custom pages (blocks)
function paras(string $body): string
{
    $out = '';
    foreach (preg_split('/\n\s*\n/', trim(str_replace("\r", '', $body))) as $p) {
        if (trim($p) !== '') {
            $out .= '<p>' . nl2br(clean_html($p), false) . '</p>';
        }
    }
    return $out;
}

function block_html(array $b): string
{
    $title = ml($b['title'] ?? []);
    $h2 = $title !== '' ? '<h2 class="rv">' . clean_html($title) . '</h2>' : '';
    switch ($b['type'] ?? '') {
        case 'text':
            return '<section class="section' . (!empty($b['band']) ? ' band' : '') . '"><div class="wrap"><div class="prose rv">' . $h2 . paras(ml($b['body'] ?? [])) . '</div></div></section>';
        case 'steps':
            $rows = '';
            foreach ($b['items'] ?? [] as $i => $it) {
                $rows .= '<div class="step rv"><div class="n">' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) . '</div><div><h3>' . clean_html(ml($it['title'] ?? [])) . '</h3><p>' . clean_html(ml($it['text'] ?? [])) . '</p></div></div>';
            }
            $lead = ml($b['lead'] ?? []);
            if ($title === '' && $lead === '') {
                return '<section class="section"><div class="wrap"><div class="steps wide">' . $rows . '</div></div></section>';
            }
            return '<section class="section"><div class="wrap process-grid"><div class="process-sticky rv">' . $h2 . ($lead !== '' ? '<p class="lead">' . clean_html($lead) . '</p>' : '') . '</div><div class="steps">' . $rows . '</div></div></section>';
        case 'cards':
            $cells = '';
            foreach ($b['items'] ?? [] as $i => $it) {
                $cells .= '<div class="value rv" style="--d:' . number_format($i * .1, 1) . 's"><h3>' . clean_html(ml($it['title'] ?? [])) . '</h3><p>' . clean_html(ml($it['text'] ?? [])) . '</p></div>';
            }
            return '<section class="section band"><div class="wrap">' . ($h2 ? '<div class="sec-head"><div class="rv">' . $h2 . '</div></div>' : '') . '<div class="values">' . $cells . '</div></div></section>';
        case 'checks':
            $li = '';
            foreach ($b['items'] ?? [] as $it) {
                $li .= '<li>' . clean_html(ml($it['text'] ?? [])) . '</li>';
            }
            return '<section class="section"><div class="wrap"><div class="prose rv">' . $h2 . '<ul class="checks">' . $li . '</ul></div></div></section>';
        case 'faq':
            $d = '';
            foreach ($b['items'] ?? [] as $it) {
                $d .= '<details class="rv"><summary>' . clean_html(ml($it['q'] ?? [])) . '</summary><div class="ans">' . paras(ml($it['a'] ?? [])) . '</div></details>';
            }
            return '<section class="section"><div class="wrap"><div class="faq">' . ($h2 ? $h2 : '') . $d . '</div></div></section>';
        case 'image_text':
            $img = !empty($b['image']) ? picture((string)$b['image'], $title) : '';
            return '<section class="section"><div class="wrap split' . (($b['side'] ?? '') === 'left' ? ' flip' : '') . '"><div class="rv prose">' . $h2 . paras(ml($b['body'] ?? [])) . '</div><div class="split-media rv">' . $img . '</div></div></section>';
        case 'certs':
            $docs = rows('SELECT * FROM docs WHERE active = 1 ORDER BY sort, id');
            $cards = '';
            foreach ($docs as $dd) {
                $ti = ml(jd($dd['title']));
                $note = ml(jd($dd['note']));
                $A = asset_prefix();
                if ($dd['image'] !== '') {
                    $href = $A . ($dd['file'] !== '' ? $dd['file'] : $dd['image']);
                    $attrs = $dd['file'] !== '' ? ' target="_blank" rel="noopener"' : ' data-lb data-cap="' . attr($ti) . '"';
                    $cards .= '<a class="doc" href="' . h($href) . '"' . $attrs . '>' . picture($dd['image'], $ti) . '<span class="cap"><b>' . h($ti) . '</b>' . ($note !== '' ? '<small>' . h($note) . '</small>' : '') . '</span></a>';
                } elseif ($dd['file'] !== '') {
                    $cards .= '<a class="doc file" href="' . h($A . $dd['file']) . '" target="_blank" rel="noopener"><span class="cap"><b>' . h($ti) . '</b>' . ($note !== '' ? '<small>' . h($note) . '</small>' : '') . '<small>' . t('download') . ' ↗</small></span></a>';
                }
            }
            $body = $cards !== '' ? '<div class="docs">' . $cards . '</div>' : '<p class="lead">' . t('docs_empty') . '</p>';
            return '<section class="section band"><div class="wrap"><div class="sec-head"><div class="rv">' . $h2 . '</div></div>' . $body . '</div></section>';
    }
    return '';
}

function page_custom(array $p): string
{
    $out = page_hero(clean_html(ml(jd($p['h1'])) ?: ml(jd($p['title']))), clean_html(ml(jd($p['lead']))));
    foreach (jd($p['blocks'], []) as $b) {
        $out .= block_html($b);
    }
    return $out . ((int)$p['cta'] === 1 ? cta_band() : '');
}

function page_404(): string
{
    return '<section class="hero"><div class="wrap" style="text-align:center"><h1 style="margin:28px 0"><em>404</em></h1><p class="lead" style="margin:0 auto 40px">Aradığınız sayfa bulunamadı. · Page not found.</p>
<div class="cta-row" style="justify-content:center"><a href="' . h(base_url()) . '" class="btn btn-solid"><span>Ana sayfa / Home</span><i class="arrow"></i></a></div></div></section>';
}

/** Render a full page. Returns [status, html]. */
function render_page(string $lang, string $slug): array
{
    $GLOBALS['UZ_LANG'] = $lang;
    $page = row('SELECT * FROM pages WHERE slug = ? AND status = 1', [$slug]);
    if (!$page) {
        $GLOBALS['UZ_ABS'] = true;
        $html = head('404', t('title_index'), '', true) . '<div class="site-header scrolled" id="siteHeader"><div class="wrap navbar"><a href="' . h(base_url()) . '" class="brand"><span class="brand-logo" aria-hidden="true"></span><span class="brand-name">UZMAN<span class="brand-sub">COSMETIC</span></span></a></div></div><main id="main">'
            . page_404() . '</main><script src="' . h(base_url()) . 'assets/js/main.js" defer></script></body></html>';
        $GLOBALS['UZ_ABS'] = false;
        return [404, $html];
    }
    $type = $page['type'];
    if ($type === 'custom') {
        $title = ml(jd($page['title'])) . ' — ' . setting('site_name', 'Uzman Cosmetic');
        $desc = ml(jd($page['meta'])) ?: plain(ml(jd($page['lead'])));
        $body = page_custom($page);
    } else {
        [$tk, $dk] = SYS_TITLE_KEY[$slug] ?? ['title_index', 'desc_index'];
        $title = t($tk);
        $desc = t($dk);
        switch ($slug) {
            case 'index': $body = page_index(); break;
            case 'products': $body = page_products(); break;
            case 'body-care':
            case 'home-care': $body = page_category($slug); break;
            case 'private-label': $body = page_private_label(); break;
            case 'about': $body = page_about(); break;
            case 'contact': $body = page_contact(); break;
            default: $body = null;
        }
        if ($body === null) {
            return render_page($lang, '__missing__');
        }
        // admin-editable SEO overrides for system pages
        $m = jd($page['meta']);
        if (ml($m) !== '') {
            $desc = ml($m);
        }
        $tt = jd($page['title']);
        if (ml($tt) !== '') {
            $title = ml($tt);
        }
    }
    return [200, head($slug, $title, $desc, (int)($page['noindex'] ?? 0) === 1, seo_jsonld_tag($slug, $lang, $page, $title, $desc)) . header_html($slug) . $body . footer_html()];
}
