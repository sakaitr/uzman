# -*- coding: utf-8 -*-
"""UI strings for the Uzman Cosmetic site. Order of values: tr, en, fr, ar, ru."""

LANGS = ["tr", "en", "fr", "ar", "ru"]
LANG_LABEL = {"tr": "TR", "en": "EN", "fr": "FR", "ar": "AR", "ru": "RU"}
LANG_NAME = {"tr": "Türkçe", "en": "English", "fr": "Français", "ar": "العربية", "ru": "Русский"}
HTML_LANG = {"tr": "tr", "en": "en", "fr": "fr", "ar": "ar", "ru": "ru"}
RTL = {"ar"}
UNIT = {"tr": "ml", "en": "ml", "fr": "ml", "ar": "مل", "ru": "мл"}

S = {}


def add(key, tr, en, fr, ar, ru):
    S[key] = dict(zip(LANGS, (tr, en, fr, ar, ru)))


# ---------------------------------------------------------------- global / chrome
add("top_loc", "ÇAYIROVA / KOCAELİ", "ÇAYIROVA / KOCAELI, TÜRKİYE", "ÇAYIROVA / KOCAELI, TURQUIE",
    "تشايروفا / قوجه إيلي، تركيا", "ЧАЙЫРОВА / КОДЖАЕЛИ, ТУРЦИЯ")
add("top_since", "1978'DEN BERİ", "SINCE 1978", "DEPUIS 1978", "منذ عام 1978", "С 1978 ГОДА")
add("top_export", "80 ÜLKEYE İHRACAT", "EXPORTING TO 80 COUNTRIES", "EXPORT DANS 80 PAYS",
    "نصدّر إلى 80 دولة", "ЭКСПОРТ В 80 СТРАН")
add("brand_sub", "COSMETIC · SINCE 1978", "COSMETIC · SINCE 1978", "COSMETIC · DEPUIS 1978",
    "COSMETIC · 1978", "COSMETIC · С 1978")
add("nav_pl", "Private Label", "Private Label", "Private Label", "العلامة الخاصة", "Private Label")
add("nav_products", "Ürünler", "Products", "Produits", "المنتجات", "Продукция")
add("nav_about", "Kurumsal", "About Us", "Entreprise", "من نحن", "О компании")
add("nav_contact", "İletişim", "Contact", "Contact", "اتصل بنا", "Контакты")
add("nav_quote", "Teklif Al", "Get a Quote", "Demander un devis", "اطلب عرض سعر", "Запросить цену")
add("aria_menu", "Menüyü aç", "Open menu", "Ouvrir le menu", "افتح القائمة", "Открыть меню")
add("aria_theme", "Renk teması", "Colour theme", "Thème de couleur", "سمة الألوان", "Цветовая тема")
add("aria_lang", "Dil", "Language", "Langue", "اللغة", "Язык")
add("aria_home", "Uzman Cosmetic — ana sayfa", "Uzman Cosmetic — home", "Uzman Cosmetic — accueil",
    "Uzman Cosmetic — الصفحة الرئيسية", "Uzman Cosmetic — главная")
add("explore", "İncele", "Explore", "Découvrir", "استكشف", "Смотреть")
add("skip", "İçeriğe geç", "Skip to content", "Aller au contenu", "انتقل إلى المحتوى", "К содержимому")
add("close", "Kapat", "Close", "Fermer", "إغلاق", "Закрыть")
add("soon", "Görsel yakında", "Image coming soon", "Visuel à venir", "الصورة قريباً", "Фото скоро")
add("all", "Tümü", "All", "Tout", "الكل", "Все")

# footer
add("foot_blurb",
    "1978'de İstanbul'da kurulan Uzman Kozmetik; body care ve home care ürünlerinde fason ve private label üretim yapar.",
    "Founded in Istanbul in 1978, Uzman Cosmetic manufactures body care and home care products under contract and private label.",
    "Fondée à Istanbul en 1978, Uzman Cosmetic fabrique des produits de soin du corps et de parfumerie d'intérieur en sous-traitance et en marque blanche.",
    "تأسست أوزمان كوزمتيك في إسطنبول عام 1978، وتصنّع منتجات العناية بالجسم والعناية بالمنزل للعلامات الخاصة وبالتصنيع للغير.",
    "Основанная в Стамбуле в 1978 году, Uzman Cosmetic производит средства для ухода за телом и для дома по контракту и под частной маркой.")
add("foot_discover", "Keşfet", "Discover", "Découvrir", "اكتشف", "Разделы")
add("foot_products", "Ürünler", "Products", "Produits", "المنتجات", "Продукция")
add("foot_hq", "Merkez & Fabrika", "Head Office & Factory", "Siège & usine", "المقر والمصنع", "Офис и фабрика")
add("foot_addr", "Şekerpınar Mh. Özbek Sk. No:4<br>Çayırova / Kocaeli, Türkiye",
    "Şekerpınar Mh. Özbek Sk. No:4<br>Çayırova / Kocaeli, Türkiye",
    "Şekerpınar Mh. Özbek Sk. No:4<br>Çayırova / Kocaeli, Turquie",
    "Şekerpınar Mh. Özbek Sk. No:4<br>تشايروفا / قوجه إيلي، تركيا",
    "Şekerpınar Mh. Özbek Sk. No:4<br>Чайырова / Коджаэли, Турция")
add("foot_rights", "© 1978 – 2026 UZMAN KOZMETİK. TÜM HAKLARI SAKLIDIR.",
    "© 1978 – 2026 UZMAN COSMETIC. ALL RIGHTS RESERVED.",
    "© 1978 – 2026 UZMAN COSMETIC. TOUS DROITS RÉSERVÉS.",
    "© 1978 – 2026 أوزمان كوزمتيك. جميع الحقوق محفوظة.",
    "© 1978 – 2026 UZMAN COSMETIC. ВСЕ ПРАВА ЗАЩИЩЕНЫ.")

