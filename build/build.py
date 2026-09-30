# -*- coding: utf-8 -*-
"""Static site generator: python3 build/build.py  ->  writes tr (root) + en/fr/ar/ru pages."""
import glob
import os
import re
import shutil
import sys

from PIL import Image

sys.path.insert(0, os.path.dirname(__file__))
from catalog import PL_GROUPS, PL_MODELS, TREE  # noqa: E402
from i18n import HTML_LANG, LANG_LABEL, LANG_NAME, LANGS, RTL, S, UNIT  # noqa: E402

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
SITE = "https://uzmancosmetic.com"
DEFAULT_LANG = "tr"
PAGES = ["index", "products", "body-care", "home-care", "private-label", "about", "contact"]


# Photos used for the two big cards on the home page and the products overview.
FAN = {
    "body": ["montagneblack3", "edt3", "oriflower-body-mist1"],
    "home": ["reed-diffuser1", "air-lilac", "oda-spreyi-fresh1"],
}

_size_cache = {}


def t(lang, key):
    return S[key][lang]


def img_size(kind, stem):
    path = os.path.join(ROOT, "assets", "img", kind, f"{stem}.webp")
    if path not in _size_cache:
        if not os.path.exists(path):
            raise SystemExit(f"missing image: {path}")
        with Image.open(path) as im:
            _size_cache[path] = im.size
    return _size_cache[path]


def asset_prefix(lang):
    return "" if lang == DEFAULT_LANG else "../"


def page_url(lang, page, from_lang=None):
    """Relative URL of `page` in `lang`, as seen from a page in `from_lang`."""
    from_lang = from_lang or lang
    name = f"{page}.html"
    if lang == from_lang:
        return name
    if from_lang == DEFAULT_LANG:
        return f"{lang}/{name}"
    return f"../{name}" if lang == DEFAULT_LANG else f"../{lang}/{name}"


def ml(lang, sizes):
    if not sizes:
        return ""
    return " · ".join(str(s) for s in sizes) + f" {UNIT[lang]}"


def bdi(text):
    return f'<bdi dir="ltr">{text}</bdi>'


def picture(lang, kind, stem, alt, cls="", eager=False):
    w, h = img_size(kind, stem)
    A = asset_prefix(lang)
    load = "" if eager else ' loading="lazy"'
    return (f'<img src="{A}assets/img/{kind}/{stem}.webp" alt="{alt}" width="{w}" height="{h}"'
            f'{load} decoding="async" class="{cls}">')


# ------------------------------------------------------------------ layout
def preload_fonts(A, lang):
    names = ["cormorant-garamond-latin", "manrope-latin"]
    if lang == "ru":
        names = ["cormorant-garamond-cyrillic", "manrope-cyrillic"]
    if lang == "ar":
        names = ["noto-naskh-arabic-arabic", "tajawal-arabic"]
    out = ""
    for n in names:
        f = sorted(glob.glob(os.path.join(ROOT, "assets", "fonts", n + "-*.woff2")))[:1]
        if f:
            out += f'<link rel="preload" href="{A}assets/fonts/{os.path.basename(f[0])}" as="font" type="font/woff2" crossorigin>\n'
    return out


