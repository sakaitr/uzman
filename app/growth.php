<?php
/**
 * Growth hub: first-party traffic + lead attribution, action board (SEO/GEO/ads/…), ad campaigns, monthly reports, cron tasks.
 * Client-neutral — nothing here is specific to one website.
 */
declare(strict_types=1);

const GROWTH_CHANNELS = ['seo' => 'SEO', 'geo' => 'GEO / yapay zekâ', 'local' => 'Yerel / harita', 'content' => 'İçerik', 'ads' => 'Reklam', 'social' => 'Sosyal medya', 'b2b' => 'B2B platform / dizin', 'pr' => 'PR / bağlantı', 'tech' => 'Teknik / izleme'];
const GROWTH_STATUS = ['todo' => 'Planlandı', 'doing' => 'Yapılıyor', 'done' => 'Tamamlandı', 'skip' => 'İptal'];
const GROWTH_LEVEL = ['high' => 'Yüksek', 'med' => 'Orta', 'low' => 'Düşük'];
const AD_PLATFORMS = ['google' => 'Google Ads', 'meta' => 'Meta (Facebook/Instagram)', 'linkedin' => 'LinkedIn', 'bing' => 'Microsoft Ads', 'alibaba' => 'Alibaba.com', 'youtube' => 'YouTube', 'other' => 'Diğer'];
const AD_STATUS = ['draft' => 'Taslak', 'active' => 'Yayında', 'paused' => 'Durduruldu', 'ended' => 'Bitti'];
const CHANNEL_LABELS = ['paid_search' => 'Reklam — arama', 'paid_social' => 'Reklam — sosyal', 'email' => 'E-posta', 'organic_search' => 'Organik arama', 'ai' => 'Yapay zekâ (ChatGPT vb.)', 'social' => 'Sosyal medya', 'referral' => 'Yönlendirme (başka site)', 'direct' => 'Doğrudan / bilinmeyen'];
const CHANNEL_COLORS = ['paid_search' => '#b3382c', 'paid_social' => '#d6803a', 'email' => '#7a5aa6', 'organic_search' => '#2d7a55', 'ai' => '#1f6fb2', 'social' => '#c4568a', 'referral' => '#8a7b3c', 'direct' => '#8d9199'];

function classify_channel(string $medium, string $source, string $clickId, string $refHost, string $siteHost = ''): array
{
    $medium = strtolower(trim($medium));
    $source = strtolower(trim($source));
    $ref = strtolower(preg_replace('/^www\./', '', trim($refHost)));
    $site = strtolower(preg_replace('/^www\./', '', $siteHost));
    $has = function (string $h, array $needles): bool {
        foreach ($needles as $n) {
            if ($h !== '' && strpos($h, $n) !== false) {
                return true;
            }
        }
        return false;
    };
    $socialN = ['facebook.', 'fb.com', 'instagram.', 'linkedin.', 'lnkd.in', 'twitter.', 't.co', 'x.com', 'youtube.', 'youtu.be', 'pinterest.', 'tiktok.', 'reddit.', 'wa.me', 'whatsapp.', 't.me', 'telegram.', 'vk.com', 'ok.ru'];
    $paid = in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'paid-search', 'paidsocial', 'paid_social', 'paid-social', 'display', 'cpm'], true) || in_array($clickId, ['gclid', 'msclkid'], true) || ($clickId === 'fbclid' && $medium !== '' && $medium !== 'social');
    if ($paid) {
        $social = in_array($medium, ['paidsocial', 'paid_social', 'paid-social'], true) || $has($source, ['facebook', 'instagram', 'meta', 'linkedin', 'tiktok', 'twitter', 'youtube']) || $clickId === 'fbclid';
        return [$social ? 'paid_social' : 'paid_search', $source ?: ($clickId === 'gclid' ? 'google' : ($ref ?: 'paid'))];
    }
    if ($medium === 'email' || $medium === 'newsletter') {
        return ['email', $source ?: 'email'];
    }
    if ($has($ref, ['chatgpt.com', 'chat.openai.com', 'perplexity.', 'claude.ai', 'gemini.google.', 'copilot.microsoft.', 'you.com', 'phind.com', 'poe.com', 'kagi.com', 'deepseek.', 'grok.']) || $has($source, ['chatgpt', 'perplexity', 'claude', 'gemini', 'copilot'])) {
        return ['ai', $ref ?: $source];
    }
    if ($medium === 'organic' || $has($ref, ['google.', 'bing.', 'yahoo.', 'yandex.', 'duckduckgo.', 'baidu.', 'ecosia.', 'naver.', 'seznam.', 'search.brave'])) {
        return ['organic_search', $ref ?: $source];
    }
    if ($medium === 'social' || $has($ref, $socialN) || $has($source, ['facebook', 'instagram', 'linkedin', 'youtube'])) {
        return ['social', $ref ?: $source];
    }
    if ($ref !== '' && $ref !== $site) {
        return ['referral', $ref];
    }
    return ['direct', ''];
}