# ---------------------------------------------------------------- product tree names
add("cat_body", "Body Care", "Body Care", "Soins du corps", "العناية بالجسم", "Уход за телом")
add("cat_home", "Home Care", "Home Care", "Parfums d'intérieur", "العناية بالمنزل", "Уход за домом")
add("p_deo", "Deodorant", "Deodorant", "Déodorant", "مزيل العرق", "Дезодорант")
add("p_rollon", "Roll-on", "Roll-on", "Roll-on", "رول أون", "Роликовый дезодорант")
add("p_hair", "Saç Ürünleri", "Hair Products", "Soins capillaires", "منتجات الشعر", "Средства для волос")
add("p_edp", "EDP Parfüm", "EDP Perfume", "Eau de Parfum", "ماء عطر (EDP)", "Парфюмерная вода (EDP)")
add("p_mist", "Body Mist", "Body Mist", "Brume corporelle", "بخاخ الجسم", "Спрей для тела")
add("p_bamboo", "Bambu Difüzör", "Bamboo Diffuser", "Diffuseur en bambou", "موزّع عطور بالخيزران", "Диффузор с бамбуком")
add("p_air", "Air Freshener", "Air Freshener", "Désodorisant d'air", "معطّر الجو", "Освежитель воздуха")
add("p_room", "Room Freshener", "Room Freshener", "Parfum d'ambiance", "معطّر الغرف", "Ароматизатор для помещений")
add("p_reed", "Reed Diffuser", "Reed Diffuser", "Diffuseur à roseaux", "موزّع عطور بالقصب", "Аромадиффузор")
add("h_wax", "Wax", "Wax", "Cire coiffante", "واكس الشعر", "Воск")
add("h_spray", "Saç Spreyi", "Hair Spray", "Laque", "سبراي الشعر", "Лак для волос")
add("h_dry", "Kuru Şampuan", "Dry Shampoo", "Shampooing sec", "شامبو جاف", "Сухой шампунь")
add("h_cond", "Saç Kremi", "Conditioner", "Après-shampooing", "بلسم الشعر", "Кондиционер")
add("h_powder", "Pudra Wax", "Powder Wax", "Cire en poudre", "واكس بودرة", "Пудра-воск")
add("sizes", "Boyutlar", "Sizes", "Formats", "الأحجام", "Объёмы")

add("desc_deo", "Yedi ayrı marka altında üretilen deodorant koleksiyonu.",
    "A deodorant collection produced under seven different brands.",
    "Une collection de déodorants fabriquée sous sept marques.",
    "مجموعة مزيلات عرق تُنتج تحت سبع علامات مختلفة.",
    "Коллекция дезодорантов, выпускаемая под семью разными брендами.")
add("desc_rollon", "Kompakt ve pratik roll-on deodorant.", "Compact, practical roll-on deodorant.",
    "Déodorant roll-on compact et pratique.", "مزيل عرق رول أون عملي وصغير الحجم.",
    "Компактный и удобный роликовый дезодорант.")
add("desc_hair", "Wax, sprey, kuru şampuan, saç kremi ve pudra wax.",
    "Wax, spray, dry shampoo, conditioner and powder wax.",
    "Cire, laque, shampooing sec, après-shampooing et cire en poudre.",
    "واكس وسبراي وشامبو جاف وبلسم وواكس بودرة.",
    "Воск, лак, сухой шампунь, кондиционер и пудра-воск.")
add("desc_edp", "50 ve 100 ml Eau de Parfum seçenekleri.", "Eau de Parfum in 50 and 100 ml.",
    "Eau de Parfum en 50 et 100 ml.", "ماء عطر بحجمي 50 و100 مل.", "Парфюмерная вода 50 и 100 мл.")
add("desc_mist", "Hafif ve kalıcı body mist serileri.", "Light, long-lasting body mist ranges.",
    "Gammes de brumes corporelles légères et tenaces.", "مجموعات بخاخ جسم خفيفة ودائمة.",
    "Лёгкие и стойкие линии спреев для тела.")
add("desc_bamboo", "Bambu çubuklu oda difüzörü.", "Room diffuser with bamboo sticks.",
    "Diffuseur d'ambiance à bâtonnets de bambou.", "موزّع عطور للغرف بأعواد الخيزران.",
    "Комнатный диффузор с бамбуковыми палочками.")
add("desc_air", "Gazlı oda spreyi; onlarca koku seçeneği.", "Aerosol room spray in dozens of fragrances.",
    "Spray d'ambiance aérosol en de nombreuses fragrances.", "بخاخ غرف أيروسول بعشرات الروائح.",
    "Аэрозольный освежитель воздуха с десятками ароматов.")
add("desc_room", "500 ml likit oda kokusu.", "500 ml liquid room freshener.",
    "Parfum d'ambiance liquide de 500 ml.", "معطّر غرف سائل سعة 500 مل.",
    "Жидкий ароматизатор для помещений, 500 мл.")
add("desc_reed", "Çubuklu oda difüzörü; üç boy.", "Reed diffuser in three sizes.",
    "Diffuseur à roseaux en trois formats.", "موزّع عطور بالقصب بثلاثة أحجام.",
    "Аромадиффузор с палочками, три объёма.")

# ---------------------------------------------------------------- index
add("hero_eyebrow", "Fason & Private Label · 1978'den beri", "Contract Manufacturing & Private Label · Since 1978",
    "Sous-traitance & Private Label · Depuis 1978", "التصنيع للغير والعلامة الخاصة · منذ 1978",
    "Контрактное производство и Private Label · с 1978 года")
