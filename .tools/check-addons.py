#!/usr/bin/env python3
"""Verify every add-on still fits the core plugin.

An add-on breaks silently. It calls a core method that no longer exists and the
fatal only shows on the screen that uses it, or it hooks a filter core stopped
firing and simply never runs. Neither is visible from the core plugin, so this
checks the seam from the add-on side.

Run it after any change to core that renames or removes something.

Usage:  python3 .tools/check-addons.py
"""

import collections
import glob
import os
import re
import sys

CORE = 'kdna-forms'


def core_surface():
    """What core offers: classes and their methods, functions, and hooks fired."""
    classes = set()
    parents = {}
    methods = collections.defaultdict(set)
    functions = set()
    hooks = set()
    hook_kinds = collections.defaultdict(set)

    for path in glob.glob(f'{CORE}/**/*.php', recursive=True):
        text = open(path, errors='ignore').read()

        # Anchored to the start of a line and allowing abstract/final. The old
        # pattern matched the bare word "class" inside a docblock and then ran to
        # the end of the file, because its lookahead could not step over the
        # "abstract " in "abstract class KDNAPaymentAddOn". The whole payment
        # framework was invisible to this script as a result.
        marks = [
            (m.start(), m.group(1))
            for m in re.finditer(
                r'^\s*(?:abstract\s+|final\s+)*(?:class|interface|trait)\s+'
                r'([A-Za-z_][A-Za-z0-9_]*)', text, re.M
            )
        ]
        for i, (pos, name) in enumerate(marks):
            end = marks[i + 1][0] if i + 1 < len(marks) else len(text)
            body = text[pos:end]
            classes.add(name)
            ext = re.search(r'\bextends\s+([A-Za-z0-9_\\]+)', body[:body.find('{') + 1 or 200])
            if ext:
                parents[name] = ext.group(1).lstrip('\\')
            methods[name] |= set(
                re.findall(r'function\s+&?([a-zA-Z_][A-Za-z0-9_]*)\s*\(', body)
            )

        functions |= set(re.findall(r'^\s*function\s+([a-z_][A-Za-z0-9_]*)\s*\(', text, re.M))

        for m in re.finditer(
            r"\b(apply_filters|do_action|kdna_apply_filters|kdna_do_action)"
            r"\s*\(\s*(?:array\(\s*)?['\"]([^'\"]+)['\"]", text
        ):
            hooks.add(m.group(2))
            hook_kinds[m.group(2)].add('filter' if 'apply_filters' in m.group(1) else 'action')

    framework = set()
    for path in glob.glob(f'{CORE}/includes/addon/*.php'):
        framework |= set(
            re.findall(r'function\s+([a-zA-Z_][A-Za-z0-9_]*)\s*\(', open(path, errors='ignore').read())
        )

    return classes, parents, methods, functions, hooks, hook_kinds, framework


def strip_comments(text):
    """Blank out comments, keeping line numbers, so docblock examples don't count."""
    text = re.sub(r'/\*.*?\*/', lambda m: '\n' * m.group(0).count('\n'), text, flags=re.S)
    text = re.sub(r'(?m)//.*$', '', text)
    return re.sub(r'(?m)^\s*#.*$', '', text)


