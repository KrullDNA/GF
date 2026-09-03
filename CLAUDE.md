# Working notes for this repository

## Smarter rather than faster

This repository is a rebranded fork of Gravity Forms. The rename was applied
inconsistently: in dozens of places one side of a name was changed and the other
was not. Almost every bug found in it has been that same fault.

The expensive mistake is fixing these one at a time as symptoms appear. Each one
costs a diagnosis, a build, an install and a round trip, and the next identical
bug is already waiting. **Sweep the whole class in one pass, every time.**

### Before changing anything, run the audit

```bash
python3 .tools/rename-audit.py kdna-forms
```

It checks every channel where PHP and JS/CSS have to agree on a name:

| Channel | Example fault it catches |
|---|---|
| `var X =` globals | `common.php` emitted `gf_vars`; the flyout read `kdna_vars` |
| `window['X']` globals | PHP wrote `kdna_form_conditional_logic`; JS read `gf_form_conditional_logic` |
| POST field names | input `name='gform_theme'`; PHP read `rgpost('kdnaform_theme')` |
| Element IDs | field rendered `kdnaform_payment_method_creditcard`; JS queried `gform_…` |
| CSS classes | PHP emitted `kdnaform_validation_error`; stylesheets defined `.gform_…` |
| Localized objects | localized under a name no JS references |
| JS functions | `kdnaform_apply_rules()` called; only `gf_apply_rules()` defined |

Run it before a change to get a baseline and after to confirm nothing regressed.
New entries mean the change broke something. One known false positive remains:
a wrapper div in `entry_detail.php` whose id resembles the span inside it.

### Rules that came out of getting this wrong

- **Fix the whole class, not the instance.** On finding a mismatched pair, search
  for every other pair of that shape before shipping.
- **Do not infer a cause from reading code when the runtime can be observed.**
  Five explanations for one editor bug were wrong; a console snippet that wrapped
  the call path and printed the real exception found it on the first attempt.
- **Diff against stock Gravity Forms.** A reference copy lives in the repo root.
  It settled where the conditional logic block belongs, proved the sidebar CSS was
  byte-identical, and revealed the `gf_vars` mismatch. Reach for it early.
- **Search the whole tree before concluding something is missing.** The conditional
  logic flyout component was declared absent and hand-rebuilt; it was present all
  along under `js/components/form_editor/`, and the rebuild caused four regressions.
- **Prefer aliasing to renaming on anything already shipped.** Emitting both names,
  or accepting either on read, cannot break existing markup, saved Elementor
  styling or third-party code. Renaming the emitted side can.
- **A CSS alias duplicates the whole selector, never the class token.** v2.8.0
  turned `.gform_settings_form .hr-divider{grid-column:span 2}` into
  `.gform_settings_form,.kdnaform_settings_form .hr-divider{…}`, which applies the
  declaration to the settings form itself. Every multi-part selector treated that
  way leaked its declarations onto a container, the containers grew over the
  toolbar, and the Save button stopped taking the click. The correct alias is
  `.gform_settings_form .hr-divider,.kdnaform_settings_form .hr-divider{…}`.
- **An invisible element still takes the click.** `.conditional_logic_flyout` is
  `position:absolute`, `calc(100vw - 270px)` wide and `calc(100vh - 5.75rem)`
  tall, and it is in the DOM at all times with `opacity:0`. `opacity` and a
  negative `z-index` hide it; neither stops it receiving pointer events, and
  `.editor-sidebar` is `position:sticky` with `z-index:1`, which creates a
  stacking context its `z-index:-10` cannot escape. So the panel sits over the
  toolbar and swallows the Save click. The component already had the right
  idiom on its own child — `.delete_field_choice{opacity:0;pointer-events:none}`
  with `.active{pointer-events:auto}` — and the panel was simply missing it.
  Whenever something is hidden with `opacity` alone, ask what it is now
  covering.
- **A correct rename can still be a regression.** Some names are only inert
  because they are wrong. Fixing one switches on code or CSS that has never run
  in this fork, and whatever was hand-written to compensate now conflicts. Ask
  what starts happening, not just what stops.

### Things that must not be renamed

- **Database tables** (`gf_form`, `gf_entry`, …) hold live forms and entries.
  Renaming needs a migration and buys nothing — the names never surface in the UI.
- **`get_database_version()`** returns the schema version that gates legacy code
  paths, not the plugin version. Setting it to the plugin version made it call
  `GF_Forms_Model_Legacy`, which does not exist here, and fataled the site.
- **Admin body classes** — `toplevel_page_gf_edit_forms`, `forms_page_gf_entries`,
  `forms_page_gf_help`, `forms_page_gf_addons`, `toplevel_page_gf_splash`.
  WordPress derives these from the menu slug and menu title, so with the slug now
  `kdna_edit_forms` the stylesheets are wrong and about fifty rules have never
  applied. `form_detail.php` carries an inline `<style>` that lays the editor out
  as a grid to compensate. Correct the body class and `.gforms_edit_form` becomes
  `position:fixed`/`display:flex` with the toolbar `position:fixed` inside it, the
  Save button stops taking the click, and four other admin screens change layout.
  v2.8.0 and v2.9.0 were both lost to this. Reviving that CSS is a deliberate
  layout change with its own version, never a side effect of a rename.

### Renaming tools

`.tools/rename-plan.py` classifies every identifier by the channels it lives in
(db, hook, request field, markup, CSS, internal) and says whether it may be
renamed, must be dual-emitted, or must be frozen. `.tools/rename-apply.py` applies
an explicit old/new map from `.tools/stages/`, never a prefix substitution, so a
token either appears on the list and moves everywhere or is never touched.

Two traps that have already cost a release:

- **Wrap the alternation.** `(?<!x)a|b|c(?!y)` binds the lookbehind to the first
  branch and the lookahead to the last; every branch between them matches as a
  bare substring. One run rewrote `_page_gf_entries` and `requires_gf_vars` this
  way. It must be `(?<!x)(?:a|b|c)(?!y)`.
- **Check for collisions first.** An identifier that already exists under both
  prefixes is a half-applied rename, i.e. a live bug, and merging the two is the
  fix. One that would collide with an unrelated name is not.

## Build and release

- **Run the gate before every zip. Never skip it.**

  ```bash
  python3 .tools/check-build.py --baseline <last version known to work>
  ```

  It lints, walks the Save chain end to end — the form id, the hidden meta input,
  the functions `SaveForm()` calls, and the localized object `ValidateForm()`
  reads — and pins the admin body classes. Save has broken twice, both times
  invisibly: the button is wired correctly and the JS is fine, and the click just
  lands on something else. The gate is what checks it without a browser.
- Version lives in the `Version:` header and `KDNAForms::$version`. Both must match
  the zip filename.
- Build to the repo root as `kdna-forms-X.Y.Z.zip`, containing the plugin folder.
- Verify the shipped zip, not just the working tree — check the changed file inside it.
- The site runs LiteSpeed. Inline scripts and CSS are cached in the page HTML, so a
  fix can look like it did nothing until the cache is purged. Say so when relevant.