function growth_currency(): string
{
    $c = (string)setting('growth_currency', 'EUR');
    return in_array($c, ['EUR', 'USD', 'TRY', 'GBP'], true) ? $c : 'EUR';
}

function money($v): string
{
    return number_format((float)$v, 2, ',', '.') . ' ' . growth_currency();
}

function num($v): string
{
    return number_format((float)$v, 0, ',', '.');
}

/** Ready-made action library for a B2B manufacturer / exporter. */
function growth_playbooks(): array
{
    $p = function ($id, $channel, $title, $impact, $effort, $why, $steps) {
        return ['id' => $id, 'channel' => $channel, 'title' => $title, 'impact' => $impact, 'effort' => $effort, 'why' => $why, 'steps' => $steps];
    };
    return [
        $p('gsc', 'tech', 'Google Search Console\'u kur ve sitemap gönder', 'high', 'low', 'Arama performansını ölçmenin ve dizine girme sorunlarını görmenin tek ücretsiz yolu.', "search.google.com/search-console → Mülk ekle (alan adı)\nHTML etiketi kodunu SEO & GEO → Ayarlar'a yapıştırın\nDoğrula → Sitemaps bölümüne sitemap.xml gönderin\nAna sayfa ve ürün sayfaları için \"Dizine eklenmesini iste\" kullanın"),
        $p('bing', 'tech', 'Bing Webmaster Tools\'u bağla (ChatGPT/Copilot aramalarını besler)', 'med', 'low', 'ChatGPT arama ve Copilot, Bing dizinini kullanır; Bing\'de görünmeyen site yapay zekâ cevaplarında da zayıf kalır.', "bing.com/webmasters → siteyi içe aktar (Search Console'dan tek tık)\nmsvalidate.01 kodunu SEO & GEO → Ayarlar'a girin\nSitemap'i gönderin"),
        $p('ga4', 'tech', 'Ziyaret ve dönüşüm ölçümünü kur (GA4 / GTM)', 'high', 'med', 'Reklam ve SEO yatırımının geri dönüşünü ölçmeden optimizasyon yapılamaz.', "İzleme ayarları sayfasına GA4 (G-XXXX) veya GTM kimliğini girin\nÇerez onay bandını açın\nForm gönderiminde \"generate_lead\" olayının geldiğini GA4 DebugView'da doğrulayın"),
        $p('gbp', 'local', 'Google İşletme Profili oluştur / doğrula', 'high', 'med', 'Harita ve yerel aramalarda firma kartı çıkar; marka aramalarında güven verir.', "business.google.com → firmayı ekle (fabrika adresi)\nKategori: Kozmetik ürünleri üreticisi / İmalatçı\nTelefon, web sitesi, çalışma saatleri, fotoğraf (fabrika, ürünler) ekleyin\nPosta/telefon ile doğrulayın\nProfil bağlantısını SEO & GEO → Ayarlar → Google İşletme Profili alanına ekleyin"),
        $p('reviews', 'local', 'Müşteri değerlendirmesi toplama süreci başlat', 'med', 'med', 'Değerlendirmeler yerel sıralamayı ve alıcı güvenini artırır.', "Memnun 5–10 müşteriden Google profilinde yorum isteyin\nYorum bağlantısını e-posta imzasına ekleyin\nHer yoruma 48 saat içinde yanıt verin"),
        $p('faq10', 'geo', 'SSS\'yi 10+ gerçek soruyla genişlet', 'high', 'low', 'Yapay zekâ cevap motorları soru-cevap biçimli içeriği en çok alıntılar.', "Satış ekibinden en sık gelen 10 soruyu toplayın (MOQ, numune süresi, ambalaj, sertifika, ihracat evrakı)\nHer soruya 2–4 cümlelik net, rakamlı cevap yazın\nSayfalar → Sıkça Sorulan Sorular → 5 dilde ekleyin"),
        $p('ai_test', 'geo', 'Aylık "yapay zekâ cevap testi" yap', 'med', 'low', 'Markanızın ChatGPT/Perplexity/Gemini cevaplarında geçip geçmediğini ölçmenin en pratik yolu.', "10 alıcı sorusu belirleyin (örn. \"Türkiye'de private label deodorant üreticileri\")\nHer soruyu ChatGPT, Perplexity, Gemini, Claude'da sorun\nMarka geçti mi / hangi site kaynak gösterildi not alın\nKaynak gösterilen rakip/dizin sitelerine ekleme planı çıkarın"),
        $p('wikidata', 'geo', 'Wikidata / Vikipedi varlık kaydı', 'med', 'med', 'Yapay zekâ ve arama motorları markayı Wikidata gibi açık bilgi tabanlarıyla eşler.', "Wikidata'da firma için kayıt oluşturun (resmi site, kuruluş yılı, konum, sektör)\nKayıt bağlantısını sameAs listesine ekleyin\nBasında/üçüncü taraf kaynaklarda geçişi referans verin"),
        $p('catalog', 'content', 'İndirilebilir ürün kataloğu (PDF) yayınla', 'high', 'med', 'B2B alıcılar katalog ister; PDF hem lead hem bağlantı çeker. (Eski sitedeki katalog bağlantısı çalışmıyordu.)', "Güncel ürün/tüp modelleri ve ölçüleriyle PDF katalog hazırlayın\nMedya'ya yükleyin; ürün ve iletişim sayfalarından bağlantı verin\nKatalog indirme için kısa form düşünün (lead toplama)"),
        $p('case', 'content', 'Vaka çalışması / referans sayfası ekle', 'high', 'med', 'Somut referans, güven ve alıntılanabilirlik sağlar.', "İsim paylaşımına izinli 2–3 marka seçin\nProblem → çözüm → sonuç biçiminde yazın\nÖzel sayfa olarak yayınlayın (Sayfalar → Yeni sayfa)"),
        $p('article', 'content', 'Aylık uzman makalesi yayınla', 'med', 'high', 'Private label süreçleri, MOQ, mevzuat gibi konularda uzman içerik hem SEO hem GEO için trafik getirir.', "Konu seç: \"Private label deodorant üretimi nasıl yapılır?\" gibi alıcı sorusu\n800–1.200 kelime, alt başlıklı, rakamlı yazın\nÖzel sayfa olarak yayınlayın; ilgili ürün sayfalarına bağlantı verin\nLinkedIn'de paylaşın"),
        $p('video', 'content', 'Fabrika / üretim tanıtım videosu', 'med', 'high', 'Gerçek üretim görüntüsü güven verir; YouTube ikinci büyük arama motorudur.', "3–4 dk fabrika turu çekin (hat, dolum, kalite kontrol)\nYouTube'a TR+EN başlık/açıklama ile yükleyin\nSitede Tesis sayfasına ekleyin"),
        $p('alibaba', 'b2b', 'Alibaba.com mağazası aç / güncelle', 'high', 'high', 'İhracatçı alıcıların önemli bir kısmı ilk aramayı B2B pazaryerlerinde yapar.', "Gold Supplier başvurusu (fabrika bilgileri, sertifikalar)\n10+ ürünü katalog görselleriyle listeleyin\nMOQ ve fiyat aralığını yazın\nSite adresini mağaza profilinde verin"),
        $p('europages', 'b2b', 'Europages / Kompass dizin kayıtları', 'med', 'low', 'Güçlü alan adlı B2B dizinlerinden bağlantı ve alıcı trafiği.', "europages.com ve kompass.com'da firma profili açın\nNAP bilgisini (ad-adres-telefon) siteyle birebir aynı yazın\nSiteye bağlantı ve ürün kategorilerini ekleyin"),
        $p('assoc', 'pr', 'Sektör dernekleri / ihracatçı birlikleri üyelik sayfalarından bağlantı', 'med', 'low', 'Kurumsal alan adlarından gelen bağlantılar güven sinyalidir.', "Üyesi olduğunuz dernek/birlik listesini çıkarın\nÜye firma sayfasında site bağlantısı olduğunu kontrol edin, yoksa talep edin"),
        $p('press', 'pr', 'Basın bülteni: yeni ürün / ihracat / kapasite', 'med', 'med', 'Sektör medyasında haber, bağlantı ve marka aramalarını artırır.', "Haber değeri olan bir gelişme seçin (yeni hat, yeni pazar, sertifika)\n5 dilde kısa bülten hazırlayın\nSektör sitelerine ve ihracat bültenlerine gönderin"),
        $p('fair', 'pr', 'Fuar katılımcı profillerini güncelle', 'med', 'low', 'Fuar siteleri yüksek otoriteli bağlantı ve alıcı listesi verir.', "Katıldığınız fuarların (örn. kozmetik/ambalaj fuarları) katılımcı sayfalarını bulun\nSite adresi, kısa tanım ve görselleri güncelleyin"),
        $p('linkedin', 'social', 'LinkedIn şirket sayfasını kur ve düzenli paylaş', 'high', 'med', 'B2B satın alıcıları LinkedIn\'de tedarikçi arar; sameAs sinyali de verir.', "Şirket sayfası: logo, kapak, tanım, web sitesi\nHaftada 1–2 paylaşım: üretim görselleri, yeni ürün, süreç\nBağlantıyı SEO & GEO → sameAs listesine ekleyin"),
        $p('instagram', 'social', 'Instagram: üretim ve ürün kısa videoları (Reels)', 'low', 'med', 'Marka bilinirliği; hero videolarındaki içerikler yeniden kullanılabilir.', "Sitedeki arka plan videolarını/ürün görsellerini kısa kliplere çevirin\nHaftada 2 paylaşım, profil bağlantısı siteye"),
        $p('gads_search', 'ads', 'Google Ads — Arama kampanyası (private label + ülke anahtar kelimeleri)', 'high', 'high', 'Alıcı niyetli aramalara (örn. "private label deodorant manufacturer") doğrudan görünürlük sağlar.', "Reklamlar menüsünde kampanya oluşturun (UTM bağlantısı otomatik)\nAnahtar kelime grupları: private label deodorant / body mist / room spray + hedef ülke\nDil bazlı reklam grupları ve ilgili açılış sayfası\nİzleme ayarlarında Google Ads dönüşüm etiketini tanımlayın\nHaftalık olumsuz kelime temizliği"),
        $p('gads_conv', 'ads', 'Google Ads dönüşüm takibi (form gönderimi)', 'high', 'low', 'Dönüşüm verisi olmadan reklam optimizasyonu kör kalır.', "Google Ads → Hedefler → Dönüşüm eylemi (Web sitesi) oluşturun\nKimlik (AW-…) ve etiketi İzleme ayarlarına girin\nTest formu gönderin ve dönüşümün göründüğünü doğrulayın"),
        $p('linkedin_ads', 'ads', 'LinkedIn Ads — satın alma / tedarik yöneticilerine hedefleme', 'med', 'high', 'B2B karar vericilere unvan ve sektör bazlı ulaşım.', "Kampanya oluşturun (Reklamlar menüsü) ve UTM bağlantısını kullanın\nHedef: unvan (Purchasing, Sourcing), sektör (Kozmetik, Perakende), ülke\nTeklif/numune formuna yönlendirin"),
        $p('remarketing', 'ads', 'Yeniden pazarlama (remarketing) kitlesi', 'med', 'med', 'Siteyi ziyaret edip form doldurmayanlara yeniden ulaşır.', "Çerez onayı + GA4/Meta/Google Ads kitle tanımı\nKatalog/teklif bölümü ziyaretçilerini hedefleyin"),
        $p('form_flow', 'tech', 'Teklif formu yanıt sürecini kur (SLA)', 'high', 'low', 'Lead\'e hızlı dönüş, dönüşüm oranını belirgin artırır.', "Bildirim e-postası ve SMTP'nin çalıştığını test edin\nYeni başvuruya aynı gün yanıt hedefi belirleyin\nBaşvurular menüsünde durumları güncel tutun"),
    ];
}