add("hero_l1", "Markanızın", "Your brand's", "La signature", "توقيعُ", "Подпись")
add("hero_l2", "<em>imzası</em>, bizim", "<em>signature</em>, our", "<em>de votre marque</em>,", "<em>علامتك</em>،", "<em>вашего бренда</em> —")
add("hero_l3", "ustalığımız.", "craftsmanship.", "notre savoir-faire.", "وحِرفتُنا.", "наше мастерство.")
add("hero_lead",
    "1978'de İstanbul'da kurulan Uzman Kozmetik; body care ve home care ürünlerini 80 ülkeye ihraç eden bir üretici olarak, markanıza özel koleksiyonlar hazırlıyor.",
    "Founded in Istanbul in 1978, Uzman Cosmetic manufactures body care and home care products for export to 80 countries — and creates collections tailored to your brand.",
    "Fondée à Istanbul en 1978, Uzman Cosmetic fabrique des produits de soin du corps et de parfumerie d'intérieur exportés dans 80 pays, et crée des collections sur mesure pour votre marque.",
    "تأسست أوزمان كوزمتيك في إسطنبول عام 1978، وهي تصنّع منتجات العناية بالجسم والمنزل وتصدّرها إلى 80 دولة، وتبتكر مجموعات خاصة بعلامتك.",
    "Основанная в Стамбуле в 1978 году, Uzman Cosmetic производит средства для ухода за телом и для дома, экспортирует их в 80 стран и создаёт коллекции специально для вашего бренда.")
add("cta_start", "Projenizi Başlatın", "Start Your Project", "Lancez votre projet", "ابدأ مشروعك", "Начните проект")
add("tag_edp", "<b>E.D.P.</b> Parfüm Serisi", "<b>E.D.P.</b> Perfume Range", "<b>E.D.P.</b> Gamme de parfums",
    "<b>E.D.P.</b> مجموعة العطور", "<b>E.D.P.</b> Парфюмерная линия")
add("tag_80", "<b>80</b> Ülkeye İhracat", "<b>80</b> Export Countries", "<b>80</b> Pays d'exportation",
    "<b>80</b> دولة تصدير", "<b>80</b> стран экспорта")
add("scroll", "KAYDIR", "SCROLL", "DÉFILER", "مرّر", "ВНИЗ")
add("m1", "1978'den beri", "Since 1978", "Depuis 1978", "منذ عام 1978", "С 1978 года")
add("m2", "80 ülkeye ihracat", "Exporting to 80 countries", "Export dans 80 pays", "تصدير إلى 80 دولة", "Экспорт в 80 стран")
add("m3", "60 milyon adet aerosol kapasitesi", "60 million aerosol units capacity", "Capacité de 60 millions d'aérosols",
    "طاقة إنتاج 60 مليون عبوة أيروسول", "Мощность — 60 млн аэрозолей")
add("m4", "50+ alüminyum tüp modeli", "50+ aluminium tube models", "Plus de 50 modèles de tubes aluminium",
    "أكثر من 50 طراز أنبوب ألومنيوم", "50+ моделей алюминиевых баллонов")
add("m5", "Ar-Ge · Tasarım · Grafik", "R&amp;D · Design · Graphics", "R&amp;D · Design · Graphisme",
    "البحث والتطوير · التصميم · الجرافيك", "НИОКР · Дизайн · Графика")
add("m6", "Fason & Private Label", "Contract &amp; Private Label", "Sous-traitance &amp; Private Label",
    "التصنيع للغير والعلامة الخاصة", "Контрактное производство и Private Label")
add("manifesto_eyebrow", "Felsefemiz", "Our Philosophy", "Notre philosophie", "فلسفتنا", "Наша философия")
add("manifesto",
    "İnsan değerlerine, doğanın korunmasına ve müşteri ilişkilerinde <em>güvenilirliğe</em> inanıyoruz. Uluslararası standartlarda teknolojiyle, markanız için <em>dünya standartlarında</em> çözümler üretiyoruz.",
    "We believe in human values, in protecting nature, and in <em>reliability</em> in every customer relationship. With internationally standard technology, we create <em>world-class</em> solutions for your brand.",
    "Nous croyons aux valeurs humaines, à la protection de la nature et à la <em>fiabilité</em> dans la relation client. Grâce à une technologie aux normes internationales, nous créons pour votre marque des solutions <em>de niveau mondial</em>.",
    "نؤمن بالقيم الإنسانية وحماية الطبيعة و<em>الموثوقية</em> في علاقتنا مع العملاء. وبتقنيات وفق المعايير الدولية، نقدّم لعلامتك حلولاً <em>بمستوى عالمي</em>.",
    "Мы верим в человеческие ценности, защиту природы и <em>надёжность</em> в отношениях с клиентами. Используя технологии международного уровня, мы создаём для вашего бренда решения <em>мирового класса</em>.")
add("st_founded", "Kuruluş Yılı", "Founded", "Année de fondation", "سنة التأسيس", "Год основания")
add("st_area", "Kapalı Üretim Alanı", "Indoor Production Area", "Surface de production couverte", "مساحة الإنتاج المغلقة", "Закрытая площадь")
add("st_export", "İhracat Ülkesi", "Export Countries", "Pays d'exportation", "دولة تصدير", "Стран экспорта")
add("st_aerosol", "Aerosol Adet Kapasitesi", "Aerosol Unit Capacity", "Capacité aérosol (unités)", "الطاقة الإنتاجية للأيروسول", "Мощность по аэрозолям")
add("coll_eyebrow", "Ürün Grupları", "Product Groups", "Groupes de produits", "مجموعات المنتجات", "Группы продукции")
add("coll_h2", "İki <em>dünya</em>, tek çatı.", "Two <em>worlds</em>, one roof.", "Deux <em>univers</em>, un seul toit.",
    "عالمان <em>تحت</em> سقف واحد.", "Два <em>мира</em> под одной крышей.")
add("coll_lead", "Kişisel bakımdan ev kokularına; fason ve private label üretim için tam bir ürün ağacı.",
    "From personal care to home fragrance — a complete product tree for contract and private label manufacturing.",
    "Du soin personnel aux parfums d'intérieur : une arborescence complète pour la sous-traitance et la marque blanche.",
    "من العناية الشخصية إلى عطور المنزل: شجرة منتجات متكاملة للتصنيع للغير والعلامة الخاصة.",
    "От ухода за собой до ароматов для дома — полное древо продукции для контрактного производства и Private Label.")
add("pl_eyebrow", "Private Label", "Private Label", "Private Label", "العلامة الخاصة", "Private Label")
add("pl_h2", "Fikirden <em>rafa</em>, adım adım.", "From idea to <em>shelf</em>, step by step.",
    "De l'idée au <em>rayon</em>, étape par étape.", "من الفكرة إلى <em>الرف</em>، خطوة بخطوة.",
    "От идеи до <em>полки</em>, шаг за шагом.")
