# -*- coding: utf-8 -*-
"""Export the approved design content (i18n strings, product tree, tube models, hero slides) to app/seed/seed.json.
Run:  python3 tools/export_seed.py"""
import importlib.util
import json
import os
import sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, os.path.join(ROOT, "build"))
sys.path.insert(0, os.path.dirname(__file__))
from catalog import PL_GROUPS, TREE  # noqa: E402
from i18n import LANGS, S  # noqa: E402
import seed_pages  # noqa: E402

spec = importlib.util.spec_from_file_location("site_build", os.path.join(ROOT, "build", "build.py"))
sb = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sb)


def tr(key):
    return dict(S[key])


def cap(c):
    return tr(c[1]) if isinstance(c, tuple) else c  # literal brand / model name


def img(stem):
    return f"assets/img/p/{stem}.webp" if stem else ""


def sub_out(s, idx):
    out = {"slug": s["id"], "name": tr(s["name"]), "desc": tr(s["desc"]), "sizes": s["sizes"], "art": s["art"], "sort": idx}
    if s.get("children"):
        out["children"] = [{"name": tr(c["name"]), "items": [{"cap": cap(a), "image": img(b)} for a, b in c["items"]]} for c in s["children"]]
    else:
        out["items"] = [{"cap": cap(a), "image": img(b)} for a, b in s["items"]]
    return out


cats = [{"slug": c["id"], "page": c["page"][:-5], "name": tr(c["name"]), "sort": i,
         "subs": [sub_out(s, j) for j, s in enumerate(c["subs"])]} for i, c in enumerate(TREE)]
groups = {g: tr(k) for g, k in PL_GROUPS}
pl = [{"grp": g, "label": label, "dims": dims, "image": f"assets/img/pl/{stem}.webp", "sort": n}
      for n, (g, label, dims, stem, a, b) in enumerate(sb.pl_items())]

strings = {k: dict(v) for k, v in S.items()}
strings.update({k: dict(v) for k, v in seed_pages.EXTRA_STRINGS.items()})

system_pages = [("index", "home", 0, 0, 0), ("private-label", "private-label", 10, 1, 1), ("products", "products", 20, 1, 1),
                ("body-care", "category", 21, 0, 0), ("home-care", "category", 22, 0, 0), ("about", "about", 80, 1, 1),
                ("contact", "contact", 90, 1, 1)]
pages = [{"slug": s, "type": t, "sort": so, "in_nav": nv, "in_footer": ft, "status": 1} for s, t, so, nv, ft in system_pages]
for slug, p in seed_pages.PAGES.items():
    pages.append({"slug": slug, "type": "custom", "sort": p["sort"], "in_nav": p["in_nav"], "in_footer": p["in_footer"], "status": 1,
                  "title": p["title"], "meta": p["meta"], "h1": p["h1"], "lead": p["lead"], "cta": p["cta"], "blocks": p["blocks"]})

hero = [{"image": f"assets/img/h/{stem}.webp", "label": name, "sort": i} for i, (stem, name) in enumerate(sb.HERO_SLIDES)]
fan = {k: [f"assets/img/p/{s}.webp" for s in v] for k, v in sb.FAN.items()}
about_fan = [f"assets/img/p/{s}.webp" for s in ["x-block1", "montagneblack2", "x-block3"]]
pw_fan = [f"assets/img/p/{s}.webp" for s in ["montagneblack3", "montagneblack6", "camay"]]

data = {"langs": LANGS, "strings": strings, "categories": cats, "pl_groups": groups, "pl_models": pl, "hero": hero,
        "pages": pages, "fan": fan, "about_fan": about_fan, "pw_fan": pw_fan}
os.makedirs(os.path.join(ROOT, "app", "seed"), exist_ok=True)
with open(os.path.join(ROOT, "app", "seed", "seed.json"), "w", encoding="utf-8") as fh:
    json.dump(data, fh, ensure_ascii=False, indent=1)
print("seed:", len(strings), "strings,", len(cats), "categories,", len(pl), "tube models,", len(pages), "pages")
