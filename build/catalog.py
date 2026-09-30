# -*- coding: utf-8 -*-
"""Product tree agreed in the meeting + image file names (stems) taken from uzmancosmetic.com.

Image stems refer to assets/img/p/<stem>.webp. `None` means no photo exists on the current
site yet (a placeholder is rendered until the client supplies one).
Captions: plain string = literal (brand/model name), ("t", key) = translated.
"""


def T(key):
    return ("t", key)


def _n(stem, count, start=1, first_plain=False):
    """stem, stem1..stemN helper -> list of image stems."""
    names = [stem] if first_plain else []
    names += [f"{stem}{i}" for i in range(start, count + 1)]
    return names


DEO_BRANDS = [
    ("Bonamour", _n("bonamour", 10)),
    ("Cremmy", _n("cremmy", 4)),
    ("Montagne Black", _n("montagneblack", 6)),
    ("Oriflowers", _n("oriflowers", 5)),
    ("X Block", _n("x-block", 4)),
    ("Camay", ["camay", "camay1", "camay2", "camay3"]),
    ("Purixima", ["purixima-deo", "purixima-deo1", "purixima-deo2", "purixima-deo3", "purixima-deo4"]),
]

AIR_SCENTS = [
    ("Anti Tobacco", "air-antitobacco"), ("Anti", "air-anti"), ("Apple Cinnamon", "air-apple"),
    ("Apple", "air-appleg"), ("Bubble Gum", "air-bubble"), ("Chocolate", "air-chocolate"),
    ("Jasmine", "air-jasmine"), ("Lilac", "air-lilac"), ("Lily of the Valley", "air-lily"),
    ("Patchouli", "air-patchouli"), ("Pomegranate", "air-pommegranade"), ("Raspberry", "air-raspberry"),
    ("Red Rose", "air-redrose"), ("Magic Fresh Air", "air-magic-fresh-air"),
    ("Magic Fresh Air", "air-magic-fresh-air1"), ("Magic Fresh Air", "air-magic-fresh-air2"),
    ("Magic Fresh Air", "air-magic-fresh-air3"),
]


def _items_from(pairs):
    return [(cap, stem) for cap, stems in pairs for stem in stems]


TREE = [
    {
        "id": "body", "name": "cat_body", "page": "body-care.html",
        "subs": [
            {"id": "deo", "name": "p_deo", "desc": "desc_deo", "sizes": [150, 200, 250], "art": "aerosol",
             "items": _items_from(DEO_BRANDS)},
            {"id": "rollon", "name": "p_rollon", "desc": "desc_rollon", "sizes": [50], "art": "aerosol",
             "items": [("Roll-on", "rollon")]},
            {"id": "hair", "name": "p_hair", "desc": "desc_hair", "sizes": [], "art": "jar",
             "children": [
                 {"name": "h_wax", "items": [(T("h_wax"), "jole")], "art": "jar"},
                 {"name": "h_spray", "items": [(T("h_spray"), s) for s in ("sac-spreyi", "sac-spreyi1", "sac-spreyi2")], "art": "aerosol"},
                 {"name": "h_dry", "items": [(T("h_dry"), None)], "art": "aerosol"},
                 {"name": "h_cond", "items": [(T("h_cond"), None)], "art": "jar"},
                 {"name": "h_powder", "items": [(T("h_powder"), None)], "art": "jar"},
             ]},
            {"id": "edp", "name": "p_edp", "desc": "desc_edp", "sizes": [50, 100], "art": "perfume",
             "items": [("EDP", s) for s in ("edt1", "edt2", "edt3", "edtparfum", "edtparfum1", "edtparfum2", "edtparfum3")]},
            {"id": "mist", "name": "p_mist", "desc": "desc_mist", "sizes": [100, 200, 250], "art": "aerosol",
             "items": [("Oriflowers", s) for s in ("oriflower-body-mist", "oriflower-body-mist1", "oriflower-body-mist2",
                                                   "oriflower-body-mist3", "oriflower-body-mist4")]
             + [("Purixima", s) for s in ("purixima-body-mist", "purixima-body-mist1", "purixima-body-mist2")]},
        ],
    },
    {
        "id": "home", "name": "cat_home", "page": "home-care.html",
        "subs": [
            {"id": "bamboo", "name": "p_bamboo", "desc": "desc_bamboo", "sizes": [], "art": "diffuser",
             "items": [(T("p_bamboo"), None)]},
            {"id": "air", "name": "p_air", "desc": "desc_air", "sizes": [260, 400], "art": "aerosol",
             "items": AIR_SCENTS},
            {"id": "room", "name": "p_room", "desc": "desc_room", "sizes": [500], "art": "aerosol",
             "items": [("Room Freshener", s) for s in ("oda-spreyi-fresh", "oda-spreyi-fresh1", "oda-spreyi-fresh2", "oda-spreyi-fresh3")]},
            {"id": "reed", "name": "p_reed", "desc": "desc_reed", "sizes": [50, 110, 150], "art": "diffuser",
             "items": [("Air Magic", s) for s in ("reed-diffuser", "reed-diffuser1", "reed-diffuser2", "reed-diffuser3",
                                                  "reed-diffuser4", "reed-diffuser5", "reed-diffuser6")]},
        ],
    },
]

# Private label aluminium tubes -----------------------------------------------------------
# model prefix -> (group id, display label, dimension style)
PL_MODELS = {
    "round": ("round", "Round", "dia"),
    "flat": ("flat", "Flat", "w"),
    "flatbogumlu": ("flat", "Flat Curved", "w"),
    "ogival": ("ogival", "Ogival", "w"),
    "ovalogival": ("ogival", "Oval Ogival", "w"),
    "superogival": ("ogival", "Super Ogival", "w"),
    "diamondogival": ("ogival", "Diamond Ogival", "w"),
    "squareflat": ("square", "Square Flat", "w"),
    "transfer": ("transfer", "Transfer", "w"),
    "diamondflat": ("special", "Diamond Flat", "w"),
    "elongfantround": ("special", "Elong Fant Round", "dia"),
    "delongfantround": ("special", "D. Elong Fant Round", "dia"),
    "fuel": ("special", "Fuel", "w"),
    "halfmoon": ("special", "Half Moon", "w"),
    "newsmart": ("special", "New Smart", "w"),
    "wave": ("special", "Wave", "w"),
}
PL_GROUPS = [("round", "t_round"), ("flat", "t_flat"), ("ogival", "t_ogival"),
             ("square", "t_square"), ("transfer", "t_transfer"), ("special", "t_special")]
