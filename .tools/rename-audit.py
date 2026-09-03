#!/usr/bin/env python3
"""
Identifier consistency audit — every class of write/read pair.

Bugs in this fork are almost always one identifier renamed on one side only.
This checks every channel where PHP and JS/CSS have to agree on a name.

v1 checked window['x'] writes and missed globals emitted as `var x = `, which
is how the gf_vars / kdna_vars break survived. That channel is covered here.
"""
import os
import re
import sys
from collections import defaultdict

ROOT = sys.argv[1] if len(sys.argv) > 1 else "."
SKIP = {".git", "node_modules", "vendor"}
PREFIX = re.compile(r"^(gform|gforms|gf|kdna|kdnaform)_")
SIBLINGS = ("gform_", "gforms_", "gf_", "kdnaform_", "kdna_")


def walk(exts, skip_min=False):
    for dp, dn, fns in os.walk(ROOT):
        dn[:] = [d for d in dn if d not in SKIP]
        for f in fns:
            if not f.endswith(exts) or "change_log" in f:
                continue
            if skip_min and ".min." in f:
                continue
            p = os.path.join(dp, f)
            try:
                yield p, open(p, encoding="utf8", errors="ignore").read()
            except OSError:
                pass


PHP, JS, CSS = (".php",), (".js",), (".css",)
php_text = "".join(t for _, t in walk(PHP))
js_text = "".join(t for _, t in walk(JS))
css_text = "".join(t for _, t in walk(CSS))

findings = defaultdict(set)


def sibling_of(name):
    """Other prefixed spellings of the same stem."""
    stem = PREFIX.sub("", name)
    return [p + stem for p in SIBLINGS if p + stem != name]


# --- 1. Globals emitted as `var X = ` in PHP, read in JS -------------------
# This is the channel that let gf_vars through.
for name in set(re.findall(r"var\s+(\w+)\s*=", php_text)):
    if not PREFIX.match(name):
        continue
    if re.search(r"\b" + re.escape(name) + r"\b", js_text):
        continue
    for alt in sibling_of(name):
        if re.search(r"\b" + re.escape(alt) + r"\b", js_text):
            findings["VAR GLOBAL"].add(
                f"PHP emits `var {name}` but JS reads '{alt}'")
            break

# Reverse: JS reads a prefixed global PHP never emits under any spelling.
js_globals = set(re.findall(r"\b((?:gf|gform|gforms|kdna|kdnaform)_\w+)\s*[.\[]", js_text))
for name in js_globals:
    if re.search(r"var\s+" + re.escape(name) + r"\s*=", php_text):
        continue
    if re.search(r"window\[\s*['\"]" + re.escape(name), php_text):
        continue
    if re.search(r"function\s+" + re.escape(name) + r"\b", js_text):
        continue
    for alt in sibling_of(name):
        if re.search(r"var\s+" + re.escape(alt) + r"\s*=", php_text):
            findings["VAR GLOBAL"].add(
                f"JS reads '{name}' but PHP emits `var {alt}`")
            break

# --- 2. window['X'] writes vs JS reads ------------------------------------
for name in set(re.findall(r"window\[\s*['\"](\w+)['\"]\s*\]", php_text)):
    if not PREFIX.match(name) or re.search(r"\b" + re.escape(name) + r"\b", js_text):
        continue
    for alt in sibling_of(name):
        if re.search(r"\b" + re.escape(alt) + r"\b", js_text):
            findings["WINDOW GLOBAL"].add(
                f"PHP writes window['{name}'] but JS reads '{alt}'")
            break

# --- 3. POST names emitted vs read ----------------------------------------
emitted = set(re.findall(r"name=['\"](\w+)['\"]", php_text))
read = set(re.findall(r"rgpost\(\s*['\"](\w+)['\"]", php_text))
for name in emitted:
    if not PREFIX.match(name) or name in read:
        continue
    for alt in sibling_of(name):
        if alt in read:
            findings["POST"].add(f"input name='{name}' but PHP reads rgpost('{alt}')")
            break

# --- 4. Element IDs emitted vs referenced in JS ---------------------------
js_ids = set(re.findall(r"getElementById\(\s*['\"](\w+)['\"]", js_text))
js_ids |= set(re.findall(r"['\"]#(\w+)", js_text))
for name in set(re.findall(r"id=['\"](\w+)['\"]", php_text)):
    if not PREFIX.match(name) or name in js_ids:
        continue
    for alt in sibling_of(name):
        if alt in js_ids:
            findings["ELEMENT ID"].add(f"PHP emits id='{name}' but JS targets '#{alt}'")
            break

# --- 5. IDs JS targets that PHP never emits -------------------------------
php_ids = set(re.findall(r"id=['\"](\w+)['\"]", php_text))
for name in js_ids:
    if not PREFIX.match(name) or name in php_ids:
        continue
    for alt in sibling_of(name):
        if alt in php_ids:
            findings["ELEMENT ID"].add(f"JS targets '#{name}' but PHP emits id='{alt}'")
            break

# --- 6. CSS classes emitted vs defined ------------------------------------
css_classes = set(re.findall(r"\.([a-z][\w-]+)", css_text))
php_classes = set()
for m in re.finditer(r"class=['\"]([^'\"]+)['\"]", php_text):
    php_classes |= {c for c in m.group(1).split() if PREFIX.match(c)}
for c in php_classes:
    if c in css_classes:
        continue
    for alt in sibling_of(c):
        if alt in css_classes:
            findings["CSS CLASS"].add(f"PHP emits class='{c}' but CSS defines '.{alt}'")
            break

# --- 7. Localized objects never referenced in JS --------------------------
for name in set(re.findall(
        r"wp_localize_script\(\s*['\"][^'\"]+['\"]\s*,\s*['\"](\w+)['\"]", php_text)):
    if not re.search(r"\b" + re.escape(name) + r"\b", js_text):
        findings["LOCALIZE"].add(f"'{name}' localized but never referenced in JS")

# --- 8. JS functions called but never defined -----------------------------
defined = set(re.findall(r"function\s+(\w+)\s*\(", js_text))
defined |= set(re.findall(r"(\w+)\s*[:=]\s*function", js_text))
for name in set(re.findall(r"\b((?:gf|gform|kdna|kdnaform)_\w+)\s*\(", js_text)):
    if name in defined:
        continue
    for alt in sibling_of(name):
        if alt in defined:
            findings["JS FUNCTION"].add(f"JS calls {name}() but only {alt}() is defined")
            break

# --- Report ---------------------------------------------------------------
total = 0
for cat in sorted(findings):
    items = sorted(findings[cat])
    total += len(items)
    print(f"\n[{cat}]  {len(items)}")
    for d in items:
        print(f"  {d}")

print(f"\n{'=' * 62}\nTOTAL MISMATCHES: {total}")
