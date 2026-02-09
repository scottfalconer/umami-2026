# Umami 2026: Status

This is the current working state of the **Umami 2026 Drupal CMS 2.0 site template** work.

Last updated: 2026-02-09

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

Verified on: 2026-02-09

- Drupal CMS project: `drupal/cms` `2.0.0` (Drupal core `11.3.2`)
- Key packages (installed versions):
  - `drupal/canvas` `1.0.4`
  - `drupal/gin` `5.0.12`
  - `drupal/umami_theme` (local path repository)
- Clean install (exit 0):
  - `ddev exec drush site:install cms/recipes/umami_2026 -y --site-name="Umami Food Magazine"`
  - Expected notices: translation file downloads can log as missing (examples: `drupal-11.3.2.es.po`, `bpmn_io-*.es.po`, `scheduler_content_moderation_integration-*.es.po`).
- Smoke checks (HTML markers present):
  - Home (`/en`): `body class="canvas-page path-frontpage"`, `data-component-id="umami:card"`, `id="block-mercury-disclaimer"`, `id="block-mercury-footer-promo"`
  - Articles listing (`/en/articles`): `view-id-featured_articles` + `view-display-id-block_1`
  - Recipes listing (`/en/recipes`): `view-id-recipes` + `view-display-id-block_1`
  - Article full (example: `/en/give-your-oatmeal-ultimate-makeover`): `hero-blog`
  - Recipe full (example: `/en/super-easy-vegetarian-pasta-bake`): `umami-recipe-meta`, `umami-ingredients-list`
  - About (`/en/about`): `body class="canvas-page` and includes “Umami is a fictional food magazine…”
  - Search (`/en/search`): redirects to `/en/search/node` and returns 200
- PHP lint (exit 0):
  - `ddev exec php -l cms/web/modules/contrib/umami_2026_support/src/Plugin/Block/BlockContentUuidBlock.php`
  - `ddev exec php -l cms/web/modules/contrib/umami_2026_support/src/Plugin/ConfigAction/GenerateCanvasComponents.php`
  - `ddev exec php -l cms/web/themes/contrib/umami_theme/src/Hook/ThemeHooks.php`
  - `ddev exec php -l cms/web/themes/contrib/umami_theme/src/RenderCallbacks.php`

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
   - Clean installs should not emit “block plugin was not found” warnings (custom blocks are placed via `umami_2026_support/`).
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

Observed failure (Canvas `1.0.4` + Drupal core `11.3.2`, during recipe apply):
- `There were validation errors in canvas.component.block.announce_block:`
- `active_version: The version d2ee7918676f072c does not match the hash of the settings for this version, expected 370a9ae608f6c7e1.`

Workaround implemented in this template:
- Add a small config action plugin in `umami_2026_support` (`umamiGenerateCanvasComponents`) that calls `ComponentSourceManager::generateComponents()`.
- Invoke it first in `umami_2026/recipe.yml` (under `system.site`) before disabling any `canvas.component.*` configs.
- Mark `canvas.component.*` disables as optional where possible (prefixed `?`) to match Drupal CMS site template base behavior.
  - Note: optional config names cannot use wildcards, so `canvas.component.block.project_browser_block.*` remains non-optional.

Upstream tracking:
- No matching Canvas issue has been identified yet for this exact recipe/config-action validation failure; file one if we want to remove the workaround.

## “New Chat” Starting Point

If you’re continuing this work in a new chat/thread, the next focused work item should be:

- Reduce brittle CSS overrides, then do a final “public review” pass (packaging/naming/versioning decisions).

## What’s Left To Do (Concrete Next Steps)

0. **Canvas Content Templates for node full pages (DONE)**
   - `node.article.full` and `node.recipe.full` now render via Canvas Content Templates using Mercury SDC components.
1. **Clean-install warnings (DONE)**
   - Avoided install-time block plugin warnings by placing custom blocks via `umami_2026_support/`.
2. **Reduce brittle CSS overrides**
   - Started: replaced the worst utility-class selectors with stable theme-owned classes (for example `umami-container`, `umami-section__container`).
   - Replace “targeting Mercury internals / utility-class selectors” with:
     - Mercury theming/token overrides where supported
     - Canvas templates using Mercury SDC components (cards/heroes/sections)
3. **Verification gates for “serious review”**
   - Clean install from recipe on tester.
   - No PHP errors/notices during install or on key pages.
   - Responsive parity checks at common breakpoints (mobile, tablet, desktop) for:
     - Home
     - Articles list + article full
     - Recipes list + recipe full
     - About
     - Search (empty + results)
   - Run basic PHP linting via DDEV for anything we touched (for example: `ddev exec php -l ...`).
   - Reference checklists / findings:
     - `claude-findings.md`
     - `MIGRATION.md`

## Public Release Readiness

### Safe to publish

- **Secrets hygiene**: No passwords, API keys, tokens, private keys, or credentials detected in the repo. The `.gitignore` excludes local artifacts (`sandboxes/`, `local-packages/`, `token.txt`, `.ddev/`, `vendor/`, `node_modules/`).
- **License**: GPL-2.0-or-later (matches Drupal ecosystem). Vendored fonts are SIL OFL 1.1 (compatible).
- **Clean install verified**: exit 0 on Drupal CMS 2.0.0 / core 11.3.2 / Canvas 1.0.4.
- **Install instructions**: README.md contains a single canonical DDEV-based quickstart.

### Remaining blockers for a non-preview release

1. **Naming/versioning**: Package names (`drupal/umami_2026`, `drupal/umami_theme`) and version constraints (`*@dev`) are placeholder/preview. Final names and stable versioning need to be decided before Drupal.org packaging.
2. **CSS fragility**: Some overrides still target Tailwind utility classes (fragile across Mercury/Tailwind upgrades). Documented in STATUS.md and `claude-findings.md`.
3. **Canvas workaround**: The `umamiGenerateCanvasComponents` config action works around an upstream Canvas hash-mismatch issue. Should be replaced when Canvas addresses the root cause (see `UPSTREAM.md`).
4. **Asset redistribution**: Final review of media file redistribution rights recommended before non-preview release (see `ASSET_ATTRIBUTION.md`).

## Open Questions

- How far to push Canvas adoption for the first "reviewable" release:
  - Minimal: node full Canvas Content Templates + 2-3 key pages.
  - Maximal: everything (including listings and search) becomes Canvas templates.
- Naming/versioning strategy for eventual community contribution (deferred for now per current direction).
