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

## Add-ons

Nine plugins sit on top of core: eight feed add-ons and Stripe. An add-on
breaks silently — it calls a core method that no longer exists and the fatal
only shows on the screen that uses it, or it hooks a filter core stopped firing
and simply never runs. Neither is visible from inside core.

```bash
python3 .tools/check-addons.py
```

Checks the seam from the add-on side: every KDNA class and method they call,
every `$this->` against the framework, every hook they listen for against the
hooks core actually fires, and that none of them still carries a Gravity Forms
name. Run it after anything in core that renames or removes something.

It also checks the seam from the **core** side, which the add-on side cannot see:
every method core calls on an add-on instance (`$this->` and `$addon->`) resolved
against the whole inheritance chain, skipping names a `method_exists` guard
already covers and classes with `__call`. That is how `add_post_payment_actions()`
was found calling `$addon->get_post_payment_actions_config()` when no class
defined it.

**A checker that reports OK may be reporting on nothing.** This one's class regex
could not step over the `abstract ` in `abstract class KDNAPaymentAddOn`, so it
matched the bare word "class" in a docblock, ran `(.*?)` to the end of the file,
and silently indexed zero payment-framework methods — while printing `OK` for
every add-on. When a check passes, confirm it looked at what you think it did:
the run now prints how many classes and methods it found, so a collapse shows up
as the number falling.

## Payments

Stripe is the first payment add-on this fork has had, and payment add-ons take a
different path through the framework than feed add-ons. That path had never
executed, so it carried faults nothing else could reach:

- **A function can exist, be spelled correctly, and still be undefined.**
  `KDNAFeedAddOn::bootstrap()` loaded `class-kdna-feed-processor.php` only when
  the add-on was *not* a payment add-on, while `maybe_process_feed()` calls
  `kdna_feed_processor()` unconditionally — so every paid submission fataled
  with a 500 and nothing in the response. No name-resolution check can see
  this; the fix is to ask which functions live in a conditionally required file
  and are called without a `function_exists` guard.
- **`log_error()` writes nothing without the Logging add-on.** A payment path
  that reports its failures through it reports into the void on most sites. Use
  `log_payment_failure()`, which also writes to PHP's `error_log`.
- **The framework must actually call the gateway.** `validation()` originally
  set up its state and returned without calling `authorize()`, so
  `process_capture()` received an empty authorization and returned immediately.
  The card was charged at Stripe and the entry recorded no payment.
- **Join the submission pipeline, do not intercept it.** An AJAX form posts into
  a hidden iframe and core drives it through `kform/submission/pre_submission`.
  Binding a jQuery `submit` handler and calling `preventDefault()` leaves the
  spinner running for ever, because the iframe never loads. Use
  `kform.utils.addAsyncFilter` and let the submission wait.
- **Read-after-write on the form object.** `kdnaform_pre_render` mutations are
  visible to everything that runs later, so a value derived from the original
  has to be captured at the moment it is replaced, not recomputed downstream.
- **Installing the first payment add-on switches on dormant core code.**
  `KDNAFeedAddOn::add_post_payment_actions()` opens with
  `if ( ! $addon instanceof KDNAPaymentAddOn ) return;`, so until Stripe existed
  the line after it never ran. That line called
  `$addon->get_post_payment_actions_config()`, which nothing defined, and adding
  Stripe fataled the feed settings page of every add-on. Any guard of the form
  "only for a kind of add-on this fork has none of" is hiding untested code;
  grep for `instanceof` in the framework before adding a new kind of add-on.
  Note the two halves already agreed — `add_post_payment_actions()` writes
  `delay_<slug>` and `maybe_delay_feed_processing()` reads it — so the feature
  was fully wired except for the one method that decides where to draw it.

## Build and release

- **Run the gate before every zip. Never skip it.**

  ```bash
  python3 .tools/check-build.py --baseline <git rev of the last build that worked>
  ```

  `--baseline` is a **git revision**, not a version string. Given a tag that does
  not exist it compares against nothing, every pinned token looks new, and it
  blocks the build claiming the body classes moved. There are no tags in this
  repo; use `HEAD` or a commit sha.

  It lints, walks both Save chains, and pins the admin body classes. Save has
  broken three times, always invisibly: the button is wired correctly and the JS
  is fine, and the click lands on something else or on nothing. The gate is what
  checks it without a browser.

- **There are two Save buttons and they fail differently.** `form_detail.php`
  emits `<button class="update-form" onclick="SaveForm();">` only when
  `is_ajax_save_disabled()` is true. Otherwise — the default, so most sites — it
  emits `<button id="ajax-save-form-menu-bar" data-js="ajax-save-form">` with no
  handler in its markup at all, wired from JS by chunk 10 (`form-ajax-save`),
  which needs module 1281 from `281.<hash>.min.js` and reads `admin_save_form`
  off the `kform_admin_config` global. On such a site `SaveForm()` is never
  called, so instrumenting it proves nothing. `check_ajax_save_path()` walks that
  half. Establish which button a site renders before diagnosing anything.
- Version lives in the `Version:` header and `KDNAForms::$version`. Both must match
  the zip filename.
