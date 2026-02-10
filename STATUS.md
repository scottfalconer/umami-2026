# Umami 2026: Status

This is the current working state of the **Umami 2026 Drupal CMS 2.0 site template** work.

Last updated: 2026-02-10

## Source Of Truth vs Baseline

- This repo is the **baseline install package** (portable output):
  - Recipe: `umami_2026/`
  - Support module: `umami_2026_support/`
  - Theme (Mercury starterkit fork): `umami_theme/`

During development, we used separate local sandboxes (authoring vs clean-install testing) to
avoid export drift. Those sandboxes are not part of this repo.

## Backups / Snapshots

When iterating on large export-driven changes, it’s worth taking rollback snapshots (DB dump +
files tarball). Snapshots are intentionally not committed.

## What’s Working Now

- Install package has the right “shape” for Drupal CMS templates:
  - `drupal/umami_2026` (recipe) exports config + default content.
  - `drupal/umami_theme` is self-contained (fonts/logo/SVGs vendored in theme).
- A fresh install can apply the recipe and produce a functional Umami-like demo.
- Key “demo routes” are Canvas-based (default content exports):
  - Home (`/home`)
  - Articles listing (`/articles`)
  - Recipes listing (`/recipes`)
  - About (`/about`)
- Major early blockers were resolved (missing assets, logo refs, mysql hardcoding, etc.).

## Latest Verification (Clean-Install Tester)

Verified on: 2026-02-10

- Drupal CMS project: `drupal/cms` `2.0.0` (Drupal core `11.3.2`)
- Key packages (installed versions):
  - `drupal/canvas` `1.1.0`
  - `drupal/gin` `5.0.12`
  - `drupal/umami_theme` (local path repository)
- Clean install (exit 0):
  - `ddev exec drush site:install cms/recipes/umami_2026 -y --site-name="Umami Food Magazine" --account-name=admin --account-pass=admin`
  - Expected notices: translation file downloads can log as missing (examples: `drupal-11.3.2.es.po`, `bpmn_io-*.es.po`, `scheduler_content_moderation_integration-*.es.po`).
- Language negotiation:
  - Default language URL prefix removed (`en: ''`, `es: es`) to keep Canvas editing working in multilingual installs.
- Smoke checks (HTML markers present):
  - Home (`/`): `body class="canvas-page path-frontpage"`, `data-component-id="umami:card"`, `id="block-mercury-disclaimer"`, `id="block-mercury-footer-promo"`
  - Articles listing (`/articles`): `view-id-featured_articles` + `view-display-id-block_1`
  - Recipes listing (`/recipes`): `view-id-recipes` + `view-display-id-block_1`
  - Article full (example: `/dairy-free-and-delicious-milk-chocolate`): `data-component-id="umami:node-article-full"`, `.node--view-mode-full`
  - Recipe full (example: `/vegan-chocolate-and-nut-brownies`): `data-component-id="umami:node-recipe-full"`, `recipe-meta`, `ingredients-list`
  - About (`/about`): `body class="canvas-page` and includes “Umami is a fictional food magazine…”
  - Search (`/search`): redirects to `/search/node` and returns 200
  - Favicon: `<link rel="icon" href="/themes/contrib/umami_theme/favicon.svg" type="image/svg+xml">`
- Canvas UI sanity:
  - Logged in and loaded Canvas editor: `/canvas/editor/canvas_page/4` renders (19 `<button>` elements).
  - Known console noise (likely upstream Canvas UI): 3 errors: `<svg> attribute width: Expected length, "auto".`
- Theme sanity:
  - Fixed `umami_theme/templates/includes/preload.twig` to preload vendored fonts (avoids 404s for non-existent `Outfit` files).
- PHP lint (exit 0):
  - `ddev exec php -l cms/web/modules/contrib/umami_2026_support/src/Plugin/ConfigAction/GenerateCanvasComponents.php`
  - `ddev exec php -l cms/web/themes/contrib/umami_theme/src/Hook/ThemeHooks.php`
  - `ddev exec php -l cms/web/themes/contrib/umami_theme/src/RenderCallbacks.php`
- Drupal intent testing ("new site setup" UI installer):
  - Verified the template appears in the installer and completes install successfully (local artifacts not committed).

## Review Notes (2026-02-10)

Notes captured from a review pass in a separate environment. Some items may depend on exact Drupal CMS / Canvas versions; reproduce on a clean install before changing exports.

### Install / Docs

