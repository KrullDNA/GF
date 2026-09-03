#!/usr/bin/env python3
"""Pre-build gate. Nothing gets zipped until this passes.

Beyond linting, it checks the two things that have actually broken this plugin:

  1. The Save button. Save is a chain of names that must agree across PHP and
     JS -- the form id, the hidden meta input, the functions SaveForm calls,
     and the localized object those functions read. Break any link and the
     button silently does nothing, which looks like "Save is broken" and reads
     like nothing at all in the source.

  2. Dormant CSS waking up. Large parts of the admin stylesheet are written
     against the WordPress admin body classes (toplevel_page_gf_edit_forms and
     friends). The fork renamed the menu slugs but not those selectors, so
     roughly fifty rules have never applied, and form_detail.php carries an
     inline <style> that lays the editor out to compensate. "Correcting" a body
     class switches its rules on, the editor toolbar goes position:fixed inside
     a fixed flex container, and the Save button stops taking the click. Both
     v2.8.0 and v2.9.0 were lost to this. So the body-class tokens are pinned:
     if the set changes, that is a deliberate layout change and has to be
     declared, not a side effect of a rename.

Usage:
  python3 .tools/check-build.py --baseline <git-rev>
"""

import argparse
import collections
import os
import re
import subprocess
import sys

ROOT = 'kdna-forms'
BODY_CLASS = re.compile(r'\b(?:toplevel|[a-z][a-z0-9]*)_page_[a-z][a-z0-9_]*')

failures = []
notes = []


def fail(msg):
    failures.append(msg)


def run(cmd):
    return subprocess.run(cmd, shell=True, capture_output=True, text=True)


def walk(*exts):
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in ('.git', 'node_modules')]
        for f in filenames:
            if f.endswith(exts):
                yield os.path.join(dirpath, f)


def read(path):
    return open(path, encoding='utf-8', errors='ignore').read()


# --------------------------------------------------------------------------
def check_lint():
    bad = run(f"find {ROOT} -name '*.php' -print0 | xargs -0 -n1 -P8 php -l 2>&1 "
              "| grep -v 'No syntax errors' || true").stdout.strip()
    if bad:
        fail('PHP syntax errors:\n' + bad)

    bad = run(f"find {ROOT} -name '*.js' -print0 | xargs -0 -n1 -P8 node --check 2>&1 "
              "|| true").stdout.strip()
    if bad:
        fail('JS parse errors:\n' + bad)

    for path in walk('.css'):
        text = read(path)
        if text.count('{') != text.count('}'):
            fail(f'unbalanced braces in {path}')


# --------------------------------------------------------------------------
def check_save_path():
    """Walk the Save chain and prove every link resolves."""
    js_php = read(os.path.join(ROOT, 'js.php'))
    detail = read(os.path.join(ROOT, 'form_detail.php'))

    m = re.search(r'function SaveForm\s*\([^)]*\)\s*\{(.*?)\n\t\}', js_php, re.S)
    if not m:
        fail('SaveForm() not found in js.php -- the save chain cannot be checked')
        return
    body = m.group(1)

    # The form and hidden input SaveForm drives must exist in the editor markup.
    for sel in re.findall(r'jQuery\(\s*["\']#([A-Za-z0-9_-]+)["\']\s*\)', body):
        if not re.search(rf'''id=["']{re.escape(sel)}["']''', detail):
            fail(f'SaveForm() targets #{sel} but form_detail.php emits no such id')

    # The submit target must actually be a form.
    for sel in re.findall(r'jQuery\(\s*["\']#([A-Za-z0-9_-]+)["\']\s*\)\.submit\(\)', body):
        if not re.search(rf'''<form[^>]*id=["']{re.escape(sel)}["']''', detail):
            fail(f'SaveForm() submits #{sel} but that id is not a <form> in form_detail.php')

    # Everything SaveForm calls has to be defined somewhere in the tree.
    all_js = js_php + ''.join(read(p) for p in walk('.js') if '.min.' not in p)
    for fn in set(re.findall(r'\b([A-Z][A-Za-z0-9_]*)\s*\(', body)):
        if fn in ('Array', 'Object', 'String', 'Number', 'Boolean', 'Date', 'JSON'):
            continue
        if not re.search(rf'function\s+{re.escape(fn)}\s*\(', all_js):
            fail(f'SaveForm() calls {fn}() but nothing defines it')

    # ValidateForm reads the localized settings object; PHP must emit it under
    # exactly that name, or every save dies on an undefined property read.
    m = re.search(r'function ValidateForm\s*\([^)]*\)\s*\{(.*?)\n\t\}', js_php, re.S)
    if not m:
        fail('ValidateForm() not found in js.php')
    else:
        php = ''.join(read(p) for p in walk('.php'))
        for obj in sorted(set(re.findall(r'\b([a-z][a-z0-9_]*_vars)\s*[.\[]', m.group(1)))):
            if not re.search(rf'var\s+{re.escape(obj)}\s*=', php):
                fail(f'ValidateForm() reads {obj} but no PHP file emits `var {obj} =`')
            else:
                notes.append(f'save chain reads {obj}, emitted by PHP')

    # The button itself has to be wired to SaveForm.
    if 'SaveForm();' not in detail:
        fail('form_detail.php has no button wired to SaveForm()')


# --------------------------------------------------------------------------
def body_class_tokens():
    found = collections.defaultdict(collections.Counter)
    for path in walk('.css', '.js'):
        for tok in BODY_CLASS.findall(read(path)):
            found[path][tok] += 1
    return found


def check_body_classes(baseline):
    """Admin body-class selectors are pinned against the baseline revision."""
    now = body_class_tokens()

    changed = []
    for path, counts in sorted(now.items()):
        old = run(f'git show {baseline}:{path}').stdout
        was = collections.Counter(BODY_CLASS.findall(old))
        if was != counts:
            gained = sorted((counts - was).elements())
            lost = sorted((was - counts).elements())
            changed.append((path, sorted(set(gained)), sorted(set(lost))))

    if changed:
        lines = ['admin body-class selectors moved -- this changes which CSS applies:']
        for path, gained, lost in changed:
            lines.append(f'  {path}')
            if gained:
                lines.append(f'      now matches: {", ".join(gained)}')
            if lost:
                lines.append(f'      no longer:   {", ".join(lost)}')
        lines.append('  If this is intended, re-run with --allow-body-class-change.')
        fail('\n'.join(lines))


# --------------------------------------------------------------------------
def check_audit(baseline):
    def total(rev=None):
        if rev is None:
            out = run('python3 .tools/rename-audit.py kdna-forms').stdout
        else:
            out = run(f'git stash -q && python3 .tools/rename-audit.py kdna-forms; '
                      f'git stash pop -q').stdout
        m = re.search(r'TOTAL MISMATCHES:\s*(\d+)', out)
        return int(m.group(1)) if m else None

    now = total()
    if now is None:
        fail('could not read the audit total')
        return
    notes.append(f'audit total: {now}')


# --------------------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--baseline', required=True,
                    help='git revision of the last build known to work')
    ap.add_argument('--allow-body-class-change', action='store_true')
    args = ap.parse_args()

    check_lint()
    check_save_path()
    check_audit(args.baseline)
    if not args.allow_body_class_change:
        check_body_classes(args.baseline)

    for n in notes:
        print(f'  .. {n}')
    print()
    if failures:
        print(f'BLOCKED -- {len(failures)} problem(s), do not build:\n')
        for f in failures:
            print(f'  * {f}\n')
        return 1
    print('OK -- save path intact, body classes pinned, lint clean.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
