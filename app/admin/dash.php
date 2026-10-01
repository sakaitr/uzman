<?php
declare(strict_types=1);

function admin_dash(): void
{
    $newN = new_submissions();
    $totalN = (int)val('SELECT COUNT(*) FROM submissions');
    $pagesN = (int)val('SELECT COUNT(*) FROM pages WHERE status = 1');
    $prodN = (int)val('SELECT COUNT(*) FROM items WHERE active = 1');
    $noImg = (int)val("SELECT COUNT(*) FROM items WHERE active = 1 AND image = ''");
    $docsN = (int)val('SELECT COUNT(*) FROM docs WHERE active = 1');
    $mailFail = (int)val("SELECT COUNT(*) FROM submissions WHERE mail_ok = 0 AND created_at > date('now','-14 day')");
    $miss = [];
    foreach (LANGS as $l) {
        if ($l !== 'tr') {
            $miss[$l] = (int)val("SELECT COUNT(*) FROM strings s WHERE s.lang = ? AND TRIM(s.v) = '' ", [$l]);
        }
    }
    $audit = audit_last();
    $openA = (int)val("SELECT COUNT(*) FROM growth_actions WHERE status IN ('todo','doing')");
    $items = [
        [$audit && $audit['score'] >= 75, 'SEO & GEO skoru', $audit ? 'Son skor ' . $audit['score'] . '/100 (' . h($audit['ts']) . '). ' . count($audit['recs']) . ' öneri bekliyor.' : 'Henüz taranmadı; SEO & GEO sayfasından ilk taramayı başlatın.', 'seo'],
        [(int)val('SELECT COUNT(*) FROM visits') > 0, 'Ziyaret ölçümü ve büyüme aksiyonları', (int)val('SELECT COUNT(*) FROM visits') > 0 ? $openA . ' açık aksiyon bekliyor.' : 'Ziyaret verisi henüz gelmedi; İzleme ve otomasyon sayfasını kontrol edin.', 'growth'],
        [$docsN > 0, 'Belgeler / sertifikalar', $docsN > 0 ? $docsN . ' belge yayında.' : 'Henüz belge eklenmedi; Kalite sayfası "talep üzerine paylaşılır" notunu gösteriyor. (Eski sitedeki tek sertifika 2012–2015 tarihli ISO 14001:2004 idi; güncel belgelerinizi ekleyin.)', 'docs'],
        [trim((string)setting('whatsapp')) !== '', 'WhatsApp numarası', trim((string)setting('whatsapp')) !== '' ? 'Sağ altta WhatsApp butonu görünüyor.' : 'Girilirse sitede yüzen WhatsApp butonu çıkar (ülke koduyla, örn. 905551112233).', 'settings'],
        [trim((string)setting('smtp_host')) !== '', 'E-posta bildirimi (SMTP)', trim((string)setting('smtp_host')) !== '' ? 'SMTP tanımlı.' : 'Şu an PHP mail() kullanılıyor; teslimat için cPanel e-posta hesabıyla SMTP tanımlamanız önerilir.', 'settings'],
        [trim((string)setting('site_url')) !== '', 'Site adresi (SEO)', trim((string)setting('site_url')) !== '' ? h(setting('site_url')) : 'Boşsa adres otomatik algılanır. Canlıya alırken ana alan adını (https://uzmancosmetic.com) girin.', 'settings'],
        [$noImg === 0, 'Ürün görselleri', $noImg === 0 ? 'Tüm ürünlerin görseli var.' : $noImg . ' ürün "görsel yakında" kartı olarak görünüyor.', 'catalog'],
        [array_sum($miss) === 0, 'Çeviriler', array_sum($miss) === 0 ? 'Tüm diller dolu.' : 'Eksik metin: ' . implode(', ', array_map(function ($l, $n) { return strtoupper($l) . ' ' . $n; }, array_keys($miss), $miss)) . ' (boş kalanlar Türkçe gösterilir).', 'strings'],
        [false, 'Gizlilik / KVKK metni', 'Şablon metin eklendi; yayına almadan önce hukuk danışmanınıza onaylatın.', 'pages'],
        [$mailFail === 0, 'Gönderilemeyen bildirimler', $mailFail === 0 ? 'Sorun yok.' : "Son 14 günde $mailFail başvurunun e-posta bildirimi gönderilemedi; başvurular Başvurular menüsünde duruyor.", 'subs'],
    ];
    ahead('Panel', 'dash', 'Sitenin genel durumu');
    echo '<div class="grid g4" style="margin-bottom:20px">
<div class="stat"><b>' . $newN . '</b><span>Yeni başvuru</span></div><div class="stat"><b>' . $totalN . '</b><span>Toplam başvuru</span></div>
<div class="stat"><b>' . $pagesN . '</b><span>Yayındaki sayfa</span></div><div class="stat"><b>' . $prodN . '</b><span>Ürün görseli</span></div></div>
<div class="grid g2"><div class="card"><h2>Yapılacaklar</h2><ul class="check-list">';
    foreach ($items as [$ok, $t, $d, $to]) {
        echo '<li><span class="dot' . ($ok ? ' ok' : '') . '"></span><div><b>' . h($t) . '</b><div class="hint">' . $d . ' <a href="' . admin_url($to) . '">Aç →</a></div></div></li>';
    }
    echo '</ul></div><div class="card"><h2>Son başvurular</h2>';
    $last = rows('SELECT id, created_at, name, company, status FROM submissions ORDER BY id DESC LIMIT 6');
    if (!$last) {
        echo '<p class="hint">Henüz başvuru yok. İletişim sayfasındaki form buraya düşer.</p>';
    } else {
        echo '<table><tbody>';
        foreach ($last as $s) {
            echo '<tr><td><a href="' . admin_url('sub_view', ['id' => $s['id']]) . '"><b>' . h($s['company']) . '</b></a><div class="hint">' . h($s['name']) . ' · ' . h($s['created_at']) . '</div></td><td style="text-align:right"><span class="pill ' . ($s['status'] === 'new' ? 'new' : 'ok') . '">' . ($s['status'] === 'new' ? 'Yeni' : 'İşlendi') . '</span></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '<p style="margin:14px 0 0"><a class="btn sm" href="' . admin_url('subs') . '">Tümünü gör</a></p></div></div>';
    afoot();
}