add("pl_lead", "Ürün, ambalaj ve tasarım; aynı ekiple, tek noktadan.", "Product, packaging and design — one team, one contact.",
    "Produit, packaging et design : une seule équipe, un seul interlocuteur.",
    "المنتج والعبوة والتصميم، مع فريق واحد ونقطة تواصل واحدة.", "Продукт, упаковка и дизайн — одна команда, один контакт.")
add("pl_btn", "Private Label Detayı", "Private Label Details", "Détails Private Label", "تفاصيل العلامة الخاصة", "Подробнее о Private Label")
add("s1_t", "Ürün & Konsept", "Product & Concept", "Produit & concept", "المنتج والمفهوم", "Продукт и концепция")
add("s1_p", "Kategori, koku ve hedef pazar birlikte belirlenir.", "Category, fragrance and target market are defined together.",
    "La catégorie, la fragrance et le marché cible sont définis ensemble.", "نحدد معاً الفئة والرائحة والسوق المستهدف.",
    "Категория, аромат и целевой рынок определяются совместно.")
add("s2_t", "Ambalaj Seçimi", "Packaging Selection", "Choix du packaging", "اختيار العبوة", "Выбор упаковки")
add("s2_p", "50'den fazla alüminyum tüp modeli arasından markanıza uygun form seçilir.",
    "Choose the right shape for your brand from more than 50 aluminium tube models.",
    "Choisissez la forme adaptée à votre marque parmi plus de 50 modèles de tubes aluminium.",
    "اختر الشكل المناسب لعلامتك من بين أكثر من 50 طراز أنبوب ألومنيوم.",
    "Выберите подходящую форму из более чем 50 моделей алюминиевых баллонов.")
add("s3_t", "Ar-Ge, Tasarım & Grafik", "R&amp;D, Design &amp; Graphics", "R&amp;D, design &amp; graphisme", "البحث والتطوير والتصميم والجرافيك", "НИОКР, дизайн и графика")
add("s3_p", "Kendi Ar-Ge, tasarım ve grafik departmanlarımız ürün ve ambalaj çalışmasını yürütür.",
    "Our in-house R&amp;D, design and graphics departments handle the product and packaging work.",
    "Nos départements R&amp;D, design et graphisme internes prennent en charge le produit et le packaging.",
    "تتولى أقسام البحث والتطوير والتصميم والجرافيك لدينا العمل على المنتج والعبوة.",
    "Собственные отделы НИОКР, дизайна и графики ведут работу над продуктом и упаковкой.")
add("s4_t", "Üretim", "Production", "Production", "الإنتاج", "Производство")
add("s4_p", "5.500 m² kapalı alandaki tesisimizde, geniş makine parkuruyla dolum ve paketleme.",
    "Filling and packaging in our 5,500 m² indoor facility with a wide range of machinery.",
    "Remplissage et conditionnement dans notre site couvert de 5 500 m², doté d'un large parc de machines.",
    "تعبئة وتغليف في منشأتنا المغلقة البالغة 5500 م² بأسطول واسع من الماكينات.",
    "Розлив и упаковка на нашей закрытой площадке в 5 500 м² с широким парком оборудования.")
add("s5_t", "Sevkiyat", "Shipping", "Expédition", "الشحن", "Отгрузка")
add("s5_p", "Siparişiniz yurt içine ya da yurt dışına gönderilir.", "Your order ships domestically or abroad.",
    "Votre commande est expédiée en Turquie ou à l'étranger.", "يُشحن طلبك داخل تركيا أو إلى الخارج.",
    "Ваш заказ отправляется по Турции или за рубеж.")
add("pw_eyebrow", "Üretim Gücü", "Production Strength", "Capacité de production", "القدرة الإنتاجية", "Производственная мощность")
add("pw_h2", "1980'den beri <em>aerosol</em> ustalığı.", "<em>Aerosol</em> expertise since 1980.",
    "Un savoir-faire <em>aérosol</em> depuis 1980.", "خبرة في <em>الأيروسول</em> منذ عام 1980.",
    "Опыт в <em>аэрозолях</em> с 1980 года.")
add("pw_lead",
    "1980'de basınçlı kaplar kanununa uygun ilk yarı otomatik aerosol makinemizle üretime başladık. Bugün 5.500 m² kapalı alanda, 60 milyon adet aerosol kapasitesine ulaştık.",
    "In 1980 we began production with our first semi-automatic aerosol machine, compliant with pressure-vessel regulations. Today, in 5,500 m² of indoor space, we have reached a capacity of 60 million aerosol units.",
    "En 1980, nous avons lancé la production avec notre première machine aérosol semi-automatique, conforme à la réglementation sur les récipients sous pression. Aujourd'hui, sur 5 500 m² couverts, nous atteignons une capacité de 60 millions d'aérosols.",
    "بدأنا الإنتاج عام 1980 بأول ماكينة أيروسول شبه آلية مطابقة لقانون الأوعية المضغوطة. واليوم، في مساحة مغلقة تبلغ 5500 م²، وصلت طاقتنا إلى 60 مليون عبوة أيروسول.",
    "В 1980 году мы начали производство на первой полуавтоматической аэрозольной линии, соответствующей закону о сосудах под давлением. Сегодня на закрытой площади 5 500 м² наша мощность достигла 60 млн аэрозолей.")
add("pw_c1", "Son teknoloji dolum sistemine sahip geniş makine parkuru", "A wide machinery park with the latest filling systems",
    "Un large parc de machines équipé des derniers systèmes de remplissage", "أسطول واسع من الماكينات بأحدث أنظمة التعبئة",
    "Широкий парк оборудования с современными системами розлива")
add("pw_c2", "Ar-Ge, tasarım ve grafik departmanları", "R&amp;D, design and graphics departments",
    "Départements R&amp;D, design et graphisme", "أقسام البحث والتطوير والتصميم والجرافيك", "Отделы НИОКР, дизайна и графики")