// ------------------------------------------------------------------ actions
function action_log(int $id, string $text): void
{
    $u = function_exists('admin_user') ? admin_user() : null;
    q('INSERT INTO growth_log(action_id, ts, who, text) VALUES(?,?,?,?)', [$id, date('Y-m-d H:i:s'), $u['username'] ?? 'sistem', $text]);
}

function action_add(array $a): int
{
    q('INSERT INTO growth_actions(title,channel,priority,impact,effort,status,due,owner,why,steps,notes,source,campaign_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
        mb_substr($a['title'], 0, 200), isset(GROWTH_CHANNELS[$a['channel'] ?? '']) ? $a['channel'] : 'seo', $a['priority'] ?? 'med', $a['impact'] ?? 'med', $a['effort'] ?? 'med', 'todo', $a['due'] ?? '', $a['owner'] ?? '',
        $a['why'] ?? '', $a['steps'] ?? '', $a['notes'] ?? '', $a['source'] ?? '', $a['campaign_id'] ?? null, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'),
    ]);
    $id = (int)db()->lastInsertId();
    action_log($id, 'Aksiyon oluşturuldu' . (($a['source'] ?? '') ? ' (' . $a['source'] . ')' : ''));
    return $id;
}

function action_set_status(int $id, string $status, string $by = ''): void
{
    if (!isset(GROWTH_STATUS[$status])) {
        return;
    }
    $old = val('SELECT status FROM growth_actions WHERE id = ?', [$id]);
    if ($old === null || $old === $status) {
        return;
    }
    q('UPDATE growth_actions SET status = ?, updated_at = ?, done_at = ? WHERE id = ?', [$status, date('Y-m-d H:i:s'), $status === 'done' ? date('Y-m-d H:i:s') : null, $id]);
    action_log($id, 'Durum: ' . GROWTH_STATUS[$old] . ' → ' . GROWTH_STATUS[$status] . ($by !== '' ? ' (' . $by . ')' : ''));
}