- **Numbering.** A fix or incremental change moves the third digit (3.4.2 → 3.4.3).
  A new feature moves the second (3.4.3 → 3.5.0). The third digit has no ceiling
  — 1.1.95 is a perfectly good version number after ninety-five fixes.

  The test is whether the release adds a capability that did not exist, not how
  much code it took or how visible the result is. Stripe 1.2.0 was numbered as a
  feature for making the early bird price show on the form; that price had been
  advertised as working already, so the release was fixing it and should have
  been 1.1.2. Making a stated feature actually work is a fix, and so is making
  something that already works do it better — Stripe 1.1.5 replaced a hardcoded
  card style with one inherited from the form, which looks like new behaviour
  but lets nobody do anything they could not do before.
- The gate cannot see a click landing on the wrong element, so a build that
  touches editor CSS or the sidebar still needs the Save button pressed once in a
  browser before it is called good.
- Build to the repo root as `kdna-forms-X.Y.Z.zip`, containing the plugin folder.
- Verify the shipped zip, not just the working tree — check the changed file inside it.
- The site runs LiteSpeed. Inline scripts and CSS are cached in the page HTML, so a
  fix can look like it did nothing until the cache is purged. Say so when relevant.
- **LiteSpeed also caches the 404 page against a missing static file's URL.**
  When a request for a file that is not there falls through to WordPress, the
  404 page is stored under that exact URL. Upload the file and the cache still
  answers first, so the file is present on disk and the browser still gets HTML.
  It survives re-uploading, deleting and re-extracting the plugin — only a purge
  clears it. Purge before concluding anything about a missing asset.
- **Reading a ChunkLoadError.** webpack ends the message with `(error: URL)` when
  the request failed and `(missing: URL)` when it *succeeded* — the script loaded
  and registered no chunk. `missing` therefore means HTTP 200 with a body that is
  not the chunk, i.e. an HTML page, which pairs with `Uncaught SyntaxError:
  Unexpected token '<'`. It never means the file is absent from the build.
- **Purging the server cache is only half of it.** Those 404 pages were also
  stored in the *browser's* cache, under the same URLs, with the far-future
  expiry the host sets on `.js`. So after the LiteSpeed purge the server served
  the chunks correctly and the editor stayed broken, because a normal reload
  reused the cached HTML without revalidating. A hard reload is not enough
  either, since chunks are injected after load: open DevTools, tick **Disable
  cache** on the Network tab, and reload with it open — or clear site data.

  The one-line test that separates the two layers: `fetch(url, {cache:'reload'})`
  bypasses the browser cache. If a fetch returns the file but the page load
  failed, the server is fixed and the browser is stale.

  The whole chain, once: a file is missing → WordPress's rewrite answers with the
  404 page at **HTTP 200** → LiteSpeed caches that against the file's URL → the
  browser caches it too → webpack loads a chunk that is really an HTML page →
  the module never registers → the Save button's handler never binds → the
  button shows a hand cursor and does nothing. Six steps, one missing file, and
  every step after the first survives fixing the one before it.

- **Probe the server rather than reasoning about the upload.** `.tools/probe-dist-files.js`
  pastes into the browser console and fetches all 45 files in `assets/js/dist/`,
  comparing status, content type and length against the shipped zip, with a
  control request for a name that is deliberately absent so the site's own 404
  is visible for comparison. Use a unique query string on each request — that is
  what distinguishes a caching fault from a missing file, since the cache is
  keyed on the URL the browser asks for. Compare `t.length` (characters) with
  care: it undercounts bytes for the vendor bundles, which contain non-ASCII.

  `.tools/probe-save.js` instruments the Save chain — every link `SaveForm()`
  needs, which element actually receives a click at the button's centre, and
  wrappers that print a thrown exception instead of swallowing it. Its log
  survives the reload a successful save causes.

  `.tools/probe-save-binding.js` answers the one question that decides an ajax
  Save: does `kform.instances.adminFormSaver` exist? The saver chunk assigns it,
  so its absence means the chunk never ran, and nothing about the button, the
  config or the endpoint matters until that changes. It also prints which
  scripts this page load took from the browser cache rather than the network.

## The form editor is initialised twice

`kdnaform_layout_editor` is registered with `$in_footer = false`, so WordPress
prints it in the head. `KDNAForms::enqueue_scripts()` then echoes the same file
again from `admin_print_footer_scripts` — a hand-written force-print whose
comment says WordPress "marks them as done but never outputs them", which is no
longer true. So `initLayoutEditor()` runs twice and every closure variable
inside it exists in two copies.

This is how the drag-and-drop bug hid. `$elem` — the editor's proxy for "this
field was added by dragging" — is set by the sidebar draggable's `start`, but
calling `.draggable( options )` on an already-initialised element replaces the
callbacks rather than adding a second draggable, so only the *second* closure's
`$elem` is ever set. The first closure's `kform_field_added` handler runs with
`$elem === null`, takes its click-to-add branch, and never calls
`moveByTarget()`. The field stays at index 0, where `StartAddField()` created
it, which is the top of the form.

Two rules came out of it:

- **Do not test a closure variable to find out what the user did.** Test the
  thing that records it. `$elem` is a proxy; the drop indicator's `target` is
  the fact. The handler now asks whether a live drop target exists, which is
  also correct for a clicked field, since a click leaves no indicator.
- **Anything hooked to `kform_field_added` must be idempotent** while the double
  load stands. The handler now marks the field with `data( 'kdnaFieldPlaced' )`
  and returns early on the second pass; before that, the move, the group id,
  `initElement()` and the submit-button insert all ran twice per field.

Fixing the double load is a separate change with its own version. It is a
script-loading change on the form editor screen, which is the same blast radius
as the failures that cost v2.8.0 and v2.9.0, so it does not ride along with an
unrelated fix.