add("pw_c3", "Türkiye'nin tanınmış markalarına fason üretim deneyimi", "Contract manufacturing experience for well-known Turkish brands",
    "Expérience de sous-traitance pour des marques turques reconnues", "خبرة في التصنيع للغير لعلامات تركية معروفة",
    "Опыт контрактного производства для известных турецких брендов")
add("pw_link", "Firmamızı Tanıyın", "Get to Know Us", "Découvrez l'entreprise", "تعرّف على شركتنا", "О компании")
add("pw_cap", "Aerosol · 60 milyon adet kapasite", "Aerosol · 60 million unit capacity", "Aérosol · capacité de 60 millions",
    "أيروسول · طاقة 60 مليون عبوة", "Аэрозоли · мощность 60 млн")
add("val_eyebrow", "Değerlerimiz", "Our Values", "Nos valeurs", "قيمنا", "Наши ценности")
add("val_h2", "Üç <em>ilke</em>.", "Three <em>principles</em>.", "Trois <em>principes</em>.", "ثلاثة <em>مبادئ</em>.", "Три <em>принципа</em>.")
add("v1_t", "İnsan Değerleri", "Human Values", "Valeurs humaines", "القيم الإنسانية", "Человеческие ценности")
add("v1_p", "Kuruluş ilkelerimizin başında insan değerleri gelir.", "Human values stand first among our founding principles.",
    "Les valeurs humaines figurent en tête de nos principes fondateurs.", "تأتي القيم الإنسانية في صدارة مبادئنا التأسيسية.",
    "Человеческие ценности стоят во главе наших основополагающих принципов.")
add("v2_t", "Doğanın Korunması", "Protecting Nature", "Protection de la nature", "حماية الطبيعة", "Защита природы")
add("v2_p", "Üretimde doğayı koruyan bir yaklaşım.", "An approach to production that protects nature.",
    "Une approche de la production qui protège la nature.", "نهج في الإنتاج يحمي الطبيعة.", "Подход к производству, бережный к природе.")
add("v3_t", "Güvenilirlik", "Reliability", "Fiabilité", "الموثوقية", "Надёжность")
add("v3_p", "Müşteri ilişkilerinde güvenilir olmak.", "Being dependable in every customer relationship.",
    "Être fiable dans chaque relation client.", "أن نكون موثوقين في كل علاقة مع العملاء.", "Быть надёжными в отношениях с каждым клиентом.")
add("ex_eyebrow", "İhracat", "Export", "Export", "التصدير", "Экспорт")
add("ex_h2", "Dünyanın <em>80 ülkesinde</em>.", "In <em>80 countries</em> worldwide.", "Dans <em>80 pays</em> à travers le monde.",
    "في <em>80 دولة</em> حول العالم.", "В <em>80 странах</em> мира.")
add("ex_lead", "Ürünlerimizi dünya pazarına tanıtmak ve yerleştirmek için her yıl uluslararası fuarlara katılıyoruz.",
    "We take part in international trade fairs every year to introduce our products to the world market.",
    "Nous participons chaque année à des salons internationaux pour faire connaître nos produits sur le marché mondial.",
    "نشارك كل عام في المعارض الدولية للتعريف بمنتجاتنا في الأسواق العالمية.",
    "Каждый год мы участвуем в международных выставках, чтобы представить продукцию на мировом рынке.")
add("ex_f1", "Merkez", "Headquarters", "Siège", "المقر", "Офис")
add("ex_f1s", "ÇAYIROVA", "ÇAYIROVA", "ÇAYIROVA", "تشايروفا", "ЧАЙЫРОВА")
add("ex_f2", "İhracat", "Export", "Export", "التصدير", "Экспорт")
add("ex_f2s", "80 ÜLKE", "80 COUNTRIES", "80 PAYS", "80 دولة", "80 СТРАН")
add("ex_f3", "Fuarlar", "Trade Fairs", "Salons", "المعارض", "Выставки")
add("ex_f3s", "HER YIL", "EVERY YEAR", "CHAQUE ANNÉE", "كل عام", "КАЖДЫЙ ГОД")
add("ex_f4", "Diller", "Languages", "Langues", "اللغات", "Языки")
add("ex_f4s", "TR · EN · FR · AR · RU", "TR · EN · FR · AR · RU", "TR · EN · FR · AR · RU", "TR · EN · FR · AR · RU", "TR · EN · FR · AR · RU")
add("cta_eyebrow", "Birlikte Başlayalım", "Let's Begin Together", "Commençons ensemble", "لنبدأ معاً", "Начнём вместе")
add("cta_h2", "Koleksiyonunuzu <em>birlikte</em> yazalım.", "Let's write your collection <em>together</em>.",
    "Écrivons votre collection <em>ensemble</em>.", "لنكتب مجموعتك <em>معاً</em>.", "Создадим вашу коллекцию <em>вместе</em>.")
add("cta_lead", "Projenizi anlatın; ekibimiz numune ve teklif için sizinle iletişime geçsin.",
    "Tell us about your project and our team will contact you about samples and a quote.",
    "Parlez-nous de votre projet ; notre équipe vous contactera pour les échantillons et un devis.",
    "أخبرنا عن مشروعك وسيتواصل معك فريقنا بخصوص العينات وعرض السعر.",
    "Расскажите о проекте — наша команда свяжется с вами по образцам и стоимости.")
add("cta_btn", "Teklif İsteyin", "Request a Quote", "Demander un devis", "اطلب عرض سعر", "Запросить расчёт")

# ---------------------------------------------------------------- products
add("pr_h1", "Ürün <em>ağacı</em>.", "The product <em>tree</em>.", "L'<em>arbre</em> des produits.", "شجرة <em>المنتجات</em>.", "<em>Древо</em> продукции.")
add("pr_lead", "İki ana grup, onlarca ürün. Her kategorinin ambalaj boyutlarını ve örnek ürünlerini inceleyin.",
    "Two main groups, dozens of products. Browse the pack sizes and sample products of every category.",
    "Deux grands groupes, des dizaines de produits. Découvrez les formats et des exemples de chaque catégorie.",
    "مجموعتان رئيسيتان وعشرات المنتجات. تصفّح أحجام العبوات ونماذج المنتجات في كل فئة.",
    "Две основные группы и десятки продуктов. Изучите объёмы и образцы в каждой категории.")