def head(lang, page, title_key, desc_key):
    A = asset_prefix(lang)
    font_links = preload_fonts(A, lang)
    alt = "\n".join(
        f'<link rel="alternate" hreflang="{HTML_LANG[l]}" href="{SITE}/{"" if l == DEFAULT_LANG else l + "/"}{page}.html">'
        for l in LANGS)
    alt += f'\n<link rel="alternate" hreflang="x-default" href="{SITE}/{page}.html">'
    return f"""<!DOCTYPE html>
<html lang="{HTML_LANG[lang]}" dir="{'rtl' if lang in RTL else 'ltr'}" class="no-js" data-theme="noir">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0b0c0e">
<title>{t(lang, title_key)}</title>
<meta name="description" content="{t(lang, desc_key) if desc_key else ''}">
{alt}
{font_links}<link rel="stylesheet" href="{A}assets/css/fonts.css">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Uzman Cosmetic">
<meta property="og:title" content="{t(lang, title_key)}">
<meta property="og:description" content="{t(lang, desc_key) if desc_key else ''}">
<meta property="og:image" content="{SITE}/assets/img/og.jpg">
<meta property="og:locale" content="{HTML_LANG[lang]}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="{A}assets/img/favicon.png">
<link rel="apple-touch-icon" href="{A}assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="{A}assets/css/style.css">
<script>try{{var s=new URLSearchParams(location.search).get('theme')||localStorage.getItem('uzman-theme');if(['noir','bordeaux','emerald','twotone','inverse'].indexOf(s)>-1)document.documentElement.setAttribute('data-theme',s)}}catch(e){{}}</script>
</head>
<body data-page="{page}">
<a class="skip" href="#main">{t(lang, 'skip')}</a>
<div class="progress" aria-hidden="true"></div>
"""


def lang_switch(lang, page, cls="lang"):
    items = []
    for l in LANGS:
        if l == lang:
            items.append(f'<b aria-current="true">{LANG_LABEL[l]}</b>')
        else:
            items.append(f'<a href="{page_url(l, page, lang)}" hreflang="{HTML_LANG[l]}" lang="{HTML_LANG[l]}" '
                         f'title="{LANG_NAME[l]}">{LANG_LABEL[l]}</a>')
    return f'<div class="{cls}" role="group" aria-label="{t(lang, "aria_lang")}">{"".join(items)}</div>'


def theme_switch(lang):
    return (f'<div class="theme-switch" role="group" aria-label="{t(lang, "aria_theme")}">'
            '<button data-set="noir" aria-label="Noir &amp; Honey" title="Noir &amp; Honey"></button>'
            '<button data-set="bordeaux" aria-label="Bordeaux Rose Gold" title="Bordeaux Rose Gold"></button>'
            '<button data-set="emerald" aria-label="Emerald &amp; Rose Gold" title="Emerald &amp; Rose Gold"></button>'
            '<button data-set="twotone" aria-label="Two-Tone Bordeaux (green ground)" title="Two-Tone Bordeaux"></button>'
            '<button data-set="inverse" aria-label="Bordeaux ground, emerald accent" title="Bordeaux × Emerald"></button></div>')


NAV = [("private-label", "nav_pl"), ("products", "nav_products"), ("about", "nav_about"), ("contact", "nav_contact")]
ACTIVE = {"body-care": "products", "home-care": "products"}


def header(lang, page):
    active = ACTIVE.get(page, page)
    nav = "".join(f'<a href="{p}.html" class="{"active" if p == active else ""}">{t(lang, k)}</a>' for p, k in NAV)
    mobile = "".join(f'<a class="big" href="{p}.html">{t(lang, k)}</a>' for p, k in NAV)
    return f"""<div class="site-header" id="siteHeader">
  <div class="topline"><div class="wrap">
    <div class="left"><span class="dot"></span>{t(lang, 'top_loc')}<span class="hide-sm"> &nbsp;·&nbsp; {t(lang, 'top_since')} &nbsp;·&nbsp; {t(lang, 'top_export')}</span></div>
    <div class="right"><a href="mailto:info@uzmancosmetic.com" class="hide-sm">info@uzmancosmetic.com</a><a href="tel:+902626580099" dir="ltr">+90 262 658 00 99</a></div>
  </div></div>
  <div class="wrap navbar">
    <a href="index.html" class="brand" aria-label="{t(lang, 'aria_home')}">
      <span class="brand-logo" aria-hidden="true"></span>
      <span class="brand-name">UZMAN<span class="brand-sub">{t(lang, 'brand_sub')}</span></span>
    </a>
    <nav class="nav">{nav}</nav>
    <div class="nav-right">
      {theme_switch(lang)}
      {lang_switch(lang, page)}
      <a href="contact.html#quote" class="btn nav-cta"><span>{t(lang, 'nav_quote')}</span></a>
      <button class="burger" id="burger" aria-label="{t(lang, 'aria_menu')}"><i></i><i></i></button>
    </div>
  </div>
</div>
<div class="mobile-menu" id="mobileMenu">
  {mobile}
  <a class="big" href="contact.html#quote" style="color:var(--gold-hi)">{t(lang, 'nav_quote')}</a>
  <div class="mm-tools">{theme_switch(lang)}{lang_switch(lang, page)}</div>
</div>
<main id="main">
"""


