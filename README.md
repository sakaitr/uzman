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
