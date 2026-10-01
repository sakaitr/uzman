<?php
/**
 * SEO + GEO (generative-engine optimisation) module: structured data, robots.txt, llms.txt, site audit with score & recommendations.
 * Client-neutral: every value comes from settings; nothing here is specific to one website.
 */
declare(strict_types=1);

const SEO_AI_BOTS = [
    'OAI-SearchBot' => ['OpenAI — ChatGPT arama sonuçları', 'search'],
    'ChatGPT-User' => ['OpenAI — kullanıcı isteğiyle sayfa açar', 'user'],
    'Claude-SearchBot' => ['Anthropic — Claude arama', 'search'],
    'Claude-User' => ['Anthropic — kullanıcı isteğiyle sayfa açar', 'user'],
    'PerplexityBot' => ['Perplexity — arama dizini', 'search'],
    'Perplexity-User' => ['Perplexity — kullanıcı isteğiyle sayfa açar', 'user'],
    'Amazonbot' => ['Amazon — Alexa / Rufus yanıtları', 'search'],
    'GPTBot' => ['OpenAI — model eğitimi', 'train'],
    'ClaudeBot' => ['Anthropic — model eğitimi', 'train'],
    'Google-Extended' => ['Google — Gemini eğitim izni (Google aramasını etkilemez)', 'train'],
    'Applebot-Extended' => ['Apple — yapay zekâ eğitim izni', 'train'],
    'Meta-ExternalAgent' => ['Meta — model eğitimi', 'train'],
    'CCBot' => ['Common Crawl — açık veri seti', 'train'],
    'Bytespider' => ['ByteDance — model eğitimi', 'train'],
];
const SEO_ORG_TYPES = ['Organization' => 'Kuruluş (genel)', 'Corporation' => 'Anonim / limited şirket', 'LocalBusiness' => 'Yerel işletme', 'Store' => 'Mağaza', 'ProfessionalService' => 'Profesyonel hizmet', 'NGO' => 'Dernek / vakıf'];
const LANG_ENGLISH = ['tr' => 'Turkish', 'en' => 'English', 'fr' => 'French', 'ar' => 'Arabic', 'ru' => 'Russian'];

function seo_defaults(): array
{
    return [
        'seo_schema' => '1', 'seo_org_type' => 'Organization', 'seo_org_name' => '', 'seo_legal' => '', 'seo_logo' => '', 'seo_og_image' => 'assets/img/og.jpg', 'seo_founded' => '',
        'seo_street' => '', 'seo_city' => '', 'seo_region' => '', 'seo_postal' => '', 'seo_country' => '', 'seo_lat' => '', 'seo_lng' => '', 'seo_gbp' => '',
        'seo_area' => '', 'seo_knows' => '', 'seo_sameas' => '', 'seo_gsc' => '', 'seo_bing' => '', 'seo_desc' => '{}',
        'seo_llms' => '1', 'seo_llms_full' => '1', 'seo_llms_lang' => 'en', 'seo_llms_intro' => '', 'seo_bots' => '{}',
    ];
}

function seo(string $k)
{
    $d = seo_defaults();
    return setting($k, $d[$k] ?? '');
}

function abs_url(string $path): string
{
    return preg_match('#^https?://#', $path) ? $path : canonical_base() . ltrim($path, '/');
}

function lines(string $s): array
{
    return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $s)), 'strlen'));
}

function seo_bots(): array
{
    $saved = jd(seo('seo_bots'));
    $out = [];
    foreach (SEO_AI_BOTS as $bot => $meta) {
        $out[$bot] = ($saved[$bot] ?? ($bot === 'Bytespider' ? 'block' : 'allow')) === 'block' ? 'block' : 'allow';
    }
    return $out;
}

function page_abs_url(string $lang, string $slug): string
{
    return canonical_base() . ($lang === DEFAULT_LANG ? '' : $lang . '/') . $slug . '.html';
}

// ------------------------------------------------------------------ robots / llms
function robots_txt(): string
{
    $o = "# robots.txt — yönetim panelinden üretilir (SEO & GEO → Ayarlar)\n";
    if (setting('robots_index', '1') !== '1') {
        return $o . "User-agent: *\nDisallow: /\n";
    }
    $o .= "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /app/\nDisallow: /data/\n\n";
    foreach (seo_bots() as $bot => $mode) {
        $o .= "User-agent: $bot\n" . ($mode === 'allow' ? "Allow: /\nDisallow: /admin/\nDisallow: /api/\n" : "Disallow: /\n") . "\n";
    }
    return $o . 'Sitemap: ' . canonical_base() . "sitemap.xml\n";
}

function seo_org_description(string $lang): string
{
    $d = jd(seo('seo_desc'));
    $v = trim((string)($d[$lang] ?? ''));
    if ($v === '') {
        $v = trim((string)($d[DEFAULT_LANG] ?? ''));
    }
    if ($v === '') {
        $GLOBALS['UZ_LANG'] = $lang;
        $v = plain(t('foot_blurb'));
    }
    return $v;
}

function public_pages(): array
{
    return rows('SELECT * FROM pages WHERE status = 1 AND noindex = 0 ORDER BY sort, id');
}

function page_text_title(array $p, string $lang): string
{
    $GLOBALS['UZ_LANG'] = $lang;
    if ($p['type'] === 'custom') {
        return ml(jd($p['title']), $lang);
    }
    $tt = ml(jd($p['title']), $lang);
    if ($tt !== '') {
        return $tt;
    }
    return plain(t(SYS_TITLE_KEY[$p['slug']][0] ?? 'title_index'));
}