def footer(lang):
    disc = "".join(f'<li><a href="{p}.html">{t(lang, k)}</a></li>' for p, k in NAV)
    prods = (f'<li><a href="body-care.html">{t(lang, "cat_body")}</a></li>'
             f'<li><a href="home-care.html">{t(lang, "cat_home")}</a></li>')
    A = asset_prefix(lang)
    return f"""</main>
<footer class="site-footer">
  <div class="wrap">
    <div class="foot-top">
      <div>
        <a href="index.html" class="brand" style="margin-bottom:24px"><span class="brand-logo lg" aria-hidden="true"></span><span class="brand-name">UZMAN<span class="brand-sub">COSMETIC</span></span></a>
        <p style="max-width:38ch">{t(lang, 'foot_blurb')}</p>
      </div>
      <div><h3>{t(lang, 'foot_discover')}</h3><ul>{disc}</ul></div>
      <div><h3>{t(lang, 'foot_products')}</h3><ul>{prods}</ul></div>
      <div><h3>{t(lang, 'foot_hq')}</h3><p>{t(lang, 'foot_addr')}<br><a href="tel:+902626580099" dir="ltr">+90 262 658 00 99</a><br><a href="mailto:info@uzmancosmetic.com">info@uzmancosmetic.com</a><br><a href="https://www.instagram.com/uzmancosmetic/" rel="noopener">@uzmancosmetic</a></p></div>
    </div>
    <div class="foot-word" aria-hidden="true">UZMAN</div>
    <div class="foot-bot"><span>{t(lang, 'foot_rights')}</span><span>ÇAYIROVA · KOCAELİ · TÜRKİYE</span></div>
  </div>
</footer>
<script src="{A}assets/js/main.js" defer></script>
</body>
</html>
"""


def cta_band(lang, video=False):
    bg = vbg(lang, "hero", ".4", "vbg-crop") if video else ""
    return f"""<section class="cta-band{' has-vbg' if video else ''}">{bg}<span class="logo-wm" aria-hidden="true"></span><div class="wrap">
  <h2 class="rv">{t(lang, 'cta_h2')}</h2>
  <p class="lead rv">{t(lang, 'cta_lead')}</p>
  <div class="cta-row rv"><a href="contact.html#quote" class="btn btn-solid"><span>{t(lang, 'cta_btn')}</span><i class="arrow"></i></a></div>
</div></section>"""


def page_hero(lang, eyebrow_key, h1_key, lead_key, word):
    return f"""<header class="page-hero">
  <div class="wrap"><h1>{t(lang, h1_key)}</h1>
  <p class="lead">{t(lang, lead_key)}</p></div>
</header>"""


def values_block(lang, bg=False):
    style = "" 
    cells = "".join(
        f'<div class="value rv" style="--d:{i * .1:.1f}s"><h3>{t(lang, f"v{i + 1}_t")}</h3><p>{t(lang, f"v{i + 1}_p")}</p></div>'
        for i in range(3))
    return f"""<section class="section{' band' if bg else ''}"{style}><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>{t(lang, 'val_h2')}</h2></div></div>
  <div class="values">{cells}</div>
</div></section>"""