def core_self_calls():
    """Methods core calls on an add-on instance that no class in the tree defines.

    The add-on side cannot see these. KDNAFeedAddOn::add_post_payment_actions()
    called $addon->get_post_payment_actions_config() for three releases without
    anything defining it, because the guard above it meant the line only ran once
    a payment add-on existed. Adding Stripe fataled every payment feed settings
    page. A function can exist in the design and still be undefined in the code.
    """
    parents = {}
    methods = collections.defaultdict(set)
    bodies = {}

    roots = [CORE] + sorted(d for d in glob.glob('kdna-forms-*') if os.path.isdir(d))
    for root in roots:
        for path in glob.glob(f'{root}/**/*.php', recursive=True):
            if 'stripe-php' in path or f'{os.sep}legacy{os.sep}' in path:
                continue
            text = strip_comments(open(path, errors='ignore').read())
            marks = [
                (m.start(), m.group(1), m.group(2))
                for m in re.finditer(
                    r'^\s*(?:abstract\s+|final\s+)?class\s+([A-Za-z_]\w*)'
                    r'\s*(?:extends\s+([A-Za-z_\\][\w\\]*))?', text, re.M
                )
            ]
            for i, (pos, name, ext) in enumerate(marks):
                end = marks[i + 1][0] if i + 1 < len(marks) else len(text)
                parents[name] = ext.lstrip('\\') if ext else None
                bodies[name] = (path, text[:pos].count('\n'), text[pos:end])
                methods[name] |= set(re.findall(
                    r'^\s*(?:public|protected|private|static|abstract|final|\s)*'
                    r'function\s+&?([A-Za-z_]\w*)\s*\(', text[pos:end], re.M
                ))

    def chain(cls):
        seen = []
        while cls and cls not in seen:
            seen.append(cls)
            cls = parents.get(cls)
        return seen

    # Only classes whose whole ancestry is present can be judged; one rooted in a
    # class we never parsed might inherit the method from there.
    def resolvable(cls):
        return parents.get(chain(cls)[-1]) is None and chain(cls)[-1] in bodies

    # $addon is whichever add-on instance was handed in, not the enclosing class,
    # so it resolves against everything an add-on could be: the three framework
    # bases plus every concrete add-on. Judging it against $this's own hierarchy
    # reported the whole delayed-feed API as missing.
    addon_surface = set()
    for cls in bodies:
        if any(base in chain(cls) for base in
               ('KDNAAddOn', 'KDNAFeedAddOn', 'KDNAPaymentAddOn')):
            addon_surface |= set().union(*(methods.get(k, set()) for k in chain(cls)))

    problems = []
    seen = set()
    for cls, (path, line0, body) in sorted(bodies.items()):
        if not resolvable(cls):
            continue
        own = set().union(*(methods.get(k, set()) for k in chain(cls)))

        # A class with __call answers for anything; ChoiceDecorator forwards the
        # whole field API to the field it wraps.
        if '__call' in own:
            continue

        # Calls the code already guards are not faults. KDNAForms tests for
        # results_fields() before calling it, which is how an optional add-on
        # API is meant to be used.
        guarded = set(re.findall(
            r'(?:method_exists|is_callable)\s*\(\s*\$\w+\s*,\s*[\'"](\w+)[\'"]', body
        ))

        for m in re.finditer(r'\$(this|addon)\s*->\s*([A-Za-z_]\w*)\s*\(', body):
            var, name = m.group(1), m.group(2)
            known = own if var == 'this' else addon_surface
            if (name in known or name in guarded or name.startswith('__')
                    or (cls, var, name) in seen):
                continue
            seen.add((cls, var, name))
            line = line0 + body[:m.start()].count('\n') + 1
            problems.append(f'{cls}: ${var}->{name}() - {path}:{line}')
    return problems


