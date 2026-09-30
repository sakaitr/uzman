# Uzman Cosmetic — web sitesi

Statik, çok dilli site (TR kökte; EN/FR/AR/RU alt klasörlerde).

## İçerik nasıl değişir
- Metinler: `build/i18n.py` (her anahtar için 5 dil: tr, en, fr, ar, ru)
- Ürün ağacı ve görsel eşleşmeleri: `build/catalog.py`
- Sayfa düzeni: `build/build.py`
- Tasarım / tema: `assets/css/style.css`, davranış: `assets/js/main.js`

Değişiklikten sonra sayfaları yeniden üretin (Python 3 + Pillow gerekir):

    pip install pillow
    python3 build/build.py

Yeni ürün görseli eklemek için WebP'yi `assets/img/p/` içine koyun ve `catalog.py` içindeki
ilgili `None` değerini dosya adıyla (uzantısız) değiştirin.

Görseller uzmancosmetic.com'daki mevcut siteden alınmıştır.

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