def steps_block(lang):
    rows = "".join(
        f'<div class="step rv"><div class="n">0{i}</div><div><h3>{t(lang, f"s{i}_t")}</h3><p>{t(lang, f"s{i}_p")}</p></div></div>'
        for i in range(1, 6))
    return f'<div class="steps">{rows}</div>'


def vbg(lang, name, opacity=".5", cls=""):
    """Ambient looping background video (lazy-loaded by main.js, tinted per theme by CSS)."""
    A = asset_prefix(lang)
    return (f'<div class="vbg {cls}" style="--vo:{opacity}" aria-hidden="true"><video muted loop playsinline preload="none" '
            f'poster="{A}assets/video/{name}.jpg" data-src="{A}assets/video/{name}.mp4" data-webm="{A}assets/video/{name}.webm"></video></div>')


def fan_imgs(lang, stems, eager=False):
    imgs = "".join(f'<span class="f{i}">{picture(lang, "p", st, "", eager=eager)}</span>' for i, st in enumerate(stems, 1))
    return f'<div class="fan">{imgs}</div>'


def fan(lang, kind):
    imgs = "".join(f'<span class="f{i}">{picture(lang, "p", s, "", "")}</span>' for i, s in enumerate(FAN[kind], 1))
    return f'<div class="fan">{imgs}</div>'