/** Turn open SEO/GEO audit recommendations into board actions (deduplicated by check id). */
function actions_from_audit(array $audit): int
{
    $n = 0;
    $ch = ['tech' => 'seo', 'content' => 'content', 'geo' => 'geo', 'local' => 'local'];
    foreach ($audit['recs'] as $c) {
        $src = 'seo:' . $c['id'];
        if (val("SELECT COUNT(*) FROM growth_actions WHERE source = ? AND status IN ('todo','doing')", [$src])) {
            continue;
        }
        action_add(['title' => $c['title'], 'channel' => $ch[$c['cat']] ?? 'seo', 'impact' => $c['gain'] >= 3 ? 'high' : ($c['gain'] >= 1.2 ? 'med' : 'low'), 'priority' => $c['gain'] >= 3 ? 'high' : ($c['gain'] >= 1.2 ? 'med' : 'low'),
            'why' => $c['msg'], 'steps' => $c['fix'], 'source' => $src]);
        $n++;
    }
    return $n;
}

/** After a scan: actions whose audit check now passes are closed automatically ("verified by scan"). */
function actions_verify_with_audit(array $audit): int
{
    $pass = [];
    foreach ($audit['checks'] as $c) {
        if ($c['ratio'] >= 0.95) {
            $pass['seo:' . $c['id']] = true;
        }
    }
    $n = 0;
    foreach (rows("SELECT id, source FROM growth_actions WHERE status IN ('todo','doing') AND source LIKE 'seo:%'") as $a) {
        if (isset($pass[$a['source']])) {
            action_set_status((int)$a['id'], 'done', 'SEO taramasıyla doğrulandı');
            $n++;
        }
    }
    return $n;
}

