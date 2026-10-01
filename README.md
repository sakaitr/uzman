# Uzman Cosmetic — web sitesi + yönetim paneli

PHP + SQLite, cPanel'de ek sunucu olmadan çalışır. Kurulum ve panel kullanımı: **[KURULUM.md](KURULUM.md)**.

```
index.php        ön yüz denetleyicisi (sayfalar, sitemap.xml, robots.txt, sayfa önbelleği)
app/             çekirdek (veritabanı, görünüm, güvenlik, e-posta) + app/admin/ panel modülleri + app/seed/seed.json (varsayılan içerik)
admin/           yönetim paneli (giriş, kurulum sihirbazı, admin.css/js)
api/             form uçları (token + teklif/numune talebi)
assets/          tasarım: css, js, fontlar, görseller, arka plan videoları
tools/           export_seed.py, make_zip.py, install_cli.php, dev-router.php
build/           eski statik üretici (referans; canlı sistemde kullanılmaz)
```

## Temalar
`noir` (varsayılan), `bordeaux`, `emerald`, `twotone` (koyu yeşil zemin + bordo), `inverse` (bordo zemin + yeşil).
Sayfayı belirli bir temayla açmak için `?theme=twotone` eklenir; seçim tarayıcıda hatırlanır.

## Ana sayfa videoları
`assets/video/` altında 4 döngü klip (hero: parfüm sisi, petals: yasemin, fill: şişeye dolan sıvı, line: aerosol dolum hattı), her biri MP4 (H.264) + WebM (VP9) + poster JPG.
Klipler Higgsfield ile üretildi: atmosferik sahneler Seedance 2.0 Fast (6 sn), şişe ve fabrika sahneleri Veo 3.1 Lite (8 sn); sessiz, klip başı 12-15 kredi. Çapraz geçişli (ileri akışı koruyan) döngüye alındı.
Siyah zeminli tek renkli olduklarından CSS (`.vbg`) ile aktif temanın rengine boyanır. Görünür alana
girince yüklenir, çıkınca durur; "azaltılmış hareket" ve veri tasarrufu modunda oynatılmaz.

## Tipografi
Yazı tipleri kendi sunucumuzdan sunulur (`assets/fonts/`, `assets/css/fonts.css`; Google'a istek yok):
Cormorant Garamond (başlık), Manrope (arayüz), Tajawal + Noto Naskh Arabic (Arapça). Latin, Latin-ext, Kiril ve Arapça alt kümeleri.