add("bc_h1", "<em>Body Care</em> koleksiyonu.", "The <em>Body Care</em> collection.", "La collection <em>Soins du corps</em>.",
    "مجموعة <em>العناية بالجسم</em>.", "Коллекция <em>Body Care</em>.")
add("bc_lead", "Deodorant, roll-on, saç ürünleri, parfüm ve body mist.",
    "Deodorant, roll-on, hair products, perfume and body mist.",
    "Déodorant, roll-on, soins capillaires, parfum et brume corporelle.",
    "مزيل العرق، رول أون، منتجات الشعر، العطور وبخاخ الجسم.",
    "Дезодорант, роллер, средства для волос, парфюм и спрей для тела.")
add("hc_h1", "<em>Home Care</em> koleksiyonu.", "The <em>Home Care</em> collection.", "La collection <em>Parfums d'intérieur</em>.",
    "مجموعة <em>العناية بالمنزل</em>.", "Коллекция <em>Home Care</em>.")
add("hc_lead", "Bambu difüzör, oda spreyleri ve reed diffuser.",
    "Bamboo diffuser, room sprays and reed diffusers.",
    "Diffuseur en bambou, sprays d'ambiance et diffuseurs à roseaux.",
    "موزّع الخيزران وبخاخات الغرف وموزّعات القصب.",
    "Диффузор с бамбуком, освежители воздуха и аромадиффузоры.")
add("back_tree", "← Ürün ağacı", "← Product tree", "← Arbre des produits", "← شجرة المنتجات", "← Древо продукции")

# ---------------------------------------------------------------- private label page
add("pl_h1", "Sizin markanız, <em>bizim</em> atölyemiz.", "Your brand, <em>our</em> atelier.", "Votre marque, <em>notre</em> atelier.",
    "علامتك، <em>وورشتنا</em>.", "Ваш бренд — <em>наша</em> мастерская.")
add("pl_page_lead", "Body care ve home care ürünlerinde markanıza özel üretim; 50'den fazla alüminyum tüp seçeneğiyle.",
    "Custom production of body care and home care products for your brand — with more than 50 aluminium tube options.",
    "Production sur mesure de soins du corps et de parfums d'intérieur pour votre marque, avec plus de 50 modèles de tubes aluminium.",
    "إنتاج مخصص لمنتجات العناية بالجسم والمنزل لعلامتك، مع أكثر من 50 خياراً من أنابيب الألومنيوم.",
    "Индивидуальное производство средств для тела и дома для вашего бренда — с более чем 50 вариантами алюминиевых баллонов.")
add("pl_proc_eyebrow", "Süreç", "Process", "Processus", "المراحل", "Процесс")
add("pl_proc_h2", "Beş adım, <em>tek</em> ekip.", "Five steps, <em>one</em> team.", "Cinq étapes, <em>une</em> équipe.", "خمس خطوات، <em>فريق</em> واحد.", "Пять шагов, <em>одна</em> команда.")
add("pl_proc_lead", "Ürünün konseptinden sevkiyatına kadar aynı muhatapla ilerleyin.", "From concept to shipping, work with the same contact.",
    "Du concept à l'expédition, avancez avec le même interlocuteur.", "من المفهوم إلى الشحن، مع جهة اتصال واحدة.",
    "От концепции до отгрузки — с одним и тем же контактным лицом.")
add("tube_eyebrow", "Alüminyum Tüp Kataloğu", "Aluminium Tube Catalogue", "Catalogue de tubes aluminium", "كتالوج أنابيب الألومنيوم", "Каталог алюминиевых баллонов")
add("tube_h2", "50+ <em>tüp</em> modeli", "50+ <em>tube</em> models", "Plus de 50 <em>modèles</em> de tubes", "أكثر من 50 طراز <em>أنبوب</em>", "50+ моделей <em>баллонов</em>")
add("tube_lead", "Deodorant, body spray, parfüm ve saç bakım ürünleri için; ölçüler milimetre cinsindendir (çap/genişlik × yükseklik).",
    "For deodorants, body sprays, perfumes and hair care; dimensions in millimetres (diameter/width × height).",
    "Pour déodorants, body sprays, parfums et soins capillaires ; dimensions en millimètres (diamètre/largeur × hauteur).",
    "لمزيلات العرق وبخاخات الجسم والعطور ومنتجات الشعر؛ الأبعاد بالمليمتر (القطر/العرض × الارتفاع).",
    "Для дезодорантов, спреев, парфюма и средств для волос; размеры в миллиметрах (диаметр/ширина × высота).")
add("t_round", "Yuvarlak", "Round", "Rond", "دائري", "Круглые")
add("t_flat", "Yassı", "Flat", "Plat", "مسطّح", "Плоские")
add("t_ogival", "Ogival", "Ogival", "Ogival", "أوجيفال", "Оживальные")
add("t_square", "Kare Yassı", "Square Flat", "Plat carré", "مسطّح مربّع", "Квадратные плоские")
add("t_transfer", "Transfer", "Transfer", "Transfer", "ترانسفر", "Transfer")
add("t_special", "Özel Formlar", "Special Shapes", "Formes spéciales", "أشكال خاصة", "Особые формы")

# ---------------------------------------------------------------- about
add("ab_h1", "Kırk yılı aşkın <em>üretim</em> tecrübesi.", "Over forty years of <em>manufacturing</em> experience.",
    "Plus de quarante ans d'expérience de <em>fabrication</em>.", "أكثر من أربعين عاماً من الخبرة في <em>التصنيع</em>.",
    "Более сорока лет <em>производственного</em> опыта.")