- Composer stability + zsh globbing: `composer require drupal/umami_2026:*@dev -W` fails in zsh unless the package constraint is quoted/escaped.
  - Status: **DONE** (README Quick Start now quotes the constraint).
  - Use: `ddev composer require 'drupal/umami_2026:*@dev' -W`
  - README also documents minimum-stability behavior (keep `minimum-stability: stable`; relax to `dev` only as a fallback).
- Recipe discovery: `find recipes ... -name recipe.yml` may not traverse if `recipes/` is a symlink in the Drupal CMS project.
  - Status: **DONE** (README uses `find -L` to follow symlinks).
  - Use: `ddev exec find -L recipes -maxdepth 4 -name recipe.yml -print`

### Blocking Runtime Errors

- OutOfRangeException on node view for:
  - Recipes: requested version `eeec0b5e05c3f216` not available (seen for `sdc.umami_theme.hero-side-by-side` in `canvas.content_template.node.recipe.full.yml`).
  - Articles: requested version `989a5b66efe6aa48` not available (seen for `sdc.umami_theme.hero-blog` in `canvas.content_template.node.article.full.yml`).
- Hypothesis: Canvas component version hashes differ across environments (different Canvas version and/or different component generation order), but the exported Canvas Content Template config hard-codes `component_version` values.
- Status: **DONE**
  - Fix: removed hard-coded `component_version` keys from exported Canvas Content Templates so the active version can resolve in the target environment.
    - `umami_2026/config/canvas.content_template.node.article.full.yml`
    - `umami_2026/config/canvas.content_template.node.recipe.full.yml`
  - Verified: clean install + load `/node/4` (article) and `/node/2` (recipe) returns 200 and watchdog shows no OutOfRangeException.

### Theme Parity / UX

- Visual parity: language + menus are close to Umami, but layout drifts further down the page (responsive spacing/alignment).
  - Plan: run breakpoint comparisons (mobile/tablet/desktop) against demo_umami (D11) and prioritize fixes in Canvas templates/SDC components over brittle CSS overrides.
  - Status: **IN PROGRESS**
    - Improved parity for node full pages by restoring classic Umami node wrapper markup in SDC components:
      - `umami_theme/components/node-article-full/`
      - `umami_theme/components/node-recipe-full/`
    - Canvas templates now compose into those wrappers (so existing Umami CSS for `.node--view-mode-full` applies).
    - Local visual diffs showed improved parity after introducing the node wrapper markup.
- Favicon missing.
  - Status: **DONE**
  - Fix:
    - Added `umami_theme/favicon.svg`
    - Wired via recipe config (`system.theme.global:favicon`)
  - Verified: `<link rel="icon" ...favicon.svg...>` present on `/`.

### "Canvas Native" Content Model

- Status: **DONE**
  - Removed legacy Umami custom block bundles + placements (banner/footer promo/disclaimer).
  - Replaced them with dedicated SDC components in the theme rendered by `umami_theme/templates/layout/page.html.twig`.
  - Removed the block-related code from `umami_2026_support/`; it now only contains Canvas install-time helpers.

### Canvas Editor Reliability

- Report: Canvas UI does not load when editing content/pages.
  - Status: **WORKAROUND APPLIED (DONE)**
  - Root cause (likely upstream Canvas UI): Canvas is mounted at `/canvas`, but the UI router base path does not currently account for a default-language URL prefix like `/en`, leading to a blank UI when the browser path is `/en/canvas/...`.
  - Fix in this template: remove the default language URL prefix so Canvas UI routes remain `/canvas/...`.
    - Implemented via exported config: `umami_2026/config/language.negotiation.yml` (`url.prefixes.en: ''`, keep `es: es`)
  - Verified: `/canvas/editor/canvas_page/4` renders after login (19 `<button>` elements).

### SDC Component Implementation (Recipe Meta + Ingredients)

- `recipe-meta` and `ingredients-list` components currently look "bolted on": no Tailwind CSS and markup doesn't align with Mercury conventions.
  - Status: **DONE**
  - Fix: updated component twig templates to use Tailwind utility classes (Mercury-style) and stable theme-owned class hooks.
    - `umami_theme/components/recipe-meta/recipe-meta.twig` (`recipe-meta ...`)
    - `umami_theme/components/ingredients-list/ingredients-list.twig` (`ingredients-list ...`)
  - Verified: recipe node full output includes `.recipe-meta` and `.ingredients-list`.

### Process / Reference Implementations

