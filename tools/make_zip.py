#!/usr/bin/env python3
"""Build dist/uzman-site.zip — upload to cPanel File Manager and Extract.  python3 tools/make_zip.py"""
import os
import zipfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
INCLUDE_DIRS = ["app", "admin", "api", "assets"]
INCLUDE_FILES = ["index.php", ".htaccess", "KURULUM.md"]
KEEP = {"data": [".htaccess", "index.php", ".gitignore"], "uploads": [".htaccess", "index.php", ".gitignore"]}
os.makedirs(os.path.join(ROOT, "dist"), exist_ok=True)
out = os.path.join(ROOT, "dist", "uzman-site.zip")
n = 0
with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
    for f in INCLUDE_FILES:
        if os.path.exists(os.path.join(ROOT, f)):
            z.write(os.path.join(ROOT, f), f); n += 1
    for d in INCLUDE_DIRS:
        for base, _, files in os.walk(os.path.join(ROOT, d)):
            for fn in files:
                full = os.path.join(base, fn)
                z.write(full, os.path.relpath(full, ROOT)); n += 1
    for d, files in KEEP.items():
        for fn in files:
            full = os.path.join(ROOT, d, fn)
            if os.path.exists(full):
                z.write(full, f"{d}/{fn}"); n += 1
        z.writestr(f"{d}/.keep", "")
print(f"{out}: {n} files, {os.path.getsize(out) / 1048576:.1f} MB")