add("ab_lead", "1978'de İstanbul'da kurulan Uzman Kozmetik, bugün Çayırova / Kocaeli'deki tesisinde üretim yapıyor.",
    "Founded in Istanbul in 1978, Uzman Cosmetic now produces at its facility in Çayırova / Kocaeli.",
    "Fondée à Istanbul en 1978, Uzman Cosmetic produit aujourd'hui dans son usine de Çayırova / Kocaeli.",
    "تأسست أوزمان كوزمتيك في إسطنبول عام 1978، وتنتج اليوم في منشأتها في تشايروفا / قوجه إيلي.",
    "Основанная в Стамбуле в 1978 году, Uzman Cosmetic сегодня производит продукцию на фабрике в Чайырове / Коджаэли.")
add("ab_manifesto",
    "Türkiye'nin tanınmış markalarına fason üretimle başladık; bugün <em>80 ülkeye</em> ihracat yapan bir üretici olarak <em>dünya standartlarında</em> çözümler sunuyoruz.",
    "We started with contract manufacturing for well-known Turkish brands; today, as a producer exporting to <em>80 countries</em>, we offer <em>world-class</em> solutions.",
    "Nous avons débuté par la sous-traitance pour des marques turques reconnues ; aujourd'hui, producteur exportant dans <em>80 pays</em>, nous proposons des solutions <em>de niveau mondial</em>.",
    "بدأنا بالتصنيع للغير لعلامات تركية معروفة، واليوم كمصنّع يصدّر إلى <em>80 دولة</em> نقدّم حلولاً <em>بمستوى عالمي</em>.",
    "Мы начинали с контрактного производства для известных турецких брендов; сегодня, экспортируя в <em>80 стран</em>, мы предлагаем решения <em>мирового класса</em>.")
add("ab_journey_eyebrow", "Yolculuk", "Our Journey", "Notre parcours", "مسيرتنا", "Наш путь")
add("ab_journey_h2", "İstanbul'dan <em>dünyaya</em>.", "From Istanbul to <em>the world</em>.", "D'Istanbul au <em>monde</em>.", "من إسطنبول إلى <em>العالم</em>.", "Из Стамбула — <em>в мир</em>.")
add("tl1", "Uzman Kozmetik, İstanbul'da kuruldu.", "Uzman Cosmetic was founded in Istanbul.", "Uzman Cosmetic est fondée à Istanbul.",
    "تأسست أوزمان كوزمتيك في إسطنبول.", "Uzman Cosmetic основана в Стамбуле.")
add("tl2", "Basınçlı kaplar kanununa uygun ilk yarı otomatik aerosol makinesiyle üretime başlandı.",
    "Production began with the first semi-automatic aerosol machine, compliant with pressure-vessel law.",
    "La production démarre avec la première machine aérosol semi-automatique, conforme à la loi sur les récipients sous pression.",
    "بدأ الإنتاج بأول ماكينة أيروسول شبه آلية مطابقة لقانون الأوعية المضغوطة.",
    "Начато производство на первой полуавтоматической аэрозольной машине, соответствующей закону о сосудах под давлением.")
add("tl3", "Çayırova / Kocaeli'de 5.500 m² kapalı alan, 60 milyon adet aerosol kapasitesi ve 80 ülkeye ihracat.",
    "5,500 m² of indoor space in Çayırova / Kocaeli, 60 million aerosol unit capacity and exports to 80 countries.",
    "5 500 m² couverts à Çayırova / Kocaeli, capacité de 60 millions d'aérosols et exportations vers 80 pays.",
    "مساحة مغلقة 5500 م² في تشايروفا / قوجه إيلي، وطاقة 60 مليون عبوة أيروسول، وتصدير إلى 80 دولة.",
    "5 500 м² закрытой площади в Чайырове / Коджаэли, мощность 60 млн аэрозолей и экспорт в 80 стран.")
add("tl_today", "Bugün", "Today", "Aujourd'hui", "اليوم", "Сегодня")
add("ab_team_eyebrow", "Ekibimiz", "Our Team", "Notre équipe", "فريقنا", "Наша команда")
add("ab_team_h2", "Ar-Ge, <em>tasarım</em>, grafik.", "R&amp;D, <em>design</em>, graphics.", "R&amp;D, <em>design</em>, graphisme.",
    "البحث والتطوير، <em>التصميم</em>، الجرافيك.", "НИОКР, <em>дизайн</em>, графика.")
add("ab_team_lead", "Ürünlerimizin kalitesini artırmak için üç ayrı departman birlikte çalışır. Pazarlama ve satış ekibimiz müşteri memnuniyeti için hizmetinizde.",
    "Three departments work together to raise the quality of our products. Our marketing and sales teams are at your service for maximum customer satisfaction.",
    "Trois départements collaborent pour élever la qualité de nos produits. Nos équipes marketing et commerciales sont à votre service pour une satisfaction client maximale.",
    "تعمل ثلاثة أقسام معاً لرفع جودة منتجاتنا، وفريقا التسويق والمبيعات في خدمتكم لتحقيق أقصى رضا للعملاء.",
    "Три отдела работают вместе, повышая качество продукции. Команды маркетинга и продаж к вашим услугам для максимальной удовлетворённости клиентов.")
add("ab_caption", "Çayırova · Kocaeli", "Çayırova · Kocaeli", "Çayırova · Kocaeli", "تشايروفا · قوجه إيلي", "Чайырова · Коджаэли")

# ---------------------------------------------------------------- contact
add("ct_h1", "Projenizi <em>anlatın</em>.", "Tell us about <em>your project</em>.", "Parlez-nous de <em>votre projet</em>.", "أخبرنا عن <em>مشروعك</em>.", "Расскажите о <em>проекте</em>.")
add("ct_lead", "Projenizi, hedef pazarınızı ve ürün grubunuzu paylaşın; ekibimiz sizinle iletişime geçsin.",
    "Share your project, target market and product group, and our team will get in touch.",
    "Partagez votre projet, votre marché cible et votre gamme de produits ; notre équipe vous contactera.",
    "شاركنا مشروعك وسوقك المستهدف ومجموعة المنتجات، وسيتواصل معك فريقنا.",
    "Расскажите о проекте, целевом рынке и группе продукции — наша команда свяжется с вами.")
