<?php
declare(strict_types=1);

require_once UZ_APP . '/admin/seo.php';

/** Fetch one of our own URLs: [status, body] or null when the server cannot reach itself. */
function self_fetch(string $path): ?array
{
    $url = base_url() . ltrim($path, '/');
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 4, 'ignore_errors' => true, 'follow_location' => 0, 'header' => "User-Agent: UzmanSelfCheck\r\n"], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $body = @file_get_contents($url, false, $ctx, 0, 4000);
    if ($body === false && empty($http_response_header)) {
        return null;
    }
    $code = 0;
    if (!empty($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    }
    return [$code, (string)$body];
}

function admin_golive(): void
{
    $manual = [
        'privacy' => 'Gizlilik / KVKK metni hukuk danışmanınca onaylandı',
        'oldbackup' => 'Eski sitenin dosya ve veritabanı yedeği alındı',
        'dns' => 'Alan adı (DNS) yeni sunucuya yönlendirildi ve SSL (https) çalışıyor',
        'content' => 'İçerikler (ürünler, sertifika, fabrika bilgileri, MOQ/teslim süreleri) müşteri tarafından onaylandı',
        'forms' => 'Gerçek bir teklif formu gönderimi yapıldı ve bildirim e-postası alındı',
        'gsc' => 'Google Search Console\'a site eklendi ve sitemap.xml gönderildi',
    ];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $k = (string)($_POST['key'] ?? '');
        if (isset($manual[$k])) {
            set_setting('golive_ack_' . $k, ($_POST['on'] ?? '') === '1' ? date('Y-m-d H:i') : '');
        }
        redirect_to('golive');
    }
    $checks = [];
    $add = function (string $group, string $title, string $status, string $detail, string $fix = '', ?array $link = null) use (&$checks) {
        $checks[] = compact('group', 'title', 'status', 'detail', 'fix', 'link');
    };
    $su = trim((string)setting('site_url', ''));
    // ---- Alan adı ve güvenlik
    $add('Alan adı ve güvenlik', 'Site adresi (canonical) https olarak tanımlı', $su !== '' && strpos($su, 'https://') === 0 ? 'ok' : 'fail', $su !== '' ? $su : 'Tanımlı değil', 'Ayarlar → SEO → Site adresi: https://alanadi.com', ['settings']);
    $add('Alan adı ve güvenlik', 'Şu an https ile açık', is_https() ? 'ok' : 'warn', is_https() ? 'Bağlantı güvenli.' : 'Bu istek http üzerinden geldi (test ortamında normal).', 'cPanel → SSL/TLS Status → AutoSSL ile sertifika alın.');
    $add('Alan adı ve güvenlik', 'HTTPS ve tek alan adı yönlendirmeleri açık', setting('force_https', '0') === '1' && setting('force_host', '0') === '1' ? 'ok' : 'warn', 'HTTPS: ' . (setting('force_https', '0') === '1' ? 'açık' : 'kapalı') . ' · alan adı: ' . (setting('force_host', '0') === '1' ? 'açık' : 'kapalı'), 'Canlıya geçince Ayarlar → SEO bölümünde iki kutuyu da işaretleyin.', ['settings']);
    $robots = setting('robots_index', '1') === '1';
    $add('Alan adı ve güvenlik', 'Arama motorlarına açık', $robots ? 'ok' : 'warn', $robots ? 'Dizinleme açık.' : 'Dizinleme KAPALI (test modu).', 'Canlıya alırken Ayarlar → SEO → dizinlemeye izin verin.', ['settings']);
    foreach ([['data/site.sqlite', 'Veritabanı dışarıdan indirilemiyor'], ['app/db.php', 'Uygulama kodu dışarıdan okunamıyor'], ['data/config.php', 'Yapılandırma dosyası dışarıdan okunamıyor']] as [$path, $title]) {
        $r = self_fetch($path);
        $leak = $r && $r[0] === 200 && $r[1] !== '' && stripos($r[1], '<!DOCTYPE') === false;
        $add('Alan adı ve güvenlik', $title, $r === null ? 'warn' : ($leak ? 'fail' : 'ok'), $r === null ? 'Sunucu kendi adresine ulaşamadı; tarayıcıdan ' . base_url() . $path . ' açılmamalı (403/404).' : ($leak ? 'ERİŞİLEBİLİR! .htaccess çalışmıyor olabilir.' : 'Engelli (' . $r[0] . ').'), 'Hosting\'in .htaccess\'e izin verdiğinden (AllowOverride) emin olun; data/ ve app/ klasörlerini web kökünün dışına taşıyabilirsiniz.');
    }
    $ru = self_fetch('uploads/index.php');
    $add('Alan adı ve güvenlik', 'Yükleme klasöründe betik çalışmıyor', $ru === null ? 'warn' : (($ru[1] !== '' && strpos($ru[1], '<?php') !== false) ? 'fail' : 'ok'), $ru === null ? 'Kontrol edilemedi.' : 'Kaynak kod sızmıyor.', 'uploads/.htaccess dosyasının yerinde olduğundan emin olun.');
    $add('Alan adı ve güvenlik', 'PHP hata ekranı kapalı, hatalar log dosyasına yazılıyor', !ini_get('display_errors') || getenv('UZ_DEBUG') ? (getenv('UZ_DEBUG') ? 'warn' : 'ok') : 'fail', getenv('UZ_DEBUG') ? 'UZ_DEBUG açık (geliştirme modu).' : 'Hatalar data/logs/php-error.log dosyasına yazılır.', 'UZ_DEBUG ortam değişkenini kaldırın.');
    $adm = (int)val("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $weakName = (bool)val("SELECT COUNT(*) FROM users WHERE username IN ('admin','administrator','root','test')");
    $add('Alan adı ve güvenlik', 'Yönetici hesapları', $weakName ? 'warn' : 'ok', $adm . ' yönetici' . ($weakName ? ' · tahmin edilmesi kolay bir kullanıcı adı var (admin/root/test)' : ''), 'Kullanıcılar → kişiye özel kullanıcı adıyla yeni yönetici açıp eskisini silin; her hesaba e-posta ekleyin (şifre sıfırlama).', ['users']);
    $noMail = (int)val("SELECT COUNT(*) FROM users WHERE email = ''");
    $add('Alan adı ve güvenlik', 'Hesaplarda e-posta tanımlı (şifre sıfırlama)', $noMail === 0 ? 'ok' : 'warn', $noMail === 0 ? 'Tüm hesaplarda var.' : $noMail . ' hesapta e-posta yok.', 'Kullanıcılar → Profilim / yeni kullanıcı.', ['users']);
    // ---- Sunucu
    $add('Sunucu', 'PHP sürümü', version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : (version_compare(PHP_VERSION, '7.4.0', '>=') ? 'warn' : 'fail'), PHP_VERSION, 'cPanel → Select PHP Version → 8.1 veya üzeri.');
    $missing = array_filter(['pdo_sqlite', 'mbstring', 'gd', 'fileinfo', 'zip'], function ($e) { return !extension_loaded($e); });
    $add('Sunucu', 'Gerekli PHP eklentileri', $missing ? 'fail' : 'ok', $missing ? 'Eksik: ' . implode(', ', $missing) : 'Hepsi yüklü.', 'cPanel → Select PHP Version → Extensions.');
    $add('Sunucu', 'WebP desteği (görsel optimizasyonu)', function_exists('imagewebp') ? 'ok' : 'warn', function_exists('imagewebp') ? 'Var.' : 'GD WebP desteği yok; görseller PNG/JPG olarak saklanır.', 'Hosting sağlayıcısından GD\'nin WebP ile derlenmiş sürümünü isteyin.');
    $writable = array_filter(['data', 'data/cache', 'uploads'], function ($d) { return !(is_dir(UZ_ROOT . '/' . $d) && is_writable(UZ_ROOT . '/' . $d)); });
    $add('Sunucu', 'Yazılabilir klasörler (data, cache, uploads)', $writable ? 'fail' : 'ok', $writable ? 'Yazılamıyor: ' . implode(', ', $writable) : 'Tamam.', 'Klasör izinlerini 755/775 yapın (cPanel Dosya Yöneticisi → İzinler).');
    $free = @disk_free_space(UZ_ROOT);
    $add('Sunucu', 'Boş disk alanı', $free === false ? 'warn' : ($free > 300 * 1048576 ? 'ok' : 'warn'), $free === false ? 'Bilinmiyor' : round($free / 1048576) . ' MB', 'Gereksiz dosyaları temizleyin veya kotanızı artırın.');
    $cron = (string)setting('cron_last', '');
    $cronOk = $cron !== '' && strtotime(substr($cron, 0, 19)) > time() - 8 * 86400;
    $add('Sunucu', 'Haftalık otomatik görevler (cron) çalışıyor', $cronOk ? 'ok' : 'warn', $cron !== '' ? 'Son çalışma: ' . $cron : 'Henüz çalışmadı.', 'İzleme ve otomasyon sayfasındaki cron komutunu cPanel → Cron Jobs\'a ekleyin.', ['tracking']);
    $bl = (string)setting('backup_last', '');
    $add('Sunucu', 'Veritabanı yedeği alınmış (≤ 10 gün)', $bl !== '' && strtotime($bl) > time() - 10 * 86400 ? 'ok' : 'warn', $bl !== '' ? 'Son yedek: ' . $bl : 'Henüz yedek yok.', 'Araçlar → Sunucuda şimdi yedek al; cron her hafta otomatik alır. Ayrıca ZIP yedeğini kendi bilgisayarınıza indirin.', ['tools']);
    // ---- E-posta ve formlar
    $rec = notify_recipients();
    $add('E-posta ve formlar', 'Form bildirim adresleri tanımlı', $rec ? 'ok' : 'fail', $rec ? implode(', ', $rec) : 'Alıcı yok.', 'Ayarlar → Form bildirimleri (virgülle birden fazla adres).', ['settings']);
    $add('E-posta ve formlar', 'SMTP tanımlı (teslimat için önerilir)', trim((string)setting('smtp_host')) !== '' ? 'ok' : 'warn', trim((string)setting('smtp_host')) !== '' ? setting('smtp_host') . ':' . setting('smtp_port') : 'PHP mail() kullanılıyor; spam klasörüne düşebilir.', 'Ayarlar → SMTP: cPanel → E-posta Hesapları → Bağlantı Ayarları.', ['settings']);
    $mt = explode('|', (string)setting('mail_last_test', ''));
    $add('E-posta ve formlar', 'Test e-postası başarıyla gönderildi', ($mt[0] ?? '') === 'ok' ? 'ok' : (($mt[0] ?? '') === 'fail' ? 'fail' : 'warn'), ($mt[0] ?? '') !== '' ? ($mt[0] === 'ok' ? 'Başarılı' : 'Başarısız') . ' · ' . ($mt[1] ?? '') : 'Hiç denenmedi.', 'Araçlar → E-posta testi.', ['tools']);
    $failed = (int)val("SELECT COUNT(*) FROM submissions WHERE mail_ok = 0 AND created_at > date('now','-14 day')");
    $add('E-posta ve formlar', 'Son başvuruların bildirimi gönderildi', $failed === 0 ? 'ok' : 'warn', $failed === 0 ? 'Sorun yok.' : "$failed başvurunun bildirim e-postası gitmedi.", 'SMTP ayarlarını kontrol edin; başvurular Başvurular menüsünde duruyor.', ['subs']);
    // ---- İçerik ve marka
    $docs = (int)val('SELECT COUNT(*) FROM docs WHERE active = 1');
    $add('İçerik ve marka', 'Sertifika / belge yayında', $docs > 0 ? 'ok' : 'warn', $docs > 0 ? $docs . ' belge' : 'Yok — Kalite sayfası "talep üzerine" notu gösteriyor.', 'Belgeler menüsünden güncel sertifikaları ekleyin.', ['docs']);
    $noImg = (int)val("SELECT COUNT(*) FROM items WHERE active = 1 AND image = ''");
    $add('İçerik ve marka', 'Ürün görselleri tamam', $noImg === 0 ? 'ok' : 'warn', $noImg === 0 ? 'Tamam.' : $noImg . ' ürün "görsel yakında" olarak görünüyor.', 'Ürünler menüsünden görsel ekleyin ya da yayından kaldırın.', ['catalog']);
    $empty = 0;
    foreach (LANGS as $l) {
        if ($l !== 'tr') {
            $empty += (int)val("SELECT COUNT(*) FROM strings WHERE lang = ? AND TRIM(v) = ''", [$l]);
        }
    }
    $add('İçerik ve marka', 'Tüm dillerde çeviriler dolu', $empty === 0 ? 'ok' : 'warn', $empty === 0 ? 'Tamam.' : $empty . ' boş çeviri (Türkçe gösterilir).', 'Site metinleri → CSV ile çeviri alın.', ['strings']);
    $add('İçerik ve marka', 'İletişim bilgileri dolu', setting('phone1') !== '' && setting('email') !== '' ? 'ok' : 'fail', trim(setting('phone1') . ' · ' . setting('email'), ' ·'), 'Ayarlar → Firma ve iletişim.', ['settings']);
    $add('İçerik ve marka', 'Logo ve favicon markaya özel', brand_path('brand_logo_mask') !== '' ? 'ok' : 'warn', brand_path('brand_logo_mask') !== '' ? 'Özel logo yüklü.' : 'Şablon logosu kullanılıyor.', 'Marka ve görünüm → Logo.', ['brand']);
    $add('İçerik ve marka', 'Tema değiştirici kapalı', setting('theme_switcher', '0') === '0' ? 'ok' : 'warn', setting('theme_switcher', '0') === '0' ? 'Kapalı.' : 'Ziyaretçiler tema seçebiliyor (yalnızca değerlendirme içindir).', 'Ayarlar → Görünüm.', ['settings']);
    $seo = audit_last();
    $add('İçerik ve marka', 'SEO & GEO skoru ≥ 75', $seo && $seo['score'] >= 75 ? 'ok' : 'warn', $seo ? 'Skor ' . $seo['score'] . '/100' : 'Henüz taranmadı.', 'SEO & GEO sayfasından önerileri uygulayın.', ['seo']);
    $add('İçerik ve marka', 'İzleme: ziyaret ölçümü çalışıyor', (int)val('SELECT COUNT(*) FROM visits') > 0 ? 'ok' : 'warn', (int)val('SELECT COUNT(*) FROM visits') > 0 ? 'Veri geliyor.' : 'Henüz ziyaret verisi yok.', 'Yayındaki sitede bir sayfa açıp İzleme ve otomasyon sayfasını kontrol edin.', ['tracking']);
    // ---- Manuel onaylar
    foreach ($manual as $k => $label) {
        $at = (string)setting('golive_ack_' . $k, '');
        $add('Elle onaylanacaklar', $label, $at !== '' ? 'ok' : 'warn', $at !== '' ? 'Onaylandı: ' . $at : 'Henüz onaylanmadı.', '', ['ack', $k, $at !== '']);
    }
    $score = ['ok' => 1, 'warn' => 0.5, 'fail' => 0];
    $sum = 0.0;
    $fails = $warns = 0;
    foreach ($checks as $c) {
        $sum += $score[$c['status']];
        $fails += $c['status'] === 'fail' ? 1 : 0;
        $warns += $c['status'] === 'warn' ? 1 : 0;
    }
    $pct = (int)round($sum / count($checks) * 100);
    ahead('Canlıya alma kontrolü', 'golive', 'Yayına çıkmadan önce otomatik ve elle kontrol listesi');
    echo '<div class="card" style="display:flex;gap:28px;align-items:center;flex-wrap:wrap"><div>' . score_ring($pct, 120) . '</div><div><h2 style="margin-bottom:4px">' . ($fails ? $fails . ' kritik sorun' : 'Kritik sorun yok') . ' · ' . $warns . ' uyarı</h2><p class="hint" style="margin:0">Hazırlık oranı %' . $pct . '. Kırmızılar yayından önce mutlaka çözülmeli; sarılar önerilen iyileştirmelerdir. Sunucu kendi adresine erişemiyorsa güvenlik testleri "kontrol edilemedi" görünür; bu durumda adresleri tarayıcıdan elle deneyin.</p></div></div>';
    $groups = [];
    foreach ($checks as $c) {
        $groups[$c['group']][] = $c;
    }
    foreach ($groups as $g => $list) {
        echo '<div class="card"><h2>' . h($g) . '</h2><ul class="check-list">';
        foreach ($list as $c) {
            $dot = ['ok' => 'ok', 'warn' => '', 'fail' => ''][$c['status']];
            $style = $c['status'] === 'fail' ? ' style="background:#d34a3a"' : '';
            echo '<li><span class="dot ' . $dot . '"' . $style . '></span><div style="flex:1"><b>' . h($c['title']) . '</b><div class="hint">' . h($c['detail']) . ($c['status'] !== 'ok' && $c['fix'] ? ' — <i>' . h($c['fix']) . '</i>' : '') . '</div></div>';
            if ($c['link'] && $c['link'][0] === 'ack') {
                echo '<form method="post">' . csrf_field() . '<input type="hidden" name="key" value="' . h($c['link'][1]) . '"><input type="hidden" name="on" value="' . ($c['link'][2] ? '0' : '1') . '"><button class="btn sm' . ($c['link'][2] ? '' : ' primary') . '">' . ($c['link'][2] ? 'Geri al' : 'Onayla') . '</button></form>';
            } elseif ($c['link'] && $c['status'] !== 'ok') {
                echo '<a class="btn sm" href="' . admin_url($c['link'][0]) . '">Aç →</a>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }
    afoot();
}
