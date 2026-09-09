#!/usr/bin/env python3
"""Apply an explicit identifier rename map across the tree, one whole token at
a time, and report exactly what moved.

Nothing here is a prefix substitution. Every rename is a named pair, so a token
is either on the list and moves everywhere, or is not on the list and is never
touched. That is what stops `gf_entry` (a live table) from being caught by a
sweep aimed at `gf_entry_wrap`.

Stage files live in .tools/stages/ as `old new` lines, blank lines and #
comments ignored.

Usage:
  python3 .tools/rename-apply.py .tools/stages/01-collisions.map --dry-run
  python3 .tools/rename-apply.py .tools/stages/01-collisions.map
"""

import argparse
import collections
import os
import re
import sys

SKIP_EXT = {
    '.png', '.jpg', '.jpeg', '.gif', '.ico', '.webp',
    '.woff', '.woff2', '.ttf', '.eot', '.otf',
    '.zip', '.mo', '.pdf',
}
SKIP_DIR = {'.git', 'node_modules', 'vendor'}


def load_map(path):
    pairs = []
    seen = set()
    for lineno, raw in enumerate(open(path, encoding='utf-8'), 1):
        line = raw.split('#', 1)[0].strip()
        if not line:
            continue
        parts = line.split()
        if len(parts) != 2:
            sys.exit(f'{path}:{lineno}: expected "old new", got {raw!r}')
        old, new = parts
        if old in seen:
            sys.exit(f'{path}:{lineno}: {old} listed twice')
        seen.add(old)
        pairs.append((old, new))

    # Longest first so a shorter token can never eat part of a longer one, even
    # though the boundary guards should already prevent it.
    pairs.sort(key=lambda p: -len(p[0]))

    targets = collections.Counter(n for _, n in pairs)
    for name, count in targets.items():
        if count > 1:
            sys.exit(f'{path}: {count} identifiers would collapse into {name}')
    return pairs


def build_regex(pairs):
    # The alternation MUST be wrapped: without the group, `(?<!x)a|b|c(?!y)`
    # binds the lookbehind to the first branch and the lookahead to the last,
    # leaving every branch in between to match as a bare substring. That is how
    # an early run rewrote `_page_gf_entries` while renaming `gf_entries`.
    alt = '|'.join(re.escape(old) for old, _ in pairs)
    return re.compile(rf'(?<![A-Za-z0-9_])(?:{alt})(?![A-Za-z0-9_])')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('mapfile')
    ap.add_argument('--root', default='kdna-forms')
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()

    pairs = load_map(args.mapfile)
    lookup = dict(pairs)
    rx = build_regex(pairs)

    hits = collections.Counter()
    per_file = collections.Counter()
    files_changed = 0

    for dirpath, dirnames, filenames in os.walk(args.root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIR]
        for filename in filenames:
            if os.path.splitext(filename)[1].lower() in SKIP_EXT:
                continue
            path = os.path.join(dirpath, filename)
            try:
                text = open(path, encoding='utf-8', errors='surrogateescape').read()
            except OSError:
                continue

            def sub(m):
                hits[m.group(0)] += 1
                per_file[path] += 1
                return lookup[m.group(0)]

            new_text, n = rx.subn(sub, text)
            if n and not args.dry_run:
                with open(path, 'w', encoding='utf-8', errors='surrogateescape') as fh:
                    fh.write(new_text)
            if n:
                files_changed += 1

    print(f'{"would rename" if args.dry_run else "renamed"} '
          f'{sum(hits.values())} occurrences of {len(hits)} identifiers '
          f'across {files_changed} files\n')
    for old, new in pairs:
        flag = '' if hits[old] else '   <-- NOT FOUND'
        print(f'  {hits[old]:5}  {old:44} -> {new}{flag}')

    missing = [old for old, _ in pairs if not hits[old]]
    if missing:
        print(f'\n{len(missing)} identifiers in the map matched nothing; '
              f'check them before trusting this stage.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