def main():
    classes, parents, methods, functions, hooks, hook_kinds, framework = core_surface()

    undefined = core_self_calls()
    if undefined:
        print('Methods called on an add-on instance that nothing defines:')
        for p in undefined:
            print(f'  - {p}')
        print()

    addons = sorted(
        d for d in glob.glob('kdna-forms-*')
        if os.path.isdir(d)
    )
    if not addons:
        print('no add-ons found')
        return 0

    print(f'core: {len(classes)} classes, {len(functions)} functions, '
          f'{len(hooks)} hooks, {len(framework)} framework methods\n')

    failures = 0
    for addon in addons:
        problems = set()

        # Classes the add-on defines itself are not core's to provide.
        own_classes = {}
        for path in glob.glob(f'{addon}/**/*.php', recursive=True):
            if 'stripe-php' in path or '/vendor/' in path:
                continue
            body = open(path, errors='ignore').read()
            for m in re.finditer(
                r'\b(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)(.*?)'
                r'(?=\n\s*(?:class|interface|trait)\s|\Z)', body, re.S
            ):
                own_classes[m.group(1)] = set(
                    re.findall(r'function\s+([a-zA-Z_][A-Za-z0-9_]*)\s*\(', m.group(2))
                )

        for path in glob.glob(f'{addon}/**/*.php', recursive=True):
            # Bundled third-party libraries answer to themselves, not to core.
            if 'stripe-php' in path or '/vendor/' in path:
                continue

            text = open(path, errors='ignore').read()
            own = set(re.findall(r'function\s+([a-zA-Z_][A-Za-z0-9_]*)\s*\(', text))

            # An add-on class inherits from whatever it extends, which is not
            # always the add-on framework — a custom field extends KDNA_Field.
            # Walk the chain so inherited methods are not reported missing.
            inherited = set(framework)
            for m in re.finditer(r'\bclass\s+[A-Za-z0-9_]+\s+extends\s+([A-Za-z0-9_]+)', text):
                parent = m.group(1)
                seen_parents = set()
                while parent in classes and parent not in seen_parents:
                    seen_parents.add(parent)
                    inherited |= methods[parent]
                    parent = parents.get(parent)
            own = own | inherited

            for m in re.finditer(r'\b([A-Z][A-Za-z0-9_]*)::([a-zA-Z_][A-Za-z0-9_]*)\s*\(', text):
                cls, meth = m.groups()
                if cls in ('self', 'static', 'parent') or not cls.startswith('KDNA'):
                    continue
                if cls in own_classes:
                    # Inherited methods count too; the add-on's parent is core's.
                    if meth not in own_classes[cls] and meth not in framework:
                        problems.add(f'{cls}::{meth}() is not defined by the add-on')
                elif cls not in classes:
                    problems.add(f'{cls} does not exist in core or the add-on')
                elif meth not in methods[cls]:
                    problems.add(f'{cls}::{meth}() does not exist')

            for m in re.finditer(r'\$this->([a-z_][A-Za-z0-9_]*)\s*\(', text):
                name = m.group(1)
                if name not in own and name not in framework:
                    problems.add(f'$this->{name}() is neither its own nor the framework\'s')

            # A callback is only named, never called, so nothing complains until
            # WordPress fires the hook and call_user_func_array throws. That is
            # how array( $this, 'maybe_create_menu' ) shipped in Stripe 1.0.0
            # and fataled the plugins screen on activation.
            for m in re.finditer(
                r"""array\(\s*\$this\s*,\s*['"]([A-Za-z_][A-Za-z0-9_]*)['"]\s*\)""", text
            ):
                name = m.group(1)
                if name not in own and name not in framework:
                    problems.add(f"callback array( $this, '{name}' ) has no such method")

            for m in re.finditer(
                r"""array\(\s*['"]([A-Za-z_][A-Za-z0-9_]*)['"]\s*,\s*['"]([A-Za-z_][A-Za-z0-9_]*)['"]\s*\)""",
                text
            ):
                cls, name = m.groups()
                if cls.startswith('KDNA'):
                    if cls not in classes:
                        problems.add(f"callback array( '{cls}', '{name}' ) — no such class")
                    elif name not in methods[cls]:
                        problems.add(f"callback array( '{cls}', '{name}' ) — no such method")

            # Hooking an action with add_filter silently discards the return
            # value, so the add-on runs and achieves nothing. Stripe 1.1.0
            # registered its init script that way: the field rendered, Stripe
            # never mounted, and the card box was simply empty.
            for m in re.finditer(r"add_(filter|action)\(\s*['\"]([^'\"]+)['\"]", text):
                used, hook = m.groups()
                if not hook.startswith(('kdnaform_', 'kdna_')):
                    continue
                kinds = hook_kinds.get(hook)
                if not kinds:
                    problems.add(f'hooks "{hook}", which core never fires')
                elif used not in kinds:
                    problems.add(
                        f'uses add_{used} for "{hook}", which core fires as '
                        f'{" and ".join(sorted(kinds))}'
                    )

            for m in re.finditer(r'(?<![\w>$:])([a-z_][a-z0-9_]{3,})\s*\(', text):
                fn = m.group(1)
                if fn.startswith(('kdna_', 'kdnaform_')) and fn not in functions and fn not in own:
                    problems.add(f'calls {fn}(), which does not exist')

        # Nothing carrying a Gravity Forms prefix should survive in an add-on.
        stale = set()
        for path in glob.glob(f'{addon}/**/*', recursive=True):
            if not os.path.isfile(path) or 'stripe-php' in path:
                continue
            if not path.endswith(('.php', '.js', '.css')):
                continue
            stale |= set(re.findall(
                r'\b(?:gform|gforms|gfield|ginput|gsection|gchoice|gresults|gaddon|gficon)'
                r'[A-Za-z0-9_-]*|gravity[a-z]*',
                open(path, errors='ignore').read()
            ))
        if stale:
            problems.add(f'still carries Gravity Forms names: {", ".join(sorted(stale)[:4])}')

        status = 'BROKEN ' if problems else 'OK     '
        if problems:
            failures += 1
        print(f'{status} {addon}')
        for p in sorted(problems)[:8]:
            print(f'           - {p}')

    print()
    if failures:
        print(f'{failures} add-on(s) will not work against this core.')
        return 1
    print('All add-ons resolve against this core.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
