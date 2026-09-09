#!/usr/bin/env python3
"""Rename GF prefixes that sit *inside* a longer identifier.

The token-level tool in rename-apply.py works on whole identifiers, and the
inventory that feeds it matches greedily. That combination silently misses
compound names: `.gform-util-gform-admin-color-zircon` was captured as one
token, so only its leading prefix moved and the inner `gform-admin-...` stayed
behind — 174 occurrences of that one alone.

This pass works on the prefix segment wherever it appears, and protects the
identifiers that must not move by matching them first and handing them back
untouched. Anything on the frozen list is therefore safe even though its
segment is spelled exactly like the ones being replaced.

Usage:
  python3 .tools/rename-segments.py --dry-run
  python3 .tools/rename-segments.py
"""

import argparse
import collections
import os
import re
import sys

ROOT = 'kdna-forms'
SKIP_EXT = {'.png', '.jpg', '.jpeg', '.gif', '.ico', '.webp', '.zip', '.mo', '.pdf'}
SKIP_DIR = {'.git', 'node_modules', 'vendor'}

# Database tables, and the option and meta keys holding this site's data.
FROZEN_DB = """
gf_addon_feed gf_form gf_form_meta gf_form_view gf_form_revisions gf_entry
gf_entry_meta gf_entry_notes gf_draft_submissions gf_rest_api_keys gf_db_version
gf_previous_db_version gf_upgrade_lock gf_submissions_block gf_imported_theme_file
gf_telemetry_data gf_last_telemetry_run gf_dimissed_ gf_addon_payment_transaction
gf_addon_payment_callback
""".split()

# The admin body classes WordPress derives from the menu slug. Correcting these
# switches on about fifty stylesheet rules that have never applied in this fork,
# which moves the editor toolbar under the Save button. See CLAUDE.md.
FROZEN_BODY = """
toplevel_page_gf_edit_forms toplevel_page_gf_splash forms_page_gf_entries
forms_page_gf_help forms_page_gf_addons
""".split()

SEGMENTS = [
    ('gforms-', 'kforms-'), ('gforms_', 'kforms_'),
    ('gform-', 'kform-'), ('gform_', 'kform_'),
    ('gfield-', 'kfield-'), ('gfield_', 'kfield_'),
    ('ginput_', 'kinput_'), ('ginput-', 'kinput-'),
    ('gsection_', 'ksection_'), ('gchoice_', 'kchoice_'), ('gchoice-', 'kchoice-'),
    ('gresults_', 'kresults_'), ('gresults-', 'kresults-'),
    ('gaddon_', 'kaddon_'), ('gaddon-', 'kaddon-'),
    ('gficon-', 'kficon-'),
    ('gf_', 'kdna_'), ('gf-', 'kdna-'),
]


def build(extra_frozen):
    frozen = sorted(set(FROZEN_DB + FROZEN_BODY + list(extra_frozen)), key=len, reverse=True)
    # rg_gforms_* are option keys; guard the whole family in one branch.
    frozen_alt = '|'.join(re.escape(f) for f in frozen) + r'|rg_gforms_[A-Za-z0-9_]+'
    seg_alt = '|'.join(re.escape(s) for s, _ in sorted(SEGMENTS, key=lambda p: -len(p[0])))
    rx = re.compile(rf'(?P<frozen>{frozen_alt})|(?P<seg>{seg_alt})')
    lookup = dict(SEGMENTS)
    return rx, lookup


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--freeze', nargs='*', default=[],
                    help='extra identifiers to leave untouched')
    args = ap.parse_args()

    rx, lookup = build(args.freeze)
    hits = collections.Counter()
    kept = collections.Counter()
    changed = 0

    for dirpath, dirnames, filenames in os.walk(ROOT):
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
                if m.group('frozen'):
                    kept[m.group('frozen')] += 1
                    return m.group(0)
                hits[m.group('seg')] += 1
                return lookup[m.group('seg')]

            new, n = rx.subn(sub, text)
            if new != text:
                changed += 1
                if not args.dry_run:
                    with open(path, 'w', encoding='utf-8', errors='surrogateescape') as fh:
                        fh.write(new)

    print(f'{"would rewrite" if args.dry_run else "rewrote"} '
          f'{sum(hits.values())} segments across {changed} files\n')
    for seg, n in hits.most_common():
        print(f'  {n:6}  {seg:12} -> {lookup[seg]}')
    print(f'\nleft alone: {sum(kept.values())} occurrences of '
          f'{len(kept)} frozen identifiers')
    for name, n in kept.most_common(8):
        print(f'  {n:6}  {name}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
