# Uzman Cosmetic — Site ve Yönetim Paneli (cPanel kurulumu)

PHP + SQLite ile çalışır. **Ek sunucu / VDS / MySQL gerekmez** — normal cPanel hosting yeterli.

## Gereksinimler
PHP 7.4 veya üzeri (8.x önerilir) · `pdo_sqlite`, `mbstring`, `gd`, `fileinfo` eklentileri · Apache (`mod_rewrite`).
cPanel → **Select PHP Version** ile kontrol edilir; kurulum ekranı eksikleri de listeler.

## Yükleme (iki yol)
**A) ZIP ile (en kolay)**
1. Bilgisayarda: `python3 tools/make_zip.py` → `dist/uzman-site.zip`
2. cPanel → **Dosya Yöneticisi** → hedef klasör (ör. `public_html/uzmanp`) → **Yükle** → zip'i seçin → **Extract**.
3. Tarayıcıda `…/uzmanp/admin/` açın → **Kurulum** ekranında yönetici kullanıcı adı ve şifre belirleyin. Bitti.

**B) cPanel Git ile (sürekli güncelleme)**
`.cpanel.yml` içindeki `DEPLOYPATH` satırını düzenleyin (kullanıcı adı + klasör). cPanel → **Git™ Version Control** → depoyu ekleyin → **Deploy HEAD Commit**.
Dağıtım `data/` (veritabanı) ve `uploads/` (yüklemeler) klasörlerine dokunmaz.

> Alt klasörde (`/uzmanp`) de, ana alan adında da aynı paket çalışır; bağlantılar göreli.

## İlk kurulumdan sonra (Panel → Yapılacaklar listesi)
1. **Ayarlar** → telefon / e-posta / Instagram / WhatsApp, **SMTP** (cPanel → E-posta Hesapları → *Bağlantı Ayarları*) ve **Araçlar → Test e-postası**.
2. **Belgeler / sertifikalar** → güncel sertifikaları yükleyin (boşsa Kalite sayfası "talep üzerine" notu gösterir).
3. **Gizlilik / KVKK** sayfasını hukuk danışmanınıza onaylatın (şablon metindir).
4. **Ayarlar → Görünüm** → site temasını seçin; tema değiştiriciyi yayında kapalı tutun.
5. Canlıya alırken **Ayarlar → SEO → Site adresi** alanına `https://uzmancosmetic.com` yazın ve dizinlemeye izin verin; test sitesinde dizinlemeyi kapalı tutun.

## Panelde neler düzenlenir
| Menü | İçerik |
|---|---|
| Sayfalar | Menü/alt bilgi konumu, SEO başlık-açıklama; **özel sayfalar** blok editörüyle (metin, adımlar, kartlar, SSS, görsel+metin, belgeler) 5 dilde |
| Site metinleri | Ana sayfa, Private Label, Kurumsal, İletişim… tüm sabit metinler (TR/EN/FR/AR/RU) |
| Ürünler | Body Care / Home Care ağacı: alt kategori, boyutlar, ürün görselleri (yükle, sırala, yayından kaldır) |
| Private Label tüpler | Alüminyum tüp galerisi: model, ölçü, grup, görsel |
| Ana sayfa slider | Hero'daki dönen şişe görselleri |
| Belgeler | Sertifika / belge galerisi (görsel + PDF) |
| Medya | Yüklemeler (otomatik WebP + küçültme) |
| Başvurular | İletişim formundan gelen teklif/numune talepleri, durum, CSV |
| SEO & GEO | **Skor (0–100) + öncelikli öneriler**, sayfa bazlı durum, yapısal veri (JSON-LD), adres/koordinat, `robots.txt` (yapay zekâ botlarına izin), `llms.txt`, doğrulama kodları, çıktı önizlemesi |
| Ayarlar / Kullanıcılar / Araçlar | İletişim, tema, SMTP, SEO; kullanıcılar; önbellek, **yedek**, sistem bilgisi |

Her kayıtta sayfa önbelleği otomatik temizlenir.

## SEO & GEO modülü
Panel → **SEO & GEO**. "Yeniden tara" tüm yayındaki sayfaları 5 dilde üretip 35 kontrol yapar (Teknik SEO, İçerik, GEO — yapay zekâ aramaları, Coğrafi/yerel) ve skorla birlikte iyileştirme önerilerini etki sırasına dizer. Hiçbir veri dışarı gönderilmez.
Ayarlar sekmesindeki bilgilerden her sayfaya JSON-LD (Organization, WebSite, BreadcrumbList, WebPage/FAQPage, ItemList), `/robots.txt`, `/llms.txt`, `/llms-full.txt` ve `sitemap.xml` otomatik üretilir.

## Güncelleme (mevcut kurulum)
Yeni sürümün dosyalarını aynı klasöre çıkarın (`data/` ve `uploads/` korunur). Veritabanı ilk istekte kendini yükseltir (`schema_version`); elle bir şey yapmanız gerekmez.

## Güvenlik notları
- Şifreler `password_hash` ile saklanır; giriş denemeleri sınırlıdır; tüm formlarda CSRF koruması vardır.
- Yükleme klasöründe betik çalışmaz; yüklenen görseller yeniden kodlanır.
- `data/` ve `app/` klasörleri `.htaccess` ile dışarıya kapalıdır. Veritabanı: `data/site.sqlite`.
  İsterseniz `data/config.php` içine `'data_dir' => '/home/KULLANICI/uzman_data'` ekleyip veritabanını web kökünün dışına taşıyabilirsiniz.
- Düzenli **Araçlar → Yedek** indirin (veritabanı + yüklemeler).

## Yerel geliştirme
```
python3 tools/export_seed.py        # (yalnızca varsayılan içerik değişirse) app/seed/seed.json'ı yeniden üretir
php tools/install_cli.php admin 'Sifre-12345!'
php -S 127.0.0.1:8766 tools/dev-router.php
```
`build/` klasörü eski statik üretici (referans); canlı sistem `app/` + `admin/` + `index.php`'dir.