# ------------------------------------------------------------------ pages
def page_index(lang):
    facts_items = "".join(f"<li>{t(lang, f'm{i}')}</li>" for i in range(1, 7))
    cards = ""
    for i, c in enumerate(TREE):
        names = " · ".join(t(lang, s["name"]) for s in c["subs"])
        cards += f"""<a href="{c['page']}" class="coll rv" style="--d:{i * .12:.2f}s">
{fan(lang, c['id'])}
        <h3>{t(lang, c['name'])}</h3><p>{names}</p><span class="link-arrow more">{t(lang, 'explore')} <i class="arrow"></i></span></a>"""
    return head(lang, "index", "title_index", "desc_index") + header(lang, "index") + f"""
<section class="hero has-vbg">
  {vbg(lang, "hero", ".42", "vbg-crop")}
  <div class="wrap hero-grid">
    <div>
      <h1>
        <span class="line"><span>{t(lang, 'hero_l1')}</span></span>
        <span class="line"><span>{t(lang, 'hero_l2')}</span></span>
        <span class="line"><span>{t(lang, 'hero_l3')}</span></span>
      </h1>
      <p class="lead">{t(lang, 'hero_lead')}</p>
      <div class="cta-row">
        <a href="contact.html#quote" class="btn btn-solid"><span>{t(lang, 'cta_start')}</span><i class="arrow"></i></a>
        <a href="private-label.html" class="btn"><span>{t(lang, 'nav_pl')}</span><i class="arrow"></i></a>
      </div>
    </div>
    <div class="hero-stage">
      <i class="arc a3"></i><i class="arc"></i><i class="arc a2"></i>
      <span class="hero-bottle"><span class="hero-float">{picture(lang, "h", "hero-lineup", t(lang, "hero_alt"), eager=True)}{vbg(lang, "hero", ".1", "vbg-fg vbg-crop")}</span></span>
    </div>
  </div>
</section>

<div class="facts"><ul>{facts_items}</ul></div>

<section class="section manifesto has-vbg">{vbg(lang, "petals", ".5")}<div class="wrap">
  <p>{t(lang, 'manifesto')}</p>
</div></section>

<section class="section has-vbg">{vbg(lang, "fill", ".42", "vbg-top")}<div class="wrap">
  <div class="sec-head">
    <div class="rv"><h2>{t(lang, 'coll_h2')}</h2></div>
    <p class="lead rv">{t(lang, 'coll_lead')}</p>
  </div>
  <div class="collections two">{cards}</div>
</div></section>

<section class="section"><div class="wrap process-grid">
  <div class="process-sticky rv">
    <h2>{t(lang, 'pl_h2')}</h2>
    <p class="lead" style="margin-bottom:36px">{t(lang, 'pl_lead')}</p>
    <a href="private-label.html" class="btn"><span>{t(lang, 'pl_btn')}</span><i class="arrow"></i></a>
  </div>
  {steps_block(lang)}
</div></section>

<section class="section band has-vbg">{vbg(lang, "line", ".5")}<div class="wrap split">
  <div class="split-media rv">{fan_imgs(lang, ["montagneblack3", "montagneblack6", "camay"])}<span class="cap">{t(lang, 'pw_cap')}</span></div>
  <div class="rv" style="--d:.1s">
    <h2>{t(lang, 'pw_h2')}</h2>
    <p class="lead">{t(lang, 'pw_lead')}</p>
    <ul class="checks"><li>{t(lang, 'pw_c1')}</li><li>{t(lang, 'pw_c2')}</li><li>{t(lang, 'pw_c3')}</li></ul>
    <a href="about.html" class="link-arrow">{t(lang, 'pw_link')} <i class="arrow"></i></a>
  </div>
</div></section>

{values_block(lang)}

<section class="section band export"><div class="wrap export-grid">
  <div class="rv">
    <h2>{t(lang, 'ex_h2')}</h2>
    <p class="lead">{t(lang, 'ex_lead')}</p>
    <div class="regions">
      {''.join(f'<div>{t(lang, f"ex_f{i}")}<small>{t(lang, f"ex_f{i}s")}</small></div>' for i in range(1, 5))}
    </div>
  </div>
  <div class="globe rv" style="--d:.15s">
    <svg viewBox="0 0 200 200" aria-hidden="true">
      <circle class="ring" cx="100" cy="100" r="92"/><circle class="ring" cx="100" cy="100" r="70" stroke-dasharray="2 3"/>
      <ellipse class="ring" cx="100" cy="100" rx="92" ry="34"/><ellipse class="ring" cx="100" cy="100" rx="34" ry="92"/><ellipse class="ring" cx="100" cy="100" rx="64" ry="92" opacity=".5"/>
      <line class="ring" x1="8" y1="100" x2="192" y2="100"/>
      <g><path class="ring" d="M112 70 Q150 40 168 82" style="stroke:var(--gold)"/><path class="ring" d="M112 70 Q80 30 52 58" style="stroke:var(--gold)"/><path class="ring" d="M112 70 Q140 110 128 148" style="stroke:var(--gold)"/></g>
      <circle class="pulse" cx="112" cy="70" r="3"/><circle class="pin" cx="112" cy="70" r="3"/>
      <circle class="pin" cx="168" cy="82" r="2"/><circle class="pin" cx="52" cy="58" r="2"/><circle class="pin" cx="128" cy="148" r="2"/>
    </svg>
  </div>
</div></section>

{cta_band(lang, video=True)}
""" + footer(lang)


def tree_card(lang, cat, idx):
    rows = ""
    for s in cat["subs"]:
        leaf = ""
        if s.get("children"):
            leaf = '<ul class="leaf">' + "".join(f'<li>{t(lang, ch["name"])}</li>' for ch in s["children"]) + "</ul>"
        rows += (f'<li><a href="{cat["page"]}#{s["id"]}"><span class="nm">{t(lang, s["name"])}</span>'
                 f'<span class="sz">{bdi(ml(lang, s["sizes"])) if s["sizes"] else ""}</span></a>{leaf}</li>')
    return f"""<article class="tree rv" style="--d:{idx * .12:.2f}s">
  {fan(lang, cat['id'])}
  <header><h2><a href="{cat['page']}">{t(lang, cat['name'])}</a></h2></header>
  <ul class="branch">{rows}</ul>
  <a href="{cat['page']}" class="link-arrow">{t(lang, 'explore')} <i class="arrow"></i></a>
</article>"""