add("ct_addr", "Fabrika & Merkez", "Factory & Head Office", "Usine & siège", "المصنع والمقر", "Фабрика и офис")
add("ct_phone", "Telefon", "Phone", "Téléphone", "الهاتف", "Телефон")
add("ct_fax", "Faks", "Fax", "Fax", "فاكس", "Факс")
add("ct_email", "E-posta", "Email", "E-mail", "البريد الإلكتروني", "Эл. почта")
add("ct_social", "Sosyal Medya", "Social Media", "Réseaux sociaux", "وسائل التواصل", "Соцсети")
add("f_name", "Ad Soyad", "Full Name", "Nom complet", "الاسم الكامل", "Имя и фамилия")
add("f_name_ph", "Adınız ve soyadınız", "Your full name", "Votre nom complet", "اسمك الكامل", "Ваше имя и фамилия")
add("f_company", "Firma / Marka", "Company / Brand", "Société / Marque", "الشركة / العلامة", "Компания / Бренд")
add("f_company_ph", "Marka veya firma adı", "Brand or company name", "Nom de la marque ou de la société", "اسم العلامة أو الشركة", "Название бренда или компании")
add("f_email", "Kurumsal E-posta", "Business Email", "E-mail professionnel", "البريد الإلكتروني للعمل", "Рабочая почта")
add("f_phone", "Telefon", "Phone", "Téléphone", "الهاتف", "Телефон")
add("f_cat", "İlgilendiğiniz Kategori", "Category of Interest", "Catégorie concernée", "الفئة المهتم بها", "Интересующая категория")
add("f_brief", "Proje & Hedefler", "Project & Goals", "Projet & objectifs", "المشروع والأهداف", "Проект и цели")
add("f_brief_ph", "Örn: Private label deodorant serisi, hedef pazar, tahmini adet…",
    "E.g. a private label deodorant range, target market, estimated quantity…",
    "Ex. : une gamme de déodorants en marque blanche, marché cible, quantité estimée…",
    "مثال: مجموعة مزيلات عرق بعلامة خاصة، السوق المستهدف، الكمية التقديرية…",
    "Например: линейка дезодорантов под частной маркой, целевой рынок, ориентировочный тираж…")
add("f_other", "Diğer", "Other", "Autre", "أخرى", "Другое")
add("f_pl_tube", "Private Label / Alüminyum Tüp", "Private Label / Aluminium Tube", "Private Label / Tube aluminium", "العلامة الخاصة / أنبوب ألومنيوم", "Private Label / Алюминиевый баллон")
add("f_submit", "Teklif & Numune Talebini İlet", "Send Quote & Sample Request", "Envoyer la demande de devis et d'échantillons", "إرسال طلب عرض السعر والعينات", "Отправить запрос на расчёт и образцы")
add("f_err", "Lütfen işaretli alanları doldurun.", "Please fill in the highlighted fields.", "Veuillez remplir les champs signalés.", "يرجى تعبئة الحقول المحددة.", "Пожалуйста, заполните отмеченные поля.")
add("f_ok", "Talebiniz alındı. Ekibimiz en kısa sürede sizinle iletişime geçecek.",
    "Your request has been received. Our team will contact you shortly.",
    "Votre demande a bien été reçue. Notre équipe vous contactera très prochainement.",
    "تم استلام طلبك. سيتواصل معك فريقنا قريباً.", "Ваш запрос получен. Наша команда свяжется с вами в ближайшее время.")

# ---------------------------------------------------------------- page titles / descriptions
add("title_index", "Uzman Cosmetic — Markanızın İmzası, Bizim Ustalığımız", "Uzman Cosmetic — Your Brand's Signature, Our Craftsmanship",
    "Uzman Cosmetic — La signature de votre marque, notre savoir-faire", "أوزمان كوزمتيك — توقيع علامتك وحِرفتنا",
    "Uzman Cosmetic — Подпись вашего бренда, наше мастерство")
add("desc_index", "1978'den beri fason ve private label body care ve home care üretimi. Çayırova, Kocaeli. 80 ülkeye ihracat.",
    "Contract and private label body care and home care manufacturing since 1978. Çayırova, Kocaeli. Exporting to 80 countries.",
    "Fabrication sous-traitée et en marque blanche de soins du corps et de parfums d'intérieur depuis 1978. Çayırova, Kocaeli. Export dans 80 pays.",
    "تصنيع منتجات العناية بالجسم والمنزل للغير وللعلامات الخاصة منذ 1978. تشايروفا، قوجه إيلي. تصدير إلى 80 دولة.",
    "Контрактное производство и Private Label средств для тела и дома с 1978 года. Чайырова, Коджаэли. Экспорт в 80 стран.")
add("title_products", "Ürünler — Uzman Cosmetic", "Products — Uzman Cosmetic", "Produits — Uzman Cosmetic", "المنتجات — أوزمان كوزمتيك", "Продукция — Uzman Cosmetic")
add("title_body", "Body Care — Uzman Cosmetic", "Body Care — Uzman Cosmetic", "Soins du corps — Uzman Cosmetic", "العناية بالجسم — أوزمان كوزمتيك", "Body Care — Uzman Cosmetic")
add("title_home", "Home Care — Uzman Cosmetic", "Home Care — Uzman Cosmetic", "Parfums d'intérieur — Uzman Cosmetic", "العناية بالمنزل — أوزمان كوزمتيك", "Home Care — Uzman Cosmetic")
add("title_pl", "Private Label — Uzman Cosmetic", "Private Label — Uzman Cosmetic", "Private Label — Uzman Cosmetic", "العلامة الخاصة — أوزمان كوزمتيك", "Private Label — Uzman Cosmetic")
add("title_about", "Kurumsal — Uzman Cosmetic", "About Us — Uzman Cosmetic", "Entreprise — Uzman Cosmetic", "من نحن — أوزمان كوزمتيك", "О компании — Uzman Cosmetic")
add("title_contact", "İletişim & Teklif — Uzman Cosmetic", "Contact & Quote — Uzman Cosmetic", "Contact & devis — Uzman Cosmetic", "اتصل بنا وعرض السعر — أوزمان كوزمتيك", "Контакты и запрос — Uzman Cosmetic")
