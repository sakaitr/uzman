<?php
declare(strict_types=1);

function admin_settings(): void
{
    $fanKeys = ['fan_body' => 'Body Care kartı (3 görsel)', 'fan_home' => 'Home Care kartı (3 görsel)', 'fan_pw' => 'Ana sayfa "Güç" bölümü (3 görsel)', 'fan_about' => 'Kurumsal sayfası (3 görsel)'];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $errs = [];
        $text = ['site_name', 'company_legal', 'phone1', 'phone2', 'fax'];
        foreach ($text as $k) {
            set_setting($k, post_str($k, 160));
        }
        $email = post_str('email', 160);
        $notify = post_str('notify_email', 160);
        $from = post_str('mail_from', 160);
        foreach (['email' => $email, 'notify_email' => $notify, 'mail_from' => $from] as $k => $v) {
            if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errs[] = "$k geçerli bir e-posta değil.";
            } else {
                set_setting($k, $v);
            }
        }
        $ig = post_str('instagram', 200);
        set_setting('instagram', preg_match('#^https?://#', $ig) || $ig === '' ? $ig : 'https://www.instagram.com/' . ltrim($ig, '@/'));
        set_setting('whatsapp', preg_replace('/\D/', '', post_str('whatsapp', 30)));
        foreach (['theme_switcher', 'hero_video', 'robots_index'] as $k) {
            set_setting($k, ($_POST[$k] ?? '0') === '1' ? '1' : '0');
        }
        $su = post_str('site_url', 200);
        if ($su !== '' && !preg_match('#^https?://[^\s/]+(/[^\s]*)?$#', $su)) {
            $errs[] = 'Site adresi https://… biçiminde olmalı.';
        } else {
            set_setting('site_url', rtrim($su, '/'));
        }
        set_setting('smtp_host', post_str('smtp_host', 120));
        set_setting('smtp_port', (string)max(1, min(65535, (int)($_POST['smtp_port'] ?? 587))));
        set_setting('smtp_user', post_str('smtp_user', 160));
        if (post_str('smtp_pass', 200) !== '') {
            set_setting('smtp_pass', (string)$_POST['smtp_pass']);
        }
        set_setting('smtp_secure', in_array($_POST['smtp_secure'] ?? '', ['tls', 'ssl', 'none'], true) ? $_POST['smtp_secure'] : 'tls');
        foreach (array_keys($fanKeys) as $fk) {
            $imgs = array_map('safe_path', array_slice((array)($_POST[$fk] ?? []), 0, 3));
            set_setting($fk, je(array_values($imgs)));
        }
        when_saved();
        $errs ? flash(implode(' ', $errs), 'err') : flash('Ayarlar kaydedildi.');
        redirect_to('settings');
    }
    ahead('Ayarlar', 'settings', 'İletişim bilgileri, görünüm, e-posta ve SEO');
    $v = function (string $k) { return (string)setting($k, ''); };
    echo '<form method="post" data-guard>' . csrf_field();
    echo '<div class="card"><h2>Firma ve iletişim</h2><div class="grid g2">' . field('Site adı', 'site_name', $v('site_name')) . field('Resmi unvan', 'company_legal', $v('company_legal')) . field('Telefon 1', 'phone1', $v('phone1')) . field('Telefon 2', 'phone2', $v('phone2'))
        . field('Faks', 'fax', $v('fax')) . field('Genel e-posta (sitede görünür)', 'email', $v('email'), 'email') . field('Instagram adresi', 'instagram', $v('instagram'), 'text', 'Örn: https://www.instagram.com/uzmancosmetic/')
        . field('WhatsApp numarası', 'whatsapp', $v('whatsapp'), 'text', 'Ülke koduyla, boşluksuz: 905551112233 — doluysa sağ altta yüzen buton çıkar.') . '</div></div>';
    echo '<div class="card"><h2>Görünüm</h2><p class="hint" style="margin-top:-8px">Marka adı, logo ve renk paleti için <a href="' . admin_url('brand') . '">Marka ve görünüm</a> sayfasını kullanın.</p>' . checkbox('theme_switcher', $v('theme_switcher') === '1', 'Ziyaretçilere tema değiştirici göster (yalnızca değerlendirme için; yayında kapalı önerilir)')
        . checkbox('hero_video', $v('hero_video') !== '0', 'Arka plan videolarını göster') . '</div>';
    echo '<div class="card"><h2>Ana sayfa ve sayfa görselleri</h2><p class="hint" style="margin-top:-8px">Kartlarda yelpaze şeklinde duran üçlü ürün görselleri. Hero slider için <a href="' . admin_url('hero') . '">Ana sayfa slider</a> bölümünü kullanın.</p><div class="grid g2">';
    foreach ($fanKeys as $fk => $label) {
        $cur = array_pad(array_values(jd($v($fk), [])), 3, '');
        echo '<div><strong style="font-size:.9rem">' . h($label) . '</strong>';
        for ($i = 0; $i < 3; $i++) {
            echo img_input($fk . '[]', (string)$cur[$i], 'Görsel ' . ($i + 1));
        }
        echo '</div>';
    }
    echo '</div></div>';
    echo '<div class="card"><h2>Form bildirimleri (e-posta)</h2><p class="hint" style="margin-top:-8px">Teklif formu doldurulduğunda bu adrese e-posta gider. Başvurular her durumda Başvurular menüsüne de kaydedilir.</p><div class="grid g2">'
        . field('Bildirimin gideceği adres', 'notify_email', $v('notify_email'), 'email') . field('Gönderen adresi (From)', 'mail_from', $v('mail_from'), 'email', 'Boşsa SMTP kullanıcısı ya da noreply@alanadı kullanılır.')
        . field('SMTP sunucu', 'smtp_host', $v('smtp_host'), 'text', 'cPanel → E-posta Hesapları → Bağlantı Ayarları. Boş bırakırsanız PHP mail() kullanılır.')
        . field('SMTP port', 'smtp_port', $v('smtp_port'), 'number') . field('SMTP kullanıcı', 'smtp_user', $v('smtp_user'))
        . '<div class="field"><label class="l">SMTP şifre</label><input type="password" name="smtp_pass" autocomplete="new-password" placeholder="' . ($v('smtp_pass') !== '' ? '(kayıtlı — değiştirmek için yazın)' : '') . '"></div>
<div class="field"><label class="l">Güvenlik</label><select name="smtp_secure">';
    foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL/TLS (465)', 'none' => 'Şifresiz (önerilmez)'] as $k => $n) {
        echo '<option value="' . $k . '"' . ($v('smtp_secure') === $k ? ' selected' : '') . '>' . h($n) . '</option>';
    }
    echo '</select></div></div><a class="btn sm" href="' . admin_url('tools') . '#mailtest">Test e-postası gönder →</a></div>';
    echo '<div class="card"><h2>SEO</h2>' . field('Site adresi (canonical)', 'site_url', $v('site_url'), 'text', 'Örn: https://uzmancosmetic.com — canlıya alırken girin. Boşsa o an açılan adres kullanılır (test alan adında yayınlarken boş bırakın).')
        . checkbox('robots_index', $v('robots_index') !== '0', 'Arama motorlarının siteyi dizinlemesine izin ver (test sitesinde kapatın)') . '</div>';
    echo '<div class="savebar"><button class="btn primary">Kaydet</button></div></form>';
    afoot();
}