def page_products(lang):
    cards = "".join(tree_card(lang, c, i) for i, c in enumerate(TREE))
    return (head(lang, "products", "title_products", "pr_lead") + header(lang, "products")
            + page_hero(lang, "nav_products", "pr_h1", "pr_lead", "Atelier")
            + f'<section class="section"><div class="wrap"><div class="tree-grid">{cards}</div></div></section>'
            + cta_band(lang) + footer(lang))


def pcard(lang, caption, stem, art):
    cap = t(lang, caption[1]) if isinstance(caption, tuple) else caption
    if stem:
        w, h = img_size("p", stem)
        A = asset_prefix(lang)
        return (f'<a class="pcard" href="{A}assets/img/p/{stem}.webp" data-lb data-cap="{cap}">'
                f'{picture(lang, "p", stem, cap)}<span class="cap">{cap}</span></a>')
    return (f'<div class="pcard ph"><span class="ph-frame" aria-hidden="true"></span>'
            f'<span class="cap">{cap}</span><span class="soon">{t(lang, "soon")}</span></div>')


def sub_section(lang, s):
    chips = f'<span class="chip">{t(lang, "sizes")}: {bdi(ml(lang, s["sizes"]))}</span>' if s["sizes"] else ""
    head_html = f"""<div class="sub-head rv"><div><h2>{t(lang, s['name'])}</h2><p class="lead">{t(lang, s['desc'])}</p></div>{chips}</div>"""
    if s.get("children"):
        body = ""
        for ch in s["children"]:
            cards = "".join(pcard(lang, c, st, ch["art"]) for c, st in ch["items"])
            body += f'<h3 class="mini rv">{t(lang, ch["name"])}</h3><div class="pgrid">{cards}</div>'
    else:
        cards = "".join(pcard(lang, c, st, s["art"]) for c, st in s["items"])
        body = f'<div class="pgrid">{cards}</div>'
    return f'<section class="section sub" id="{s["id"]}"><div class="wrap">{head_html}{body}</div></section>'


def page_category(lang, cat):
    pre = "bc" if cat["id"] == "body" else "hc"
    tabs = "".join(f'<a href="#{s["id"]}">{t(lang, s["name"])}</a>' for s in cat["subs"])
    subs = "".join(sub_section(lang, s) for s in cat["subs"])
    return (head(lang, cat["page"][:-5], "title_body" if pre == "bc" else "title_home", f"{pre}_lead")
            + header(lang, cat["page"][:-5])
            + page_hero(lang, "nav_products", f"{pre}_h1", f"{pre}_lead", "Body" if pre == "bc" else "Home")
            + f'<nav class="subnav"><div class="wrap"><a href="products.html" class="back">{t(lang, "back_tree")}</a>{tabs}</div></nav>'
            + subs + cta_band(lang) + footer(lang))


def pl_items():
    out = []
    for path in sorted(glob.glob(os.path.join(ROOT, "assets/img/pl/*.webp"))):
        stem = os.path.basename(path)[:-5]
        m = re.match(r"^([a-z]+)(\d+)x(\d+)([a-z]?)$", stem)
        if m:
            prefix, a, b, suffix = m.groups()
            group, label, style = PL_MODELS[prefix]
            dims = (f"Ø{a} × {b}" if style == "dia" else f"{a} × {b}") + " mm" + (f" ({suffix.upper()})" if suffix else "")
            out.append((group, label, dims, stem, int(a), int(b)))
        else:  # a-type / b-type
            out.append(("special", stem.replace("-", " ").title(), "", stem, 999, 999))
    order = {g: i for i, (g, _) in enumerate(PL_GROUPS)}
    out.sort(key=lambda x: (order[x[0]], x[1], x[4], x[5]))
    return out


