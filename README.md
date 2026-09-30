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
`assets/video/` altında 4 döngü klip (hero, silk, liquid, mist), her biri MP4 (H.264) + WebM (VP9) + poster JPG.
Klipler Higgsfield / Veo 3.1 Lite ile üretildi (4 sn, sessiz, klip başı 6 kredi), ileri+geri döngüye alındı.
Siyah zeminli tek renkli olduklarından CSS (`.vbg`) ile aktif temanın rengine boyanır. Görünür alana
girince yüklenir, çıkınca durur; "azaltılmış hareket" ve veri tasarrufu modunda oynatılmaz.