- Compare our exports against other Drupal CMS site template generators (including Umami-style examples) and adopt improvements that reduce custom code and increase portability.
- Alignment notes (video workflow):
  - We already follow the “build the site first, then export” approach (authoring sandbox + clean-install tester) and use `drush site:export` as a starting point.
  - We treat this as a **fresh-install site template** (validated via `drush site:install <recipe>`), not an “apply onto an existing site” recipe (`drush recipe:apply`), due to config conflicts observed in early exports.

### Drupal CMS Installer UX

- Status: **DONE**
  - This template appears in the Drupal CMS "Choose a site template" step.
  - Screenshot asset: `umami_2026/screenshot.webp`

## Gaps vs “2026” Goals (Still Not Done)

The current state demonstrates **Drupal CMS 2.0 + Canvas + Mercury** across key routes and node full pages, but it is still not “done” from a long-term maintainability / public-release perspective.

1. **Canvas Content Templates for full node pages (DONE)**
   - Added `canvas.content_template.*` config entities for:
     - `node.article.full`: `umami_2026/config/canvas.content_template.node.article.full.yml`
     - `node.recipe.full`: `umami_2026/config/canvas.content_template.node.recipe.full.yml`
   - Reduced Layout Builder reliance in the full view displays (now Canvas-driven):
     - `umami_2026/config/core.entity_view_display.node.article.full.yml`
     - `umami_2026/config/core.entity_view_display.node.recipe.full.yml`
   - Removed exported `layout_builder__layout` field config from node bundles/displays to keep the template focused on Canvas for presentation.
   - Added minimal Umami-specific SDC components in the Umami theme:
     - `umami_theme/components/ingredients-list/`
     - `umami_theme/components/recipe-meta/`
2. **Canvas beyond the homepage (DONE)**
   - Articles listing, Recipes listing, and About are now Canvas pages (default content exports).
   - Card rendering for Views listings maps into the Umami Theme’s `card` SDC component via:
     - `umami_theme/templates/node--card.html.twig`
3. **Theme is still doing too much presentation work**
   - We should reduce “override CSS” and move page assembly/presentation into Canvas templates and Mercury SDC components where possible.
4. **Clean-install warnings are resolved; small parity deltas remain**
   - Clean installs should not emit “block plugin was not found” warnings (legacy Umami custom blocks were removed; banner/footer/disclaimer are now theme SDC components).
   - Some spacing/alignment parity issues still show up at responsive breakpoints.
5. **Process documentation (DONE)**
   - AI process notes are captured in `AI_PROCESS.md` (scope, what was automated vs. human-reviewed, and failure modes like invalid Canvas prop expressions).

## Known Failure Mode: `{"detail":"Bad Request"}`

When iterating on Canvas templates via JSON:API (or Canvas structured-data prop expressions), invalid prop-expression strings can cause HTTP 400 responses and/or watchdog errors.

Practical mitigation:
- Copy known-good structured data expression formats from Canvas’ own test fixtures (for example, `NodeTemplatesTest` in the Canvas project).
- Avoid hand-typing the separator characters; treat the expression strings as “copy/paste only”.

## Canvas Component Disables (Install-Time Ordering)

Drupal CMS site templates commonly disable noisy `canvas.component.*` configs (to reduce UI noise in the component picker).

Observed failure (Canvas `1.1.0` + Drupal core `11.3.2`, during recipe apply):
- `There were validation errors in canvas.component.block.announce_block:`
- `active_version: The version d2ee7918676f072c does not match the hash of the settings for this version, expected 370a9ae608f6c7e1.`

Workaround implemented in this template (still present as of 2026-02-10):
- Add a small config action plugin in `umami_2026_support` (`umamiGenerateCanvasComponents`) that calls `ComponentSourceManager::generateComponents()`.
- Invoke it first in `umami_2026/recipe.yml` (under `system.site`) before disabling any `canvas.component.*` configs.
- Mark `canvas.component.*` disables as optional where possible (prefixed `?`) to match Drupal CMS site template base behavior.
  - Note: optional config names cannot use wildcards, so `canvas.component.block.project_browser_block.*` remains non-optional.

Upstream tracking:
- No matching Canvas issue has been identified yet for this exact recipe/config-action validation failure; file one if we want to remove the workaround.
  - Note: Canvas `1.1.0` includes a recipe subscriber that generates components during recipe apply, but it appears to run too late to prevent this validation error (the install fails before the subscriber can run).

## “New Chat” Starting Point

If you’re continuing this work in a new chat/thread, the next focused work items should be:

- Reduce layout drift / brittle CSS overrides by moving page assembly into Canvas templates and Mercury SDC components.
- Compare against other template generator outputs and adopt best practices where it reduces custom code.

