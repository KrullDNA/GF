#!/usr/bin/env python3
"""
Write/read consistency audit for KDNA Forms.

Every fault found so far has the same shape: an identifier was renamed on one
side and not the other, so a write and its matching read no longer agree. This
finds those pairs mechanically.

Run before and after a rename and diff the output. New entries mean the rename
broke something; entries that disappear were pre-existing faults now fixed.
"""
import os
import re
import sys
from collections import defaultdict

ROOT = sys.argv[1] if len(sys.argv) > 1 else "."
PREFIX = re.compile(r"^(gform|gforms|gf|kdna|kdnaform)_")

SKIP_DIRS = {".git", "node_modules", "vendor"}


def walk(exts):
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for fn in filenames:
            if fn.endswith(exts) and "change_log" not in fn:
                p = os.path.join(dirpath, fn)
                try:
                    yield p, open(p, encoding="utf8", errors="ignore").read()
                except OSError:
                    pass


def collect(pattern, exts, group=1):
    """Map identifier -> set of files it appears in."""
    out = defaultdict(set)
    rx = re.compile(pattern)
    for path, text in walk(exts):
        for m in rx.finditer(text):
            out[m.group(group)].add(path)
    return out


PHP = (".php",)
JS = (".js",)
CSS = (".css",)
ALL = (".php", ".js", ".css")

findings = []


def report(category, detail):
    findings.append((category, detail))


# --- 1. POST field names: what the markup emits vs what PHP reads -----------
emitted = collect(r"name=['\"]([a-z0-9_]+)['\"]", PHP)
read = collect(r"rgpost\(\s*['\"]([a-z0-9_]+)['\"]", PHP)

for name in emitted:
    if not PREFIX.match(name):
        continue
    if name in read:
        continue
    # Is the same thing read under a sibling prefix?
    stem = PREFIX.sub("", name)
    for alt_prefix in ("gform_", "gf_", "gforms_", "kdnaform_", "kdna_"):
        alt = alt_prefix + stem
        if alt != name and alt in read:
            report("POST", f"input name='{name}' but PHP reads rgpost('{alt}')")
            break


# --- 2. JS globals: PHP writes window['X'] vs JS reading X -----------------
php_writes = collect(r"window\[\s*['\"]([a-z0-9_]+)['\"]\s*\]\s*(?:\[[^\]]*\])?\s*=", PHP)
js_all = collect(r"\b([a-z][a-z0-9_]{4,})\b", JS)

for name in php_writes:
    if not PREFIX.match(name):
        continue
    if name in js_all:
        continue
    stem = PREFIX.sub("", name)
    for alt_prefix in ("gform_", "gf_", "gforms_", "kdnaform_", "kdna_"):
        alt = alt_prefix + stem
        if alt != name and alt in js_all:
            report("JS GLOBAL", f"PHP writes window['{name}'] but JS reads '{alt}'")
            break
    else:
        report("JS GLOBAL", f"PHP writes window['{name}'] — no JS reads it")


# --- 3. Localized objects: wp_localize_script name vs JS usage -------------
localized = collect(r"wp_localize_script\(\s*['\"][^'\"]+['\"]\s*,\s*['\"]([a-zA-Z0-9_]+)['\"]", PHP)
js_text = "".join(t for _, t in walk(JS))
for name in localized:
    if not re.search(r"\b" + re.escape(name) + r"\b", js_text):
        report("LOCALIZE", f"'{name}' localized but never referenced in any JS")


# --- 4. Script handles: enqueued without a src and never registered --------
registered = set(collect(r"wp_register_script\(\s*['\"]([a-z0-9_]+)['\"]", PHP))
enqueued_bare = set()
for path, text in walk(PHP):
    for m in re.finditer(r"wp_enqueue_script\(\s*['\"]([a-z0-9_]+)['\"]\s*\)", text):
        enqueued_bare.add(m.group(1))
for h in sorted(enqueued_bare - registered):
    if h.startswith(("kdna", "gform", "gf")):
        report("HANDLE", f"'{h}' enqueued with no src and never registered")


# --- 5. Element IDs emitted by PHP vs referenced in JS ---------------------
php_ids = collect(r"id=['\"]([a-z][a-z0-9_]+)['\"]", PHP)
js_id_refs = set()
for path, text in walk(JS):
    js_id_refs |= set(re.findall(r"getElementById\(\s*['\"]([a-z0-9_]+)['\"]", text))
    js_id_refs |= set(re.findall(r"['\"]#([a-z0-9_]+)", text))

for name in php_ids:
    if not PREFIX.match(name):
        continue
    if name in js_id_refs:
        continue
    stem = PREFIX.sub("", name)
    for alt_prefix in ("gform_", "gf_", "gforms_", "kdnaform_", "kdna_"):
        alt = alt_prefix + stem
        if alt != name and alt in js_id_refs:
            report("ELEMENT ID", f"PHP emits id='{name}' but JS targets '#{alt}'")
            break


# --- 6. CSS classes emitted by PHP vs defined in stylesheets ---------------
css_text = "".join(t for _, t in walk(CSS))
css_classes = set(re.findall(r"\.([a-z][a-z0-9_-]+)", css_text))
php_classes = set()
for path, text in walk(PHP):
    for m in re.finditer(r"class=['\"]([^'\"]+)['\"]", text):
        for c in m.group(1).split():
            if PREFIX.match(c):
                php_classes.add(c)

for c in sorted(php_classes):
    if c in css_classes:
        continue
    stem = PREFIX.sub("", c)
    for alt_prefix in ("gform_", "gf_", "gforms_", "kdnaform_", "kdna_"):
        alt = alt_prefix + stem
        if alt != c and alt in css_classes:
            report("CSS CLASS", f"PHP emits class='{c}' but CSS defines '.{alt}'")
            break


# --- Output ---------------------------------------------------------------
by_cat = defaultdict(list)
for cat, detail in findings:
    by_cat[cat].append(detail)

total = 0
for cat in sorted(by_cat):
    items = sorted(set(by_cat[cat]))
    total += len(items)
    print(f"\n[{cat}]  {len(items)}")
    for d in items:
        print(f"  {d}")

print(f"\n{'=' * 60}\nTOTAL MISMATCHES: {total}")
