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

## Büyüme merkezi (SEO + GEO + reklam aksiyonları ve raporlama)
Panel → **Büyüme** grubu:
- **Büyüme panosu:** son 30 gün ziyaret, başvuru, dönüşüm oranı, yapay zekâdan gelen ziyaret, kanal kırılımı, reklam harcaması, açık aksiyonlar.
- **Aksiyonlar:** planlandı / yapılıyor / tamamlandı panosu. SEO & GEO önerileri tek tıkla aksiyona dönüşür; **B2B üretici için 24 hazır aksiyon** (Search Console, Google İşletme Profili, Alibaba, LinkedIn, Google Ads, katalog PDF…) kütüphanesi vardır. Tarama bir önerinin çözüldüğünü görünce aksiyonu otomatik "tamamlandı"ya alır. Her aksiyonun hareket geçmişi ve sonuç notu tutulur.
- **Reklamlar:** kampanya kaydı, her dil için UTM bağlantısı, platformdan girilen harcama/tıklama/dönüşüm, başvuru başı maliyet. UTM'li gelen form başvuruları kampanyaya otomatik bağlanır.
- **Raporlar:** aylık rapor (önceki ayla karşılaştırma, kanal, sayfa, kampanya, tamamlanan aksiyonlar, SEO skoru), CSV/PDF, e-postayla gönderim.
- **İzleme ve otomasyon:** çerezsiz kendi ziyaret sayacı (kişisel veri tutmaz, Do-Not-Track'e uyar), isteğe bağlı GTM / GA4 / Google Ads / Meta Pixel (çerez onay bandıyla), cPanel **cron** komutu: haftalık SEO taraması + ayın 1'inde aylık rapor e-postası.

## Marka ve görünüm (beyaz etiket)
Panel → **Marka ve görünüm**: marka adı (üst menü, alt bilgi, sekme başlığı), **logo** ve **renk paleti** panelden yönetilir; başka bir müşteriye geçmek için dosya düzenlemek gerekmez.
- **Logo:** PNG/JPG/WebP/SVG yükleyin. Şeffaf PNG/SVG en iyisidir; opak görsellerde arka plan otomatik ayıklanır. İki biçim: temanın vurgu rengiyle boyanan tek renk (varsayılan) ya da orijinal renkler. Favicon ve uygulama ikonu otomatik üretilir (SVG yüklenirse favicon için PNG gerekir).
- **Renkler:** 10 hazır palet (koyu ve açık) ya da 4 renk seçerek özel palet (zemin, yazı, vurgu, ikinci vurgu). Ara tonlar otomatik türetilir, erişilebilirlik (kontrast) kontrolü canlı gösterilir. Açık zeminli paletlerde arka plan videoları gizlenir. Yönetim paneli de marka vurgu rengine ve adına uyar.
- Yazı tipleri bu sürümde sabittir.

## Canlıya alma (go-live)
Panel → **Canlıya alma kontrolü**: 35'e yakın otomatik kontrol (HTTPS, dışarıdan erişilebilir dosya testi, PHP/eklentiler, SMTP ve test e-postası, cron, yedek, içerik, SEO) ve elle onaylanacak maddeler (KVKK onayı, eski site yedeği, DNS, içerik onayı, gerçek form testi, Search Console). Yeni kurulum **test modunda** (arama motorlarına kapalı) açılır; canlıya geçerken:
1. Ayarlar → SEO: **Site adresi** = `https://alanadi.com`, "dizinlemeye izin ver", "HTTPS'e yönlendir" ve "alan adına yönlendir" kutularını işaretleyin. (Kilitlenirseniz adrese `?noredirect=1` ekleyin.)
2. Ayarlar → Form bildirimleri: bildirim adres(ler)i (virgülle birden fazla) ve SMTP; Araçlar → test e-postası.
3. cPanel → Cron Jobs: İzleme ve otomasyon sayfasındaki komut (haftalık tarama + yedek + aylık rapor).
4. Kullanıcılar: kişiye özel kullanıcı adı, her hesaba e-posta (şifre sıfırlama), gerekirse **Editör** rolü (içerik/SEO/büyüme; sistem ayarlarına erişemez).
5. Araçlar: ilk yedeği alın ve ZIP yedeğini bilgisayarınıza indirin.

Diğer yönetim özellikleri: etkinlik günlüğü (Kullanıcılar), yayında olmayan sayfaları **önizleme**, sayfa adresi değişince otomatik **301 yönlendirme** (Sayfalar → Yönlendirmeler), kullanımdaki görselin silinmesini engelleme, Site metinleri **CSV** dışa/içe aktarma (çeviri ajansı için), oturum 8 saat hareketsizlikte kapanır, PHP hataları ekrana basılmaz (`data/logs/php-error.log`).

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
