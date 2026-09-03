#!/usr/bin/env python3
"""Classify every gform_/gforms_/gf_/kdnaform_ identifier by the channels it
lives in, so each one can be renamed, aliased or left alone on evidence rather
than on a blanket find-and-replace.

An identifier is only safe to rename outright when every channel it appears in
is inside this tree. Anything that reaches the database, a saved page, or a
third party's code has to keep answering to its old name as well.

Usage:  python3 .tools/rename-plan.py [--root kdna-forms] [--csv out.csv]
"""

import argparse
import collections
import csv
import os
import re
import sys

IDENT = re.compile(r'\b(?:gform_|gforms_|gf_|kdnaform_|kdna_)[A-Za-z0-9_]+')

SKIP_EXT = {
    '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.webp',
    '.woff', '.woff2', '.ttf', '.eot', '.otf',
    '.zip', '.mo', '.po', '.pot', '.map', '.pdf',
}
SKIP_DIR = {'.git', 'node_modules', 'vendor'}

# Names that live in the database. Renaming any of these needs a migration and
# buys nothing, because none of them ever surface in the UI.
DB_TABLES = re.compile(
    r"(?:\$wpdb->(?:prefix|base_prefix)|get_blog_prefix\(\s*\w*\s*\))\s*\.\s*'([a-z0-9_]+)'"
)
DB_KEYS = re.compile(
    r"(?:get|update|add|delete)_(?:option|site_option|transient|site_transient"
    r"|user_meta|post_meta|term_meta|comment_meta|metadata)\(\s*(?:[^,'\"]+,\s*)?"
    r"['\"]([^'\"]+)['\"]"
)

HOOK = re.compile(
    r"(?:add_filter|add_action|apply_filters|do_action|has_filter|has_action"
    r"|remove_filter|remove_action|did_action|gf_apply_filters|gf_do_action)"
    r"\(\s*(?:array\(\s*)?['\"]([^'\"]+)['\"]"
)
HANDLE = re.compile(
    r"wp_(?:register|enqueue|deregister|dequeue|localize|add_inline|script_is|style_is)"
    r"_(?:script|style)\w*\(\s*['\"]([^'\"]+)['\"]"
)
POSTFIELD = re.compile(r"(?:rgpost|rgempty)\(\s*['\"]([^'\"]+)['\"]|\$_POST\[\s*['\"]([^'\"]+)['\"]")
GETFIELD = re.compile(r"rgget\(\s*['\"]([^'\"]+)['\"]|\$_GET\[\s*['\"]([^'\"]+)['\"]")
NAMEATTR = re.compile(r"""name\s*=\s*(?:\\?['"])([^'"\\]+)""")
IDATTR = re.compile(r"""\bid\s*=\s*(?:\\?['"])([^'"\\ ]+)""")
CLASSATTR = re.compile(r"""class\s*=\s*(?:\\?['"])([^'"\\]*)""")
CSS_SEL = re.compile(r'[.#]([A-Za-z0-9_-]+)')
JS_DEF = re.compile(r'function\s+([A-Za-z0-9_$]+)\s*\(|(?:var|let|const)\s+([A-Za-z0-9_$]+)\s*=')
JS_WINDOW = re.compile(r"""window\[\s*['"]([^'"]+)['"]\s*\]""")

# The JS side has its own hook system and its own jQuery events, and they are
# every bit as public as a PHP filter — gform_post_render is what every custom
# script on the site binds to. Miss this channel and a "purely internal" rename
# silently breaks third-party JS.
JS_HOOK = re.compile(
    r"""gform\.(?:addAction|addFilter|doAction|applyFilters|removeAction|removeFilter)"""
    r"""\s*\(\s*['"]([^'"]+)['"]"""
)
JS_EVENT = re.compile(
    r"""(?:\.trigger|\.on|\.off|\.one|\.bind|\.unbind)\s*\(\s*['"]([A-Za-z0-9_ .]+)['"]"""
)

CHANNELS = [
    'db',            # table, option, meta or transient name  -> never rename
    'hook',          # PHP filter/action name                 -> dual fire
    'js_hook',       # gform.addAction / applyFilters name    -> public to JS
    'js_event',      # jQuery event name                      -> public to JS
    'handle',        # script/style handle                    -> internal, but add-ons use them
    'post',          # POST field name                        -> dual read
    'get',           # GET field name                         -> dual read
    'html_id',       # id attribute in emitted markup
    'html_class',    # class attribute in emitted markup
    'html_name',     # name attribute in emitted markup
    'css',           # selector in a stylesheet
    'js_def',        # defined as a JS function/var
    'js_win',        # window['name'] global
    'php',           # any other PHP occurrence
    'js',            # any other JS occurrence
    'path',          # appears in a file or directory name
]