function llms_txt(): string
{
    $lang = in_array(seo('seo_llms_lang'), LANGS, true) ? seo('seo_llms_lang') : 'en';
    $GLOBALS['UZ_LANG'] = $lang;
    $name = seo('seo_org_name') ?: setting('site_name', 'Site');
    $intro = trim((string)seo('seo_llms_intro')) ?: seo_org_description($lang);
    $o = "# $name\n\n> " . $intro . "\n\n";
    $facts = [];
    foreach ([['Founded', seo('seo_founded')], ['Location', trim(implode(', ', array_filter([seo('seo_city'), seo('seo_region'), seo('seo_country')])))], ['Email', setting('email')], ['Phone', setting('phone1')], ['Languages', implode(', ', array_map(function ($l) { return LANG_ENGLISH[$l]; }, LANGS))]] as [$k, $v]) {
        if (trim((string)$v) !== '') {
            $facts[] = "- $k: $v";
        }
    }
    if ($facts) {
        $o .= implode("\n", $facts) . "\n\n";
    }
    $o .= "## Pages\n\n";
    foreach (public_pages() as $p) {
        $t = page_text_title($p, $lang);
        $d = $p['type'] === 'custom' ? plain(ml(jd($p['meta']), $lang)) : plain(ml(jd($p['meta']), $lang) ?: t(SYS_TITLE_KEY[$p['slug']][1] ?? 'desc_index'));
        $o .= '- [' . $t . '](' . page_abs_url($lang, $p['slug']) . ')' . ($d !== '' ? ': ' . $d : '') . "\n";
    }
    $cats = rows('SELECT * FROM categories ORDER BY sort');
    if ($cats) {
        $o .= "\n## Products\n\n";
        foreach ($cats as $c) {
            $subs = rows('SELECT * FROM subcats WHERE cat_id = ? AND parent_id IS NULL ORDER BY sort', [$c['id']]);
            $o .= '- [' . ml(jd($c['name']), $lang) . '](' . page_abs_url($lang, $c['page']) . '): ' . implode(', ', array_map(function ($s) use ($lang) { return ml(jd($s['name']), $lang); }, $subs)) . "\n";
        }
    }
    $o .= "\n## Other languages\n\n";
    foreach (LANGS as $l) {
        if ($l !== $lang) {
            $o .= '- [' . LANG_ENGLISH[$l] . '](' . page_abs_url($l, 'index') . ")\n";
        }
    }
    if (seo('seo_llms_full') === '1') {
        $o .= "\n## Optional\n\n- [Full site text](" . canonical_base() . "llms-full.txt)\n";
    }
    return $o;
}

function html_to_md(string $html): string
{
    if (preg_match('#<main[^>]*>(.*)</main>#s', $html, $m)) {
        $html = $m[1];
    }
    $html = preg_replace('#<(script|style|template|svg|video|nav)\b.*?</\1>#is', '', $html);
    $html = preg_replace_callback('#<h([1-4])[^>]*>(.*?)</h\1>#is', function ($m) { return "\n\n" . str_repeat('#', (int)$m[1]) . ' ' . trim(strip_tags($m[2])) . "\n\n"; }, $html);
    $html = preg_replace('#<li[^>]*>#i', "\n- ", $html);
    $html = preg_replace('#<(br|/p|/div|/section|/summary|/tr)[^>]*>#i', "\n", $html);
    $t = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $t = preg_replace("/[ \t]+/", ' ', $t);
    $t = preg_replace("/\n[ ]+/", "\n", $t);
    return trim(preg_replace("/\n{3,}/", "\n\n", $t));
}

function llms_full_txt(): string
{
    $lang = in_array(seo('seo_llms_lang'), LANGS, true) ? seo('seo_llms_lang') : 'en';
    $o = '# ' . (seo('seo_org_name') ?: setting('site_name', 'Site')) . " — full text ($lang)\n\n";
    foreach (public_pages() as $p) {
        [$st, $html] = render_page($lang, $p['slug']);
        if ($st === 200) {
            $o .= "---\n\nSource: " . page_abs_url($lang, $p['slug']) . "\n\n" . html_to_md($html) . "\n\n";
        }
    }
    return $o;
}