// ------------------------------------------------------------------ reporting
function period_range(string $ym): array
{
    $start = date('Y-m-01', strtotime($ym . '-01'));
    $end = date('Y-m-t', strtotime($start));
    return [$start, $end];
}

function traffic_summary(string $from, string $to): array
{
    $r = row('SELECT COALESCE(SUM(sessions),0) AS s, COALESCE(SUM(views),0) AS v FROM visits WHERE day BETWEEN ? AND ?', [$from, $to]);
    $by = rows('SELECT channel, SUM(sessions) AS s, SUM(views) AS v FROM visits WHERE day BETWEEN ? AND ? GROUP BY channel ORDER BY s DESC', [$from, $to]);
    $leads = rows("SELECT channel, COUNT(*) AS n FROM submissions WHERE date(created_at) BETWEEN ? AND ? GROUP BY channel", [$from, $to]);
    $leadBy = [];
    foreach ($leads as $l) {
        $leadBy[$l['channel'] ?: 'direct'] = (int)$l['n'];
    }
    return ['sessions' => (int)$r['s'], 'views' => (int)$r['v'], 'by' => $by, 'leads_by' => $leadBy, 'leads' => array_sum($leadBy)];
}

function top_pages(string $from, string $to, int $limit = 8): array
{
    return rows('SELECT slug, SUM(sessions) AS s, SUM(views) AS v FROM visits WHERE day BETWEEN ? AND ? GROUP BY slug ORDER BY v DESC LIMIT ' . $limit, [$from, $to]);
}