def collect(root):
    seen = collections.defaultdict(lambda: collections.defaultdict(set))
    counts = collections.Counter()

    def mark(name, channel, path):
        if IDENT.fullmatch(name or ''):
            seen[name][channel].add(path)

    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIR]

        for name in filenames + dirnames:
            for m in IDENT.finditer(name):
                seen[m.group(0)]['path'].add(os.path.join(dirpath, name))

        for filename in filenames:
            ext = os.path.splitext(filename)[1].lower()
            if ext in SKIP_EXT:
                continue
            path = os.path.join(dirpath, filename)
            try:
                text = open(path, encoding='utf-8', errors='ignore').read()
            except OSError:
                continue

            for m in IDENT.finditer(text):
                counts[m.group(0)] += 1

            if ext == '.php':
                for m in DB_TABLES.finditer(text):
                    mark(m.group(1), 'db', path)
                for m in DB_KEYS.finditer(text):
                    mark(m.group(1), 'db', path)
                for m in HOOK.finditer(text):
                    mark(m.group(1), 'hook', path)
                for m in HANDLE.finditer(text):
                    mark(m.group(1), 'handle', path)
                for m in POSTFIELD.finditer(text):
                    mark(m.group(1) or m.group(2), 'post', path)
                for m in GETFIELD.finditer(text):
                    mark(m.group(1) or m.group(2), 'get', path)

            if ext in ('.php', '.html', '.js'):
                for m in IDATTR.finditer(text):
                    mark(m.group(1), 'html_id', path)
                for m in NAMEATTR.finditer(text):
                    mark(m.group(1), 'html_name', path)
                for m in CLASSATTR.finditer(text):
                    for cls in m.group(1).split():
                        mark(cls, 'html_class', path)

            if ext == '.css':
                for m in CSS_SEL.finditer(text):
                    mark(m.group(1), 'css', path)

            if ext == '.js':
                for m in JS_DEF.finditer(text):
                    mark(m.group(1) or m.group(2), 'js_def', path)
                for m in JS_WINDOW.finditer(text):
                    mark(m.group(1), 'js_win', path)

            # Both PHP and JS register JS hooks and trigger jQuery events.
            if ext in ('.php', '.js'):
                for m in JS_HOOK.finditer(text):
                    mark(m.group(1), 'js_hook', path)
                for m in JS_EVENT.finditer(text):
                    for evt in m.group(1).split():
                        mark(evt.split('.')[0], 'js_event', path)

            generic = 'php' if ext == '.php' else 'js' if ext == '.js' else None
            if generic:
                for m in IDENT.finditer(text):
                    seen[m.group(0)][generic].add(path)

    for name in counts:
        seen[name]  # make sure every counted identifier has a row
    return seen, counts


def verdict(name, channels):
    """What may be done to this identifier, and why."""
    if 'db' in channels:
        return 'FREEZE', 'stored in the database'
    if 'path' in channels:
        return 'FREEZE', 'part of a file or directory name'
    if 'hook' in channels:
        return 'DUAL', 'a filter or action third-party code can hook'
    if 'js_hook' in channels:
        return 'DUAL', 'a JS hook name custom scripts can register against'
    if 'js_event' in channels:
        return 'DUAL', 'a jQuery event name custom scripts can bind to'
    if 'html_class' in channels and 'css' in channels:
        return 'DUAL', 'a class in saved markup and customer CSS'
    if 'html_class' in channels:
        return 'DUAL', 'a class emitted into saved markup'
    if 'post' in channels or 'get' in channels or 'html_name' in channels:
        return 'DUAL', 'a request field name'
    if 'css' in channels and not ('php' in channels or 'js' in channels):
        return 'ORPHAN', 'defined in CSS but nothing emits it'
    if 'html_id' in channels or 'js_win' in channels or 'handle' in channels:
        return 'RENAME', 'internal, but both sides must move together'
    return 'RENAME', 'internal only'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--root', default='kdna-forms')
    ap.add_argument('--csv')
    ap.add_argument('--verdict', help='only show this verdict')
    args = ap.parse_args()

    seen, counts = collect(args.root)

    rows = []
    for name in sorted(seen, key=lambda n: (-counts[n], n)):
        channels = {c for c in seen[name] if seen[name][c]}
        v, why = verdict(name, channels)
        rows.append({
            'identifier': name,
            'occurrences': counts[name],
            'verdict': v,
            'reason': why,
            'channels': ' '.join(c for c in CHANNELS if c in channels),
        })

    if args.csv:
        with open(args.csv, 'w', newline='') as fh:
            w = csv.DictWriter(fh, fieldnames=list(rows[0]))
            w.writeheader()
            w.writerows(rows)
        print(f'wrote {len(rows)} rows to {args.csv}')

    tally = collections.Counter(r['verdict'] for r in rows)
    occ = collections.Counter()
    for r in rows:
        occ[r['verdict']] += r['occurrences']
    print(f'{len(rows)} identifiers, {sum(counts.values())} occurrences\n')
    for v in ('FREEZE', 'DUAL', 'RENAME', 'ORPHAN'):
        print(f'  {v:8} {tally[v]:5} identifiers  {occ[v]:6} occurrences')
    print()

    if args.verdict:
        for r in rows:
            if r['verdict'] == args.verdict:
                print(f"{r['occurrences']:6}  {r['identifier']:52} {r['channels']}")
    return 0


if __name__ == '__main__':
    sys.exit(main())