// ------------------------------------------------------------------ structured data
function ld_org(string $lang): array
{
    $name = seo('seo_org_name') ?: setting('site_name', 'Site');
    $o = ['@type' => in_array(seo('seo_org_type'), array_keys(SEO_ORG_TYPES), true) ? seo('seo_org_type') : 'Organization', '@id' => canonical_base() . '#organization', 'name' => $name, 'url' => canonical_base()];
    if (trim((string)seo('seo_legal')) !== '' || trim((string)setting('company_legal')) !== '') {
        $o['legalName'] = seo('seo_legal') ?: setting('company_legal');
    }
    $logo = seo('seo_logo');
    if ($logo === '' || $logo === 'assets/img/apple-touch-icon.png') {
        $logo = brand_path('brand_apple') ?: $logo;
    }
    if ($logo !== '' && is_file(UZ_ROOT . '/' . $logo)) {
        [$w, $h] = img_size($logo);
        $o['logo'] = ['@type' => 'ImageObject', 'url' => abs_url($logo)] + ($w ? ['width' => $w, 'height' => $h] : []);
        $o['image'] = abs_url(seo('seo_og_image') ?: $logo);
    }
    $d = seo_org_description($lang);
    if ($d !== '') {
        $o['description'] = $d;
    }
    if (trim((string)seo('seo_founded')) !== '') {
        $o['foundingDate'] = trim((string)seo('seo_founded'));
    }
    $addr = array_filter(['@type' => 'PostalAddress', 'streetAddress' => seo('seo_street'), 'addressLocality' => seo('seo_city'), 'addressRegion' => seo('seo_region'), 'postalCode' => seo('seo_postal'), 'addressCountry' => seo('seo_country')], function ($v) { return $v !== ''; });
    if (count($addr) > 1) {
        $o['address'] = $addr;
    }
    if (is_numeric(seo('seo_lat')) && is_numeric(seo('seo_lng'))) {
        $o['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)seo('seo_lat'), 'longitude' => (float)seo('seo_lng')];
    }
    if (setting('phone1') !== '') {
        $o['telephone'] = setting('phone1');
    }
    if (setting('email') !== '') {
        $o['email'] = setting('email');
    }
    if ($o['telephone'] ?? $o['email'] ?? false) {
        $cp = ['@type' => 'ContactPoint', 'contactType' => 'sales', 'availableLanguage' => array_values(array_map(function ($l) { return LANG_ENGLISH[$l]; }, LANGS))];
        if (!empty($o['telephone'])) {
            $cp['telephone'] = $o['telephone'];
        }
        if (!empty($o['email'])) {
            $cp['email'] = $o['email'];
        }
        $o['contactPoint'] = [$cp];
    }
    $same = lines((string)seo('seo_sameas'));
    if (setting('instagram') !== '') {
        array_unshift($same, setting('instagram'));
    }
    if (seo('seo_gbp') !== '') {
        $same[] = seo('seo_gbp');
    }
    $same = array_values(array_unique(array_filter($same)));
    if ($same) {
        $o['sameAs'] = $same;
    }
    if (lines((string)seo('seo_area'))) {
        $o['areaServed'] = array_map(function ($c) { return ['@type' => 'Country', 'name' => $c]; }, lines((string)seo('seo_area')));
    }
    if (lines((string)seo('seo_knows'))) {
        $o['knowsAbout'] = lines((string)seo('seo_knows'));
    }
    return $o;
}

function faq_items_of(array $page): array
{
    $out = [];
    foreach (jd($page['blocks'] ?? '[]', []) as $b) {
        if (($b['type'] ?? '') === 'faq') {
            foreach ($b['items'] ?? [] as $it) {
                $q = plain(ml($it['q'] ?? []));
                $a = trim(preg_replace('/\s+/', ' ', plain(ml($it['a'] ?? []))));
                if ($q !== '' && $a !== '') {
                    $out[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
                }
            }
        }
    }
    return array_slice($out, 0, 40);
}

function seo_graph(string $slug, string $lang, array $page, string $title, string $desc): array
{
    $base = canonical_base();
    $url = page_abs_url($lang, $slug);
    $graph = [ld_org($lang)];
    $graph[] = ['@type' => 'WebSite', '@id' => $base . '#website', 'url' => $base, 'name' => seo('seo_org_name') ?: setting('site_name', ''), 'inLanguage' => $lang, 'publisher' => ['@id' => $base . '#organization']];
    $type = ['about' => 'AboutPage', 'contact' => 'ContactPage', 'products' => 'CollectionPage', 'body-care' => 'CollectionPage', 'home-care' => 'CollectionPage'][$slug] ?? 'WebPage';
    $faq = faq_items_of($page);
    $wp = ['@type' => $faq ? ['WebPage', 'FAQPage'] : $type, '@id' => $url . '#webpage', 'url' => $url, 'name' => plain($title), 'description' => plain($desc), 'inLanguage' => $lang, 'isPartOf' => ['@id' => $base . '#website'], 'about' => ['@id' => $base . '#organization']];
    if (!empty($page['updated_at'])) {
        $wp['dateModified'] = date('c', (int)strtotime($page['updated_at']));
    }
    if ($faq) {
        $wp['mainEntity'] = $faq;
    }
    if ($slug !== 'index') {
        $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => plain(t('aria_home')) ?: 'Home', 'item' => page_abs_url($lang, 'index')]];
        if (in_array($slug, ['body-care', 'home-care'], true)) {
            $crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => t('nav_products'), 'item' => page_abs_url($lang, 'products')];
        }
        $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => plain($title), 'item' => $url];
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $crumbs];
        $wp['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
    }
    $graph[] = $wp;
    if (in_array($slug, ['body-care', 'home-care'], true)) {
        $items = [];
        foreach (catalog() as $c) {
            if ($c['page'] === $slug) {
                foreach ($c['subs'] as $i => $s) {
                    $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => ml(jd($s['name'])), 'url' => $url . '#' . $s['slug']];
                }
            }
        }
        if ($items) {
            $graph[] = ['@type' => 'ItemList', 'itemListElement' => $items];
        }
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