function campaign_stats(int $id, string $from = '0000-00-00', string $to = '9999-12-31'): array
{
    $c = row('SELECT * FROM campaigns WHERE id = ?', [$id]);
    $m = row('SELECT COALESCE(SUM(spend),0) AS spend, COALESCE(SUM(impressions),0) AS imp, COALESCE(SUM(clicks),0) AS clk, COALESCE(SUM(conversions),0) AS conv FROM campaign_metrics WHERE campaign_id = ? AND to_date >= ? AND from_date <= ?', [$id, $from, $to]);
    $leads = (int)val("SELECT COUNT(*) FROM submissions WHERE utm_campaign = ? AND date(created_at) BETWEEN ? AND ?", [$c['slug'] ?? '', $from, $to]);
    $sessions = (int)val("SELECT COALESCE(SUM(sessions),0) FROM visits WHERE source = ? AND day BETWEEN ? AND ?", ['campaign:' . ($c['slug'] ?? ''), $from, $to]);
    $spend = (float)$m['spend'];
    $clicks = (int)$m['clk'];
    $imp = (int)$m['imp'];
    $totalLeads = max($leads, (int)$m['conv']);
    return ['campaign' => $c, 'spend' => $spend, 'imp' => $imp, 'clicks' => $clicks, 'conv' => (int)$m['conv'], 'leads' => $leads, 'sessions' => $sessions,
        'ctr' => $imp ? $clicks / $imp * 100 : 0, 'cpc' => $clicks ? $spend / $clicks : 0, 'cpl' => $totalLeads ? $spend / $totalLeads : 0, 'best_leads' => $totalLeads];
}

