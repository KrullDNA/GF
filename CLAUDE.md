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

### Things that must not be renamed

- **Database tables** (`gf_form`, `gf_entry`, …) hold live forms and entries.
  Renaming needs a migration and buys nothing — the names never surface in the UI.
- **`get_database_version()`** returns the schema version that gates legacy code
  paths, not the plugin version. Setting it to the plugin version made it call
  `GF_Forms_Model_Legacy`, which does not exist here, and fataled the site.

## Build and release

- Version lives in the `Version:` header and `KDNAForms::$version`. Both must match
  the zip filename.
- Build to the repo root as `kdna-forms-X.Y.Z.zip`, containing the plugin folder.
- Verify the shipped zip, not just the working tree — check the changed file inside it.
- Lint before shipping: `php -l` across all PHP, `node --check` across all JS, and
  brace balance across all CSS.
- The site runs LiteSpeed. Inline scripts and CSS are cached in the page HTML, so a
  fix can look like it did nothing until the cache is purged. Say so when relevant.