function seo_jsonld_tag(string $slug, string $lang, array $page, string $title, string $desc): string
{
    if (seo('seo_schema') !== '1') {
        return '';
    }
    return '<script type="application/ld+json">' . json_encode(seo_graph($slug, $lang, $page, $title, $desc), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
}

// ------------------------------------------------------------------ audit
function audit_facts(string $html, int $status): array
{
    $f = ['status' => $status, 'bytes' => strlen($html)];
    $f['title'] = preg_match('#<title>(.*?)</title>#s', $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : '';
    $f['desc'] = preg_match('#<meta name="description" content="(.*?)">#s', $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : '';
    $f['h1'] = preg_match_all('#<h1[\s>]#', $html);
    $f['h2'] = preg_match_all('#<h2[\s>]#', $html);
    $f['qheads'] = preg_match_all('#<h[2-3][^>]*>[^<]*\?[^<]*</h[2-3]>|<summary>#', $html);
    $main = preg_match('#<main[^>]*>(.*)</main>#s', $html, $m) ? $m[1] : $html;
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#<(script|style|template|svg)\b.*?</\1>#is', ' ', $main)), ENT_QUOTES, 'UTF-8')));
    $f['text'] = $text;
    $f['words'] = $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    $f['numbers'] = preg_match_all('/\d[\d.,]*/', $text);
    $f['lists'] = preg_match_all('#<(ul|ol|table|dl)[\s>]#', $main);
    preg_match_all('#<img\b[^>]*>#', $main, $im);
    $f['imgs'] = count($im[0]);
    $f['img_noalt'] = $f['img_emptyalt'] = $f['img_nodim'] = 0;
    foreach ($im[0] as $tag) {
        if (!preg_match('#\salt="#', $tag)) {
            $f['img_noalt']++;
        } elseif (preg_match('#\salt=""#', $tag)) {
            $f['img_emptyalt']++;
        }
        if (!preg_match('#\swidth="#', $tag) || !preg_match('#\sheight="#', $tag)) {
            $f['img_nodim']++;
        }
    }
    preg_match_all('#<a\s[^>]*href="([^"]*)"#', $main, $lm);
    $f['links'] = count(array_filter($lm[1], function ($h) { return $h !== '' && !preg_match('#^(https?:|mailto:|tel:|\#|javascript:)#', $h); }));
    $f['canonical'] = (bool)preg_match('#rel="canonical"#', $html);
    $f['hreflang'] = preg_match_all('#rel="alternate" hreflang="#', $html);
    $f['og_image'] = (bool)preg_match('#property="og:image"#', $html);
    $f['viewport'] = (bool)preg_match('#name="viewport"#', $html);
    $f['lang'] = (bool)preg_match('#<html lang="#', $html);
    $f['noindex'] = (bool)preg_match('#name="robots" content="noindex#', $html);
    $types = [];
    if (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $jm)) {
        $walk = function ($n) use (&$walk, &$types) {
            if (is_array($n)) {
                if (isset($n['@type'])) {
                    foreach ((array)$n['@type'] as $t) {
                        $types[] = $t;
                    }
                }
                foreach ($n as $v) {
                    if (is_array($v)) {
                        $walk($v);
                    }
                }
            }
        };
        foreach ($jm[1] as $j) {
            $walk(json_decode($j, true));
        }
    }
    $f['schema'] = array_values(array_unique(array_diff($types, ['ImageObject', 'PostalAddress', 'ContactPoint', 'Question', 'Answer', 'ListItem', 'Country', 'GeoCoordinates'])));
    $f['jsonld'] = (bool)preg_match('#application/ld\+json#', $html);
    return $f;
}

function seo_collect(): array
{
    $out = [];
    foreach (rows('SELECT * FROM pages WHERE status = 1 ORDER BY sort, id') as $p) {
        foreach (LANGS as $l) {
            [$st, $html] = render_page($l, $p['slug']);
            $out[$p['slug']][$l] = audit_facts($html, $st) + ['noindex_flag' => (int)$p['noindex'], 'type' => $p['type'], 'updated' => $p['updated_at']];
        }
    }
    return $out;
}

function ratio_status(float $r): string
{
    return $r >= 0.95 ? 'pass' : ($r >= 0.6 ? 'warn' : 'fail');
}

function audit_run(): array
{
    $pages = seo_collect();
    $checks = [];
    $add = function (string $id, string $cat, string $title, int $weight, float $ratio, string $msg, string $fix, ?array $link = null, array $details = []) use (&$checks) {
        $ratio = max(0.0, min(1.0, $ratio));
        $checks[] = ['id' => $id, 'cat' => $cat, 'title' => $title, 'weight' => $weight, 'ratio' => $ratio, 'status' => ratio_status($ratio), 'msg' => $msg, 'fix' => $fix, 'link' => $link, 'details' => array_slice($details, 0, 12)];
    };
    $share = function (callable $ok, ?array $filter = null) use ($pages) {
        $tot = $good = 0;
        $bad = [];
        foreach ($pages as $slug => $langs) {
            foreach ($langs as $l => $f) {
                if ($f['noindex_flag'] || ($filter && !in_array($slug, $filter, true))) {
                    continue;
                }
                $tot++;
                if ($ok($f, $slug, $l)) {
                    $good++;
                } else {
                    $bad[] = "$slug ($l)";
                }
            }
        }
        return [$tot ? $good / $tot : 1.0, $bad];
    };
    $n = function ($arr) { return count($arr); };
    $siteUrl = trim((string)setting('site_url', ''));
    $effective = $siteUrl !== '' ? $siteUrl : base_url();
    $isHttps = strpos($effective, 'https://') === 0;
    $indexable = setting('robots_index', '1') === '1';

    // ---- Teknik SEO
    $add('https', 'tech', 'HTTPS ve kalıcı site adresi', 8, ($isHttps ? 0.7 : 0) + ($siteUrl !== '' ? 0.3 : 0), $isHttps && $siteUrl !== '' ? 'Site adresi tanımlı ve HTTPS.' : ($siteUrl === '' ? 'Site adresi (canonical) tanımlanmamış; o an açılan adres kullanılıyor.' : 'Site adresi HTTPS değil.'), 'Ayarlar → SEO → "Site adresi" alanına https://alanadi.com yazın; sunucuda SSL sertifikasının (cPanel → SSL/TLS Status) açık olduğundan emin olun.', ['settings']);
    $add('indexable', 'tech', 'Arama motorlarına açık', 10, $indexable ? 1 : 0, $indexable ? 'Site dizinlemeye açık.' : 'Site dizinlemeye KAPALI (robots: Disallow /). Test ortamında doğru, canlıda hata.', 'Canlıya alırken Ayarlar → SEO → "Arama motorlarının siteyi dizinlemesine izin ver" kutusunu işaretleyin.', ['settings']);
    [$r, $bad] = $share(function ($f) { return $f['canonical'] && $f['hreflang'] >= 2; });
    $add('canon', 'tech', 'Canonical ve hreflang etiketleri', 6, $r, $r >= 0.95 ? 'Tüm sayfalarda canonical + hreflang var.' : count($bad) . ' sayfada eksik.', 'Çok dilli sayfalarda her dil için hreflang ve tek bir canonical bulunmalı (sistem otomatik üretir; özel şablon kullanıldıysa kontrol edin).', null, $bad);
    [$r, $bad] = $share(function ($f) { $l = mb_strlen($f['title']); return $l >= 25 && $l <= 62; });
    $add('title_len', 'tech', 'Sayfa başlığı uzunluğu (25–62 karakter)', 8, $r, $r >= 0.95 ? 'Başlık uzunlukları ideal.' : count($bad) . ' sayfa/dil kombinasyonunda başlık çok kısa ya da uzun.', 'Sayfalar → ilgili sayfa → SEO bölümünde "Sayfa başlığı" alanını 25–62 karakter aralığında, ana anahtar kelimeyi başa alarak yazın.', ['pages'], $bad);
    [$r, $bad] = $share(function ($f) { $l = mb_strlen($f['desc']); return $l >= 70 && $l <= 165; });
    $add('desc_len', 'tech', 'Meta açıklama uzunluğu (70–165 karakter)', 8, $r, $r >= 0.95 ? 'Açıklamalar ideal uzunlukta.' : count($bad) . ' sayfa/dil kombinasyonunda meta açıklama eksik, çok kısa ya da uzun.', 'Sayfalar → ilgili sayfa → "Meta açıklama" alanına, sayfayı özetleyen ve eylem çağrısı içeren 120–155 karakterlik bir metin yazın. Her dil için ayrı.', ['pages'], $bad);
    foreach (['title' => ['Benzersiz sayfa başlıkları', 6], 'desc' => ['Benzersiz meta açıklamalar', 5]] as $key => [$label, $w]) {
        $seen = [];
        $dups = [];
        foreach ($pages as $slug => $langs) {
            foreach ($langs as $l => $f) {
                if ($f['noindex_flag'] || $f[$key] === '') {
                    continue;
                }
                $k = $l . '|' . $f[$key];
                if (isset($seen[$k])) {
                    $dups[] = "$slug = {$seen[$k]} ($l)";
                } else {
                    $seen[$k] = $slug;
                }
            }
        }
        $add($key . '_uniq', 'tech', $label, $w, $dups ? 0.3 : 1, $dups ? count($dups) . ' sayfa aynı ' . ($key === 'title' ? 'başlığı' : 'açıklamayı') . ' kullanıyor.' : 'Tekrar yok.', 'Her sayfanın başlığı/açıklaması kendine özgü olmalı; yinelenenleri sayfa düzenleme ekranından değiştirin.', ['pages'], $dups);
    }
    [$r, $bad] = $share(function ($f) { return $f['h1'] === 1; });
    $add('h1', 'tech', 'Sayfa başına tek H1', 7, $r, $r >= 0.95 ? 'Her sayfada tam bir H1 var.' : count($bad) . ' sayfada H1 yok ya da birden fazla.', 'Her sayfa için tek bir ana başlık (H1) kullanın; özel sayfalarda "Ana başlık" alanını doldurun.', ['pages'], $bad);
    $tot = $noalt = $nodim = $empty = 0;
    foreach ($pages as $langs) {
        foreach ($langs as $f) {
            $tot += $f['imgs'];
            $noalt += $f['img_noalt'];
            $nodim += $f['img_nodim'];
            $empty += $f['img_emptyalt'];
        }
    }
    $add('img_alt', 'tech', 'Görsellerde alt metin', 6, $tot ? 1 - (($noalt + $empty * 0.5) / $tot) : 1, $noalt + $empty === 0 ? 'Tüm görsellerde açıklayıcı alt metin var.' : "$noalt görselde alt özelliği yok, $empty görselde boş (dekoratif).", 'Ürün ve hero görsellerine anlamlı alt metin verin. Ürünlerde başlık alanı alt metin olarak kullanılır; başlıkları doldurun.', ['catalog']);
    $add('img_dim', 'tech', 'Görsel boyutları (CLS)', 3, $tot ? 1 - $nodim / $tot : 1, $nodim === 0 ? 'Tüm görsellerde genişlik/yükseklik tanımlı.' : "$nodim görselde boyut yok (yerleşim kayması riski).", 'Görselleri panelden yükleyin; sistem boyutları otomatik ekler.');
    $ogf = seo('seo_og_image');
    $ogOk = $ogf !== '' && is_file(UZ_ROOT . '/' . $ogf);
    [$ow, $oh] = $ogOk ? img_size($ogf) : [0, 0];
    $add('og', 'tech', 'Sosyal paylaşım görseli (Open Graph)', 4, !$ogOk ? 0 : ($ow >= 1200 && $oh >= 630 ? 1 : 0.6), !$ogOk ? 'Paylaşım görseli bulunamadı.' : ($ow >= 1200 && $oh >= 630 ? "Paylaşım görseli {$ow}×{$oh}." : "Paylaşım görseli {$ow}×{$oh}; önerilen en az 1200×630."), 'SEO & GEO → Ayarlar → "Paylaşım görseli" alanına 1200×630 piksel (veya daha büyük) bir görsel seçin.', ['seo_settings']);
    $add('verify', 'tech', 'Search Console / Bing doğrulaması', 3, (seo('seo_gsc') !== '' ? 0.6 : 0) + (seo('seo_bing') !== '' ? 0.4 : 0), seo('seo_gsc') !== '' ? 'Google Search Console doğrulama etiketi var.' : 'Doğrulama etiketi yok; arama performansını ölçemezsiniz.', 'Google Search Console\'da siteyi ekleyin, "HTML etiketi" doğrulama kodunu SEO & GEO → Ayarlar bölümüne yapıştırın; sitemap.xml adresini gönderin.', ['seo_settings']);
    [$r, $bad] = $share(function ($f) { return $f['bytes'] < 160000; });
    $add('weight', 'tech', 'Sayfa HTML boyutu', 2, $r, $r >= 0.95 ? 'HTML boyutları makul.' : count($bad) . ' sayfa 160 KB üzerinde.', 'Çok uzun sayfaları bölün; çok sayıda ürün görseli için sayfalamayı düşünün.', null, $bad);
    $nf = render_page('tr', '__yok__')[0];
    $add('404', 'tech', 'Olmayan sayfalar 404 döner', 2, $nf === 404 ? 1 : 0, $nf === 404 ? 'Doğru 404 yanıtı.' : 'Olmayan sayfa 404 dönmüyor.', 'Sunucu yapılandırmasını (.htaccess) kontrol edin.');

    // ---- İçerik
    [$r, $bad] = $share(function ($f) { return $f['words'] >= 250; }, ['index', 'private-label', 'about', 'process', 'facility', 'quality', 'faq']);
    $add('words', 'content', 'Yeterli metin (≥ 250 kelime, ana sayfalar)', 6, $r, $r >= 0.95 ? 'Ana sayfalarda yeterli içerik var.' : count($bad) . ' sayfa/dil "ince içerik" sınırının altında.', 'Arama motorları ve yapay zekâ cevap motorları kısa sayfaları az güvenilir bulur. Sayfalara sorulara cevap veren, somut bilgi içeren paragraflar ekleyin.', ['pages'], $bad);
    [$r, $bad] = $share(function ($f) { return $f['h2'] >= 2; }, ['index', 'private-label', 'about', 'process', 'facility', 'quality', 'faq']);
    $add('h2', 'content', 'Başlık hiyerarşisi (H2 alt başlıkları)', 4, $r, $r >= 0.95 ? 'Sayfalar alt başlıklarla bölünmüş.' : count($bad) . ' sayfada 2\'den az alt başlık var.', 'İçeriği net H2 başlıklarıyla bölün; her başlık tek bir konuyu anlatsın.', ['pages'], $bad);
    [$r, $bad] = $share(function ($f) { return $f['links'] >= 3; });
    $add('links', 'content', 'İç bağlantılar', 3, $r, $r >= 0.95 ? 'Sayfalar birbirine bağlı.' : count($bad) . ' sayfada 3\'ten az iç bağlantı var.', 'İlgili sayfalara (ürünler, süreç, iletişim) metin içinden bağlantı verin.', null, $bad);
    $tr = (int)val("SELECT COUNT(DISTINCT k) FROM strings WHERE lang = 'tr' AND TRIM(v) <> ''");
    $missing = [];
    foreach (LANGS as $l) {
        if ($l !== 'tr') {
            $c = (int)val("SELECT COUNT(*) FROM strings WHERE lang = ? AND TRIM(v) <> ''", [$l]);
            if ($c < $tr) {
                $missing[] = strtoupper($l) . ': ' . ($tr - $c) . ' eksik';
            }
        }
    }
    $add('i18n', 'content', 'Çeviri tamlığı', 7, $missing ? 0.5 : 1, $missing ? 'Boş çeviriler Türkçe gösteriliyor: ' . implode(', ', $missing) : 'Tüm diller tam.', 'Site metinleri bölümünde boş (kırmızı noktalı) dil sekmelerini doldurun. Yarım çeviri, hreflang\'ı olan sayfalarda yinelenen içerik sayılır.', ['strings'], $missing);
    $old = (int)val("SELECT COUNT(*) FROM pages WHERE status = 1 AND updated_at < ?", [date('Y-m-d H:i:s', strtotime('-12 months'))]);
    $add('fresh', 'content', 'İçerik güncelliği (12 ay)', 3, $old ? 0.5 : 1, $old ? "$old sayfa 12 aydan uzun süredir güncellenmedi." : 'Sayfalar güncel.', 'Yılda en az bir kez sayfaları gözden geçirin; yapay zekâ motorları güncel kaynakları öne çıkarır.', ['pages']);

    // ---- GEO / yapay zekâ
    $org = ld_org(DEFAULT_LANG);
    $orgScore = 0;
    $orgMiss = [];
    foreach ([
        'logo' => isset($org['logo']), 'description' => mb_strlen((string)($org['description'] ?? '')) >= 60, 'foundingDate' => isset($org['foundingDate']), 'address' => isset($org['address']['streetAddress'], $org['address']['addressLocality'], $org['address']['addressCountry']),
        'iletişim' => isset($org['telephone']) || isset($org['email']), 'sameAs (≥2 profil)' => count($org['sameAs'] ?? []) >= 2, 'knowsAbout (≥3 konu)' => count($org['knowsAbout'] ?? []) >= 3, 'areaServed' => !empty($org['areaServed']),
    ] as $k => $ok) {
        $ok ? $orgScore++ : $orgMiss[] = $k;
    }
    $add('org', 'geo', 'Kuruluş (Organization) yapısal verisi', 10, (seo('seo_schema') === '1' ? $orgScore / 8 : 0), seo('seo_schema') !== '1' ? 'Yapısal veri kapalı.' : ($orgMiss ? 'Eksik alanlar: ' . implode(', ', $orgMiss) : 'Tüm temel alanlar dolu.'), 'SEO & GEO → Ayarlar → Kuruluş bölümünü doldurun. Yapay zekâ motorları markanızı bu veriden "varlık" olarak tanır ve alıntılar.', ['seo_settings'], $orgMiss);
    $faqPages = 0;
    $faqCount = 0;
    foreach (rows("SELECT * FROM pages WHERE status = 1 AND type = 'custom'") as $p) {
        $c = count(faq_items_of($p));
        $faqCount += $c;
        $faqPages += $c ? 1 : 0;
    }
    $add('faq', 'geo', 'SSS içeriği ve FAQPage şeması', 8, $faqCount >= 8 ? 1 : ($faqCount >= 1 ? 0.65 : 0), $faqCount ? "$faqCount soru-cevap yayında (FAQPage şeması otomatik)." : 'SSS yok.', 'Yapay zekâ cevap motorları soru-cevap biçimli içeriği en çok alıntılar. Sayfalar → SSS sayfasına müşterilerin gerçekten sorduğu en az 8–10 soruyu net cevaplarla ekleyin (fiyat/MOQ/teslim süresi gibi).', ['pages'], []);
    $llmsOn = seo('seo_llms') === '1';
    $add('llms', 'geo', 'llms.txt (yapay zekâ site özeti)', 6, $llmsOn ? (seo('seo_llms_full') === '1' ? 1 : 0.8) : 0, $llmsOn ? '/llms.txt yayında.' : 'llms.txt kapalı.', 'SEO & GEO → Ayarlar → Yapay zekâ bölümünden llms.txt\'i açın; büyük dil modellerine sitenizin yapılandırılmış özetini sunar.', ['seo_settings']);
    $bots = seo_bots();
    $allowShare = count(array_filter($bots, function ($m) { return $m === 'allow'; })) / count($bots);
    $retrieval = array_filter(SEO_AI_BOTS, function ($m) { return $m[1] !== 'train'; });
    $retAllowed = 0;
    foreach ($retrieval as $b => $_) {
        $retAllowed += $bots[$b] === 'allow' ? 1 : 0;
    }
    $r = !$indexable ? 0 : ($retAllowed / max(1, count($retrieval))) * 0.8 + $allowShare * 0.2;
    $add('bots', 'geo', 'Yapay zekâ tarayıcılarına izin', 8, $r, !$indexable ? 'Site tümüyle engelli.' : ($retAllowed === count($retrieval) ? 'Arama/cevap botları (ChatGPT, Claude, Perplexity…) erişebiliyor.' : 'Bazı yapay zekâ arama botları engelli.'), 'SEO & GEO → Ayarlar → Yapay zekâ botları: arama ve kullanıcı-isteği botlarına izin verin (görünür olmak için). Model eğitimi botlarını (GPTBot, CCBot…) isteğe göre kapatabilirsiniz.', ['seo_settings']);
    $home = $pages['index'][DEFAULT_LANG] ?? ['text' => ''];
    $about = $pages['about'][DEFAULT_LANG] ?? ['text' => ''];
    $txt = mb_strtolower($home['text'] . ' ' . $about['text']);
    $ent = 0;
    $entMiss = [];
    foreach (['kuruluş yılı' => seo('seo_founded'), 'şehir' => seo('seo_city'), 'ülke/ihracat' => 'ihracat'] as $label => $needle) {
        if ($needle !== '' && mb_strpos($txt, mb_strtolower((string)$needle)) !== false) {
            $ent++;
        } else {
            $entMiss[] = $label;
        }
    }
    $add('entity', 'geo', 'Kim olduğunuz net anlatılıyor (varlık netliği)', 5, $ent / 3, $entMiss ? 'Ana sayfa/Hakkımızda metinlerinde geçmeyen: ' . implode(', ', $entMiss) : 'Kuruluş yılı, konum ve faaliyet metinlerde geçiyor.', 'Hakkımızda ve ana sayfada "X yılında Y\'de kurulan, Z ülkeye ihracat yapan ... üreticisi" gibi tek cümlelik net bir tanım bulunsun; yapay zekâ bu cümleyi aynen alıntılar.', ['strings']);
    $nums = ($home['numbers'] ?? 0) + ($about['numbers'] ?? 0);
    $add('facts', 'geo', 'Alıntılanabilir somut veriler', 4, min(1, $nums / 6), $nums >= 6 ? 'Rakamsal olgular var (yıl, ülke sayısı, kapasite vb.).' : 'Metinlerde somut rakam az.', 'Kapasite, ihracat ülke sayısı, kuruluş yılı, ürün sayısı gibi doğrulanabilir rakamları metne ekleyin; cevap motorları bunları öne çıkarır.', ['strings']);
    $listy = 0;
    $tp = 0;
    foreach (['index', 'private-label', 'about', 'process', 'facility', 'quality', 'faq'] as $s) {
        if (isset($pages[$s][DEFAULT_LANG])) {
            $tp++;
            $listy += $pages[$s][DEFAULT_LANG]['lists'] >= 1 || $pages[$s][DEFAULT_LANG]['qheads'] >= 1 ? 1 : 0;
        }
    }
    $add('struct', 'geo', 'Yapılandırılmış içerik (liste, soru başlığı)', 3, $tp ? $listy / $tp : 0, $tp && $listy === $tp ? 'Sayfalarda liste/soru yapısı var.' : 'Bazı sayfalarda liste ya da soru biçimli başlık yok.', 'Süreç, madde işaretli liste ve "…nedir? / nasıl?" biçimli başlıklar yapay zekâ özetlerine girme şansını artırır.', ['pages']);
    $docs = (int)val('SELECT COUNT(*) FROM docs WHERE active = 1');
    $add('trust', 'geo', 'Güven kanıtları (sertifika / belge)', 4, $docs >= 2 ? 1 : ($docs === 1 ? 0.7 : 0), $docs ? "$docs belge yayında." : 'Yayında belge/sertifika yok.', 'Belgeler menüsünden güncel sertifikalarınızı (ISO, GMP, vb.) ekleyin; B2B alıcılar ve cevap motorları güvenilirlik sinyali olarak kullanır.', ['docs']);
    $langsOk = 0;
    foreach (LANGS as $l) {
        $langsOk += isset($pages['index'][$l]) && $pages['index'][$l]['hreflang'] >= 2 ? 1 : 0;
    }
    $add('multi', 'geo', 'Çok dilli kapsama', 4, min(1, $langsOk / 3), "$langsOk dil hreflang ile yayında.", 'En az 3 dilde tam içerik ve hreflang önerilir (mevcut yapı bunu sağlar).');
    $add('nap', 'geo', 'Tutarlı iletişim bilgisi (ad-adres-telefon)', 3, (setting('phone1') !== '' ? 0.34 : 0) + (setting('email') !== '' ? 0.33 : 0) + (seo('seo_street') !== '' ? 0.33 : 0), setting('phone1') !== '' && setting('email') !== '' && seo('seo_street') !== '' ? 'Telefon, e-posta ve adres tanımlı.' : 'Telefon/e-posta/adres eksik.', 'Ayarlar → Firma ve iletişim ile SEO & GEO → Adres bölümünü eksiksiz doldurun; aynı bilgi sitede ve yapısal verilerde otomatik kullanılır.', ['settings']);

    // ---- Coğrafi / yerel
    $a = $org['address'] ?? [];
    $filled = count(array_filter([$a['streetAddress'] ?? '', $a['addressLocality'] ?? '', $a['addressRegion'] ?? '', $a['postalCode'] ?? '', $a['addressCountry'] ?? '']));
    $add('addr', 'local', 'Eksiksiz adres (yapısal)', 6, $filled / 5, $filled === 5 ? 'Adres alanları tam.' : (5 - $filled) . ' adres alanı boş.', 'SEO & GEO → Ayarlar → Adres ve konum: sokak, şehir, bölge, posta kodu ve ülke kodunu (TR) girin.', ['seo_settings']);
    $add('geo_xy', 'local', 'Harita koordinatları', 4, isset($org['geo']) ? 1 : 0, isset($org['geo']) ? 'Enlem/boylam tanımlı.' : 'Koordinat girilmemiş.', 'Google Haritalar\'da konumunuza sağ tıklayıp enlem/boylamı kopyalayın ve Ayarlar → Adres ve konum alanına yapıştırın.', ['seo_settings']);
    $add('area', 'local', 'Hizmet verilen bölgeler (areaServed)', 5, !empty($org['areaServed']) ? 1 : 0, !empty($org['areaServed']) ? count($org['areaServed']) . ' bölge/ülke tanımlı.' : 'Hizmet verilen ülke/bölgeler tanımlı değil.', 'İhracat yaptığınız ülkeleri (en az başlıca pazarlar) virgülle girin; "Türkiye\'den X\'e ihracat" aramalarında kuruluşunuz eşleşir.', ['seo_settings']);
    $add('gbp', 'local', 'Google İşletme Profili bağlantısı', 3, seo('seo_gbp') !== '' ? 1 : 0, seo('seo_gbp') !== '' ? 'Profil bağlantısı var.' : 'Google İşletme Profili bağlantısı yok.', 'Google İşletme Profili (Maps) oluşturun/talep edin, bağlantıyı Ayarlar → Adres ve konum alanına ekleyin; yerel arama ve harita sonuçlarında görünürlük sağlar.', ['seo_settings']);
    $add('sameas', 'local', 'Sosyal ve harici profiller (sameAs)', 3, min(1, count($org['sameAs'] ?? []) / 3), count($org['sameAs'] ?? []) . ' profil bağlı.', 'LinkedIn, Instagram, YouTube, Alibaba, Wikipedia/Wikidata gibi resmi profillerinizi sameAs listesine ekleyin; markanızın kimliğini doğrular.', ['seo_settings']);
    $add('xdefault', 'local', 'Dil/bölge hedeflemesi (x-default)', 2, 1, 'hreflang x-default tanımlı.', '');

    // ---- scores
    $cats = ['tech' => ['Teknik SEO', 35], 'content' => ['İçerik', 20], 'geo' => ['GEO — yapay zekâ aramaları', 30], 'local' => ['Coğrafi / yerel', 15]];
    $catScore = [];
    $total = 0.0;
    foreach ($cats as $k => [$label, $w]) {
        $sum = $got = 0.0;
        foreach ($checks as $c) {
            if ($c['cat'] === $k) {
                $sum += $c['weight'];
                $got += $c['weight'] * $c['ratio'];
            }
        }
        $catScore[$k] = $sum ? round($got / $sum * 100) : 0;
        $total += $catScore[$k] * $w / 100;
    }
    $catSum = [];
    foreach ($checks as $c) {
        $catSum[$c['cat']] = ($catSum[$c['cat']] ?? 0) + $c['weight'];
    }
    foreach ($checks as $i => $c) {
        $checks[$i]['gain'] = round($c['weight'] * (1 - $c['ratio']) / max(1, $catSum[$c['cat']]) * $cats[$c['cat']][1], 1);
    }
    $recs = array_filter($checks, function ($c) { return $c['ratio'] < 0.95 && $c['fix'] !== ''; });
    usort($recs, function ($x, $y) { return $y['gain'] <=> $x['gain']; });
    $facts = [];
    foreach ($pages as $slug => $langs) {
        foreach ($langs as $l => $f) {
            $facts[$slug][$l] = array_diff_key($f, ['text' => 1]);
        }
    }
    return ['ts' => date('Y-m-d H:i:s'), 'score' => (int)round($total), 'cats' => $catScore, 'cat_labels' => array_map(function ($c) { return $c[0]; }, $cats), 'checks' => $checks, 'recs' => array_values($recs), 'pages' => $facts];
}

function audit_save(array $a): void
{
    q('INSERT INTO seo_audits(ts, score, data) VALUES(?,?,?)', [$a['ts'], $a['score'], je($a)]);
    q('DELETE FROM seo_audits WHERE id NOT IN (SELECT id FROM seo_audits ORDER BY id DESC LIMIT 20)');
}

function audit_last(): ?array
{
    $r = row('SELECT data FROM seo_audits ORDER BY id DESC LIMIT 1');
    return $r ? jd($r['data']) : null;
}