def page_private_label(lang):
    items = pl_items()
    filters = f'<button class="on" data-f="all">{t(lang, "all")}</button>' + "".join(
        f'<button data-f="{g}">{t(lang, k)}</button>' for g, k in PL_GROUPS if any(i[0] == g for i in items))
    A = asset_prefix(lang)
    cards = "".join(
        f'<a class="tcard" data-cat="{g}" href="{A}assets/img/pl/{stem}.webp" data-lb data-cap="{label} {dims}">'
        f'{picture(lang, "pl", stem, label)}<span class="cap"><b>{label}</b>{bdi(dims) if dims else ""}</span></a>'
        for g, label, dims, stem, _, _ in items)
    return (head(lang, "private-label", "title_pl", "pl_page_lead") + header(lang, "private-label")
            + page_hero(lang, "nav_pl", "pl_h1", "pl_page_lead", "Label")
            + f"""<section class="section"><div class="wrap process-grid">
  <div class="process-sticky rv"><h2>{t(lang, 'pl_proc_h2')}</h2><p class="lead">{t(lang, 'pl_proc_lead')}</p></div>
  {steps_block(lang)}
</div></section>
<section class="section band" id="tubes"><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>{t(lang, 'tube_h2')}</h2></div><p class="lead rv">{t(lang, 'tube_lead')}</p></div>
  <div class="filters rv">{filters}</div>
  <div class="tgrid">{cards}</div>
</div></section>"""
            + cta_band(lang) + footer(lang))


def page_about(lang):
    return (head(lang, "about", "title_about", "ab_lead") + header(lang, "about")
            + page_hero(lang, "nav_about", "ab_h1", "ab_lead", "1978")
            + f"""<section class="section manifesto"><div class="wrap"><p>{t(lang, 'ab_manifesto')}</p></div></section>
<section class="section"><div class="wrap split">
  <div class="rv"><h2>{t(lang, 'ab_journey_h2')}</h2>
    <div class="timeline" style="margin-top:48px">
      <div class="tl"><div class="yr">1978</div><p>{t(lang, 'tl1')}</p></div>
      <div class="tl"><div class="yr">1980</div><p>{t(lang, 'tl2')}</p></div>
      <div class="tl"><div class="yr">{t(lang, 'tl_today')}</div><p>{t(lang, 'tl3')}</p></div>
    </div>
  </div>
  <div class="split-media rv" style="--d:.1s">{fan_imgs(lang, ["x-block1", "montagneblack2", "x-block3"])}<span class="cap">{t(lang, 'ab_caption')}</span></div>
</div></section>
{values_block(lang, bg=True)}
<section class="section"><div class="wrap">
  <div class="sec-head"><div class="rv"><h2>{t(lang, 'ab_team_h2')}</h2></div><p class="lead rv">{t(lang, 'ab_team_lead')}</p></div>
</div></section>"""
            + cta_band(lang) + footer(lang))