function campaign_url(array $c, string $lang = 'tr'): string
{
    $src = ['google' => 'google', 'meta' => 'facebook', 'linkedin' => 'linkedin', 'bing' => 'bing', 'alibaba' => 'alibaba', 'youtube' => 'youtube'][$c['platform']] ?? 'other';
    $medium = in_array($c['platform'], ['meta', 'linkedin'], true) ? 'paid_social' : 'cpc';
    $base = page_abs_url($lang, preg_match('/^[a-z0-9-]+$/', (string)$c['landing']) ? $c['landing'] : 'index');
    return $base . '?' . http_build_query(['utm_source' => $src, 'utm_medium' => $medium, 'utm_campaign' => $c['slug']]);
}

function seo_score_in(string $from, string $to): array
{
    $a = row('SELECT score, ts FROM seo_audits WHERE date(ts) <= ? ORDER BY id DESC LIMIT 1', [$from]) ?: row('SELECT score, ts FROM seo_audits WHERE date(ts) BETWEEN ? AND ? ORDER BY id ASC LIMIT 1', [$from, $to]);
    $b = row('SELECT score, ts FROM seo_audits WHERE date(ts) <= ? ORDER BY id DESC LIMIT 1', [$to]);
    return ['start' => $a ? (int)$a['score'] : null, 'end' => $b ? (int)$b['score'] : null];
}

function growth_report(string $ym): array
{
    [$from, $to] = period_range($ym);
    $pfrom = date('Y-m-01', strtotime($from . ' -1 month'));
    [, $pto] = period_range(date('Y-m', strtotime($pfrom)));
    $cur = traffic_summary($from, $to);
    $prev = traffic_summary($pfrom, $pto);
    $camps = [];
    foreach (rows('SELECT id FROM campaigns ORDER BY id') as $c) {
        $st = campaign_stats((int)$c['id'], $from, $to);
        if ($st['spend'] > 0 || $st['leads'] > 0 || $st['clicks'] > 0 || $st['sessions'] > 0) {
            $camps[] = $st;
        }
    }
    $done = rows("SELECT * FROM growth_actions WHERE status = 'done' AND date(done_at) BETWEEN ? AND ? ORDER BY done_at", [$from, $to]);
    $open = (int)val("SELECT COUNT(*) FROM growth_actions WHERE status IN ('todo','doing')");
    $overdue = rows("SELECT title, due FROM growth_actions WHERE status IN ('todo','doing') AND due <> '' AND due < ? ORDER BY due LIMIT 10", [date('Y-m-d')]);
    return ['ym' => $ym, 'from' => $from, 'to' => $to, 'cur' => $cur, 'prev' => $prev, 'pages' => top_pages($from, $to), 'camps' => $camps, 'done' => $done, 'open' => $open, 'overdue' => $overdue, 'seo' => seo_score_in($from, $to),
        'ai' => (int)val("SELECT COALESCE(SUM(sessions),0) FROM visits WHERE channel = 'ai' AND day BETWEEN ? AND ?", [$from, $to]), 'ai_prev' => (int)val("SELECT COALESCE(SUM(sessions),0) FROM visits WHERE channel = 'ai' AND day BETWEEN ? AND ?", [$pfrom, $pto])];
}

function delta_html($cur, $prev, bool $lowerBetter = false): string
{
    if (!$prev) {
        return $cur ? '<span class="hint">yeni</span>' : '<span class="hint">—</span>';
    }
    $d = ($cur - $prev) / $prev * 100;
    $good = $lowerBetter ? $d <= 0 : $d >= 0;
    return '<span style="color:' . ($good ? '#2d7a55' : '#b3382c') . ';font-weight:600">' . ($d >= 0 ? '▲' : '▼') . ' ' . abs((int)round($d)) . '%</span>';
}