## What’s Left To Do (Concrete Next Steps)

0. **Canvas Content Templates for node full pages (DONE)**
   - `node.article.full` and `node.recipe.full` now render via Canvas Content Templates using Mercury SDC components.
1. **Clean-install warnings (DONE)**
   - Removed legacy Umami custom blocks and replaced them with dedicated SDC components rendered by the theme layout.
2. **Fix OutOfRangeException on article/recipe node full pages (DONE)**
   - Removed exported `component_version` keys from the Canvas Content Templates so version hashes do not break portability.
3. **Install docs hardening (DONE)**
   - README now covers zsh quoting/escaping, recipe discovery (`find -L`), and Composer stability behavior.
4. **Add favicon (DONE)**
   - Added `umami_theme/favicon.svg` and wired it up via recipe config.
5. **Make `recipe-meta` + `ingredients-list` Mercury-style (DONE)**
   - Updated twig templates to use Mercury/Tailwind utility classes and stable theme-owned class hooks.
6. **Replace legacy custom blocks with Canvas components (DONE)**
   - Banner/footer promo/disclaimer are now SDC components in `umami_theme/` rendered by `page.html.twig`.
7. **Reduce brittle CSS overrides**
   - Started: replaced the worst utility-class selectors with stable theme-owned classes (for example `umami-container`, `umami-section__container`).
   - Replace “targeting Mercury internals / utility-class selectors” with:
     - Mercury theming/token overrides where supported
     - Canvas templates using Mercury SDC components (cards/heroes/sections)
8. **Verification gates for “serious review”**
   - Clean install from recipe on tester.
   - Drupal CMS UI installer (“new site setup”) intent-test scenario:
     - Artifacts are local-only and intentionally not committed.
   - No PHP errors/notices during install or on key pages.
   - Canvas loads for editing (article + recipe + Canvas pages).
   - Responsive parity checks at common breakpoints (mobile, tablet, desktop) for:
     - Home
     - Articles list + article full
     - Recipes list + recipe full
     - About
     - Search (empty + results)
   - Run basic PHP linting via DDEV for anything we touched (for example: `ddev exec php -l ...`).
   - Reference notes: `MIGRATION.md`

## Public Release Readiness

### Safe to publish

- **Secrets hygiene**: No API keys, tokens, private keys, or other credentials detected in the repo. Note: default content includes pre-hashed demo user password hashes in `umami_2026/content/user/*.yml`. The `.gitignore` excludes local artifacts (`sandboxes/`, `local-packages/`, `token.txt`, `.ddev/`, `vendor/`, `node_modules/`).
- **License**: GPL-2.0-or-later (matches Drupal ecosystem). Vendored fonts are SIL OFL 1.1 (compatible).
- **Clean install verified**: exit 0 on Drupal CMS 2.0.0 / core 11.3.2 / Canvas 1.1.0.
- **Install instructions**: README.md contains a single canonical DDEV-based quickstart.
  - Note: because this package is installed via Composer path repositories (no tagged releases yet),
    the Drupal CMS project must require the recipe with an explicit `*@dev` constraint (see README).

### Remaining blockers for a non-preview release

1. **Naming/versioning**: Package names (`drupal/umami_2026`, `drupal/umami_theme`) and version constraints (`*@dev`) are placeholder/preview. Final names and stable versioning need to be decided before Drupal.org packaging.
2. **"Canvas native" content model**: Legacy Umami custom block bundles (banner/footer promo/disclaimer) were removed and replaced with dedicated SDC components rendered by the theme layout.
3. **CSS fragility**: Some overrides still target Tailwind utility classes (fragile across Mercury/Tailwind upgrades). Documented in `STATUS.md`.
4. **Canvas workarounds / upstream**:
   - Default language URL prefix: template removes the default prefix (`en: ''`) because Canvas UI currently fails to render when the default language has a prefix like `/en`.
   - `umamiGenerateCanvasComponents`: install-time workaround for Canvas component disable ordering; confirm whether it is still required on Canvas `1.1.0` (see `UPSTREAM.md`).
5. **Asset redistribution**: Final review of media file redistribution rights recommended before non-preview release (see `ASSET_ATTRIBUTION.md`).

## Open Questions

- How far to push Canvas adoption for the first "reviewable" release:
  - Minimal: node full Canvas Content Templates + 2-3 key pages.
  - Maximal: everything (including listings and search) becomes Canvas templates.
- Naming/versioning strategy for eventual community contribution (deferred for now per current direction).