def page_contact(lang):
    opts = "".join(f"<option>{t(lang, s['name'])}</option>" for c in TREE for s in c["subs"])
    opts += f"<option>{t(lang, 'f_pl_tube')}</option><option>{t(lang, 'f_other')}</option>"
    return (head(lang, "contact", "title_contact", "ct_lead") + header(lang, "contact")
            + page_hero(lang, "nav_contact", "ct_h1", "ct_lead", "Contact")
            + f"""<section class="section" id="quote"><div class="wrap contact-grid">
  <aside class="contact-info rv">
    <div><h2>{t(lang, 'ct_addr')}</h2><p>{t(lang, 'foot_addr')}</p></div>
    <div><h2>{t(lang, 'ct_phone')}</h2><a href="tel:+902626580099" dir="ltr">+90 262 658 00 99</a><br><a href="tel:+902626580399" dir="ltr">+90 262 658 03 99</a><p style="font-size:14px;color:var(--muted);margin-top:6px">{t(lang, 'ct_fax')}: <bdi dir="ltr">+90 262 658 03 20</bdi></p></div>
    <div><h2>{t(lang, 'ct_email')}</h2><a href="mailto:info@uzmancosmetic.com">info@uzmancosmetic.com</a></div>
    <div style="border-bottom:1px solid var(--line)"><h2>{t(lang, 'ct_social')}</h2><a href="https://www.instagram.com/uzmancosmetic/" rel="noopener">@uzmancosmetic</a></div>
  </aside>
  <form class="form-card rv" data-form data-err="{t(lang, 'f_err')}" data-ok="{t(lang, 'f_ok')}" novalidate style="--d:.1s">
    <div class="grid-2">
      <div class="field"><label for="f_name">{t(lang, 'f_name')}</label><input id="f_name" required autocomplete="name" placeholder="{t(lang, 'f_name_ph')}"></div>
      <div class="field"><label for="f_company">{t(lang, 'f_company')}</label><input id="f_company" required autocomplete="organization" placeholder="{t(lang, 'f_company_ph')}"></div>
    </div>
    <div class="grid-2">
      <div class="field"><label for="f_email">{t(lang, 'f_email')}</label><input id="f_email" type="email" required autocomplete="email" placeholder="name@brand.com" dir="ltr"></div>
      <div class="field"><label for="f_phone">{t(lang, 'f_phone')}</label><input id="f_phone" type="tel" required autocomplete="tel" placeholder="+90 5XX XXX XX XX" dir="ltr"></div>
    </div>
    <div class="field"><label for="f_cat">{t(lang, 'f_cat')}</label><select id="f_cat">{opts}</select></div>
    <div class="field"><label for="f_brief">{t(lang, 'f_brief')}</label><textarea id="f_brief" placeholder="{t(lang, 'f_brief_ph')}"></textarea></div>
    <button class="btn btn-solid" type="submit"><span>{t(lang, 'f_submit')}</span><i class="arrow"></i></button>
    <p class="form-status" role="status" aria-live="polite"></p>
  </form>
</div></section>""" + footer(lang))


BUILDERS = {
    "index": page_index,
    "products": page_products,
    "body-care": lambda l: page_category(l, TREE[0]),
    "home-care": lambda l: page_category(l, TREE[1]),
    "private-label": page_private_label,
    "about": page_about,
    "contact": page_contact,
}


def page_404():
    """Branded 404 (root-relative URLs: it can be served from any path)."""
    body = page_index_min()
    return body


def page_index_min():
    lang = DEFAULT_LANG
    html = head(lang, "404", "title_index", None)
    html = html.replace('href="assets/', 'href="/assets/').replace('<body data-page="404">', '<body data-page="404">')
    return html + f"""<div class="site-header scrolled" id="siteHeader"><div class="wrap navbar"><a href="/index.html" class="brand"><span class="brand-logo" aria-hidden="true"></span><span class="brand-name">UZMAN<span class="brand-sub">COSMETIC</span></span></a></div></div>
<main id="main"><section class="hero"><div class="wrap" style="text-align:center"><h1 style="margin:28px 0"><em>404</em></h1><p class="lead" style="margin:0 auto 40px">Aradığınız sayfa bulunamadı. · Page not found.</p>
<div class="cta-row" style="justify-content:center"><a href="/index.html" class="btn btn-solid"><span>Ana sayfa / Home</span><i class="arrow"></i></a></div></div></section></main>
<script src="/assets/js/main.js" defer></script></body></html>"""


def main():
    # remove previously generated output (only files this script owns)
    for lang in LANGS:
        base = ROOT if lang == DEFAULT_LANG else os.path.join(ROOT, lang)
        if lang != DEFAULT_LANG and os.path.isdir(base):
            shutil.rmtree(base)
        os.makedirs(base, exist_ok=True)
        for page in PAGES:
            with open(os.path.join(base, f"{page}.html"), "w", encoding="utf-8") as fh:
                fh.write(BUILDERS[page](lang))
    with open(os.path.join(ROOT, "404.html"), "w", encoding="utf-8") as fh:
        fh.write(page_404())
    print(f"built {len(PAGES)} pages x {len(LANGS)} languages + 404")


if __name__ == "__main__":
    main()