function report_text(array $r): string
{
    $mon = ['01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan', '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos', '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'];
    [$y, $m] = explode('-', $r['ym']);
    $o = (setting('site_name', 'Site') . " — Büyüme raporu · {$mon[$m]} $y\n") . str_repeat('=', 48) . "\n\n";
    $c = $r['cur'];
    $p = $r['prev'];
    $o .= "Ziyaret (oturum): " . num($c['sessions']) . " (önceki ay " . num($p['sessions']) . ")\nSayfa görüntüleme: " . num($c['views']) . "\nForm başvurusu: " . $c['leads'] . " (önceki ay " . $p['leads'] . ")\n";
    $o .= 'Dönüşüm oranı: ' . ($c['sessions'] ? round($c['leads'] / $c['sessions'] * 100, 2) : 0) . "%\nYapay zekâ yönlendirmeli oturum: " . $r['ai'] . " (önceki ay " . $r['ai_prev'] . ")\n";
    if ($r['seo']['end'] !== null) {
        $o .= 'SEO & GEO skoru: ' . ($r['seo']['start'] ?? '—') . ' → ' . $r['seo']['end'] . "\n";
    }
    $o .= "\nKanal kırılımı (oturum / başvuru)\n";
    foreach ($c['by'] as $b) {
        $o .= '  - ' . (CHANNEL_LABELS[$b['channel']] ?? $b['channel']) . ': ' . $b['s'] . ' / ' . ($c['leads_by'][$b['channel']] ?? 0) . "\n";
    }
    if ($r['camps']) {
        $o .= "\nReklam kampanyaları\n";
        foreach ($r['camps'] as $s) {
            $o .= '  - ' . $s['campaign']['name'] . ': harcama ' . money($s['spend']) . ', tıklama ' . $s['clicks'] . ', başvuru ' . $s['best_leads'] . ($s['best_leads'] ? ', başvuru başı ' . money($s['cpl']) : '') . "\n";
        }
    }
    $o .= "\nBu ay tamamlanan aksiyonlar (" . count($r['done']) . ")\n";
    foreach ($r['done'] as $a) {
        $o .= '  ✓ ' . $a['title'] . "\n";
    }
    $o .= "\nAçık aksiyon: " . $r['open'] . "\n";
    foreach ($r['overdue'] as $a) {
        $o .= '  ! Gecikmiş: ' . $a['title'] . ' (' . $a['due'] . ")\n";
    }
    return $o;
}

// ------------------------------------------------------------------ cron
function growth_cron(string $task): string
{
    $log = [];
    if ($task === 'weekly' || $task === 'all') {
        $a = audit_run();
        audit_save($a);
        $v = actions_verify_with_audit($a);
        $log[] = 'SEO taraması: skor ' . $a['score'] . ", $v aksiyon doğrulandı";
    }
    if ($task === 'monthly' || ($task === 'all' && date('j') === '1')) {
        require_once UZ_APP . '/mail.php';
        $r = growth_report(date('Y-m', strtotime('first day of last month')));
        $to = trim((string)setting('notify_email')) ?: (string)setting('email');
        if ($to !== '') {
            [$ok, $err] = mail_send($to, setting('site_name', 'Site') . ' — aylık büyüme raporu (' . $r['ym'] . ')', report_text($r) . "\n\nAyrıntı: " . base_url() . "admin/index.php?a=reports&m=" . $r['ym']);
            $log[] = 'Aylık rapor e-postası: ' . ($ok ? 'gönderildi' : 'gönderilemedi (' . $err . ')');
        }
    }
    q('DELETE FROM visits WHERE day < ?', [date('Y-m-d', strtotime('-26 months'))]);
    set_setting('cron_last', date('Y-m-d H:i:s') . ' · ' . $task);
    return implode("\n", $log) ?: 'tamam';
}

function cron_key(): string
{
    return substr(hash_hmac('sha256', 'cron', secret()), 0, 24);
}
