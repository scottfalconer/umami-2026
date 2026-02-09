# Umami 2026 Release Review Findings

**Date:** 2026-02-09
**Packages reviewed:**
- `drupal/umami_2026` (drupal-recipe) -- 457 config files, 158 content files
- `drupal/umami_2026_support` (drupal-module) -- helper module for install-time behavior
- `drupal/umami_theme` (drupal-theme) -- Umami look-and-feel theme (forked from the Mercury starterkit)

**Verdict: Technically shippable as a Public Preview, but does not fully accomplish the stated goals from the original thread.**

The code quality is solid and all technical blockers have been resolved, but the packages fall short of what the Drupal community thread was actually asking for in several important ways.

---

## Current Baseline (2026-02-09)

- The theme is a Mercury starterkit fork (generator: `mercury:1.0.0`):
  - `umami_theme/` (no base theme)
- Canvas Content Templates are now implemented for:
  - `node.article.full`
  - `node.recipe.full`
  (see `umami_2026/config/canvas.content_template.*`).
- Key demo routes are Canvas pages (default content exports):
  - Home (`/home`)
  - Articles (`/articles`)
  - Recipes (`/recipes`)
  - About (`/about`)
- Layout Builder is not used for `node.article.full`, `node.recipe.full`, and `node.page.full` view displays.
- Clean install “block plugin was not found” warnings were eliminated by adding a tiny helper module:
  - `umami_2026_support/` provides a stable block plugin that renders `block_content` by UUID.
  - Block placements (`block.block.mercury_*`) now use that plugin so recipes can import block placement config before content exists.

---

## Evaluation Against the Original Ask

The thread ([#3523324](https://www.drupal.org/project/drupal/issues/3523324)) asked:

> *"Take Umami's content model and sample content and bring it to the 2026 age on top of Drupal CMS + Canvas + Mercury... a good stress test of what Mercury is capable of and can maybe have a fast way to get into the planned template gallery on d.o as a free template."*

### What we accomplished

| Goal | Status | Notes |
|------|--------|-------|
| Retain Umami content model + sample content | Done | 19 nodes, 8 users, media, taxonomy, bilingual EN/ES |
| Convert to a recipe (not install profile) | Done | `drupal-recipe` type, applied during install via `drush site:install recipes/umami_2026` |
| Build on Drupal CMS | Done | Requires `drupal_cms_helper`, `canvas`, `gin` |
| Build on Mercury | Partially | Mercury starterkit fork theme, plus ~1500 LOC of Umami-specific CSS (`umami_theme/src/umami.css`) |
| Build on Canvas | Done | Key routes are Canvas pages; Article/Recipe full pages render via Canvas Content Templates |
| Stress test Mercury's capabilities | Partially | Some SDC usage exists (notably `node--card.html.twig` mapping into the card SDC), but the theme still has substantial custom CSS and custom Twig templates |
| Template gallery ready on d.o | Unclear | Namespace and project structure don't match the discussed plan |
| Document how AI agent was used | Done | See `AI_PROCESS.md` (workflow notes + disclosure guidance) |

### What's missing or underdelivered

**Canvas adoption is not complete.** Key routes are Canvas pages and full view mode templates exist for Article/Recipe, but there are still gaps (for example: search, additional view modes, and deeper SDC usage for more “building blocks”).

**The theme still overrides Mercury rather than purely leveraging it.** Most of the Umami look-and-feel lives in `umami_theme/src/umami.css`. Some overrides still target utility classes (for example, `.bg-accent`), which is fragile across Mercury/Tailwind changes.

**Naming doesn't match the community plan.** phenaproxima proposed `drupal/umami` for the recipe and `drupal/umami_theme` for the theme. We currently have `drupal/umami_2026` for the recipe and `drupal/umami_theme` for the theme, so the remaining mismatch is the recipe package name.

---

## Review from Each Participant's Perspective

### Gabor Hojtsy (Product Manager)

**Would likely say:** "Good progress: the key demo routes are Canvas pages, and Article/Recipe full rendering is Canvas-driven. The remaining gap is the Mercury story: a real stress test should use Mercury's components and tokens more directly, with fewer brittle CSS overrides. Also, naming/versioning should align with the template gallery path."

**Gap:** Deeper Mercury SDC/token adoption with less fragile CSS.

### phenaproxima (DevOps / Technical Lead)

**Would likely say:** "The recipe structure is solid -- proper `drupal-recipe` type, `composer.json` with correct dependencies, content export via Drupal CMS Helper. But a few things concern me:

1. I proposed a 1.x/2.x branching strategy where 1.x is archival (based on core tools) and 2.x gets CMS-ified. This jumped straight to a 2.x-style approach without the 1.x foundation. There's no upgrade path from core Umami.

2. The naming is still not aligned. I said I'd use the `umami` namespace for the recipe and `umami_theme` for the theme. The theme matches now (`umami_theme`), but the recipe is still `umami_2026`.

3. The recipe depends on local path-repository packages today (`^1 || *@dev`). That's fine for a public preview, but for Drupal.org/template-gallery readiness we need stable versioning and publishable constraints.

4. The recipe installs 53 modules directly. That's still substantial. A tighter recipe that composes other recipes (where feasible) would be more maintainable."

**Gap:** Namespace alignment, composer installability, and recipe composability.

### catch (Core Maintainer)

**Would likely say:** "This is cleanly decoupled from core, which is good. The main question is whether we have clean-install coverage and whether the recipe remains stable across core/CMS minor updates. `config.strict: false` is acceptable for a site template, but should be used deliberately and documented. I'd also want to see proof it installs end-to-end via Drush on a fresh Drupal CMS install."

**Gap:** Keep “clean install on Drupal CMS” as a hard gate and document any expected notices.

### finnsky (SDC / Components Expert)

**Would likely say:** "It's good that `node--card.html.twig` maps into the theme's card SDC. The next step is to extend that approach so more view modes and blocks render via SDC components (and reduce duplication in classic Twig templates)."

**Gap:** More SDC/component adoption and less bespoke Twig/CSS.

---

## Technical Blockers -- ALL FIXED

### ~~B1. Font/asset paths referenced missing profile~~ FIXED

Fonts vendored in `umami_theme/fonts/`, CSS uses relative paths, `pointer--white.svg` ships locally.

### ~~B2. Logo path referenced missing profile~~ FIXED

Logo ships as `umami_theme/logo.svg`, recipe updated.

### ~~B3. Site name was "Umami Export"~~ FIXED

Changed to "Umami Food Magazine".

### ~~B4. `mysql` module hardcoded~~ FIXED

Removed from install list.

### ~~B5. Page cache `max_age: 0`~~ FIXED

Changed to `900` (15 minutes).

---

## Technical Issues -- Top 4 FIXED

### ~~I1. Stale `@see` to `byte_theme`~~ FIXED

Updated to `\Drupal\umami_theme\Hook\ThemeHooks`.

### ~~I2. Undeclared regions~~ FIXED

All 10 regions now declared in `.info.yml`.

### ~~I3. Unsafe `rendered_by_canvas` check~~ FIXED

Now uses `|default(false)`.

### ~~I4. Missing `font-display`~~ FIXED

All `@font-face` rules now have `font-display: swap`.

---

## Remaining Technical Items (non-blocking)

- **I6.** `admin@example.com` as default email (standard for demos)
- **I8.** Canvas page `owner: target_id: 1` hardcoded
- **M1.** Pre-hashed passwords in user content
- **M2.** Duplicate CSS for recipe collections band
- **M3.** CSS selectors targeting Tailwind utility classes (fragile)
- **M4.** Inline SVG duplication in card templates
- **M5.** `config.strict: false`

---

## Strengths (what went well)

- Clean recipe/theme separation -- correct Drupal CMS 2.0 packaging pattern
- PHP quality: `strict_types`, proper type hints, `SearchPageInterface` handling
- Accessibility: ARIA attributes, `visually-hidden`, skip nav, semantic HTML
- RTL support: comprehensive `[dir="rtl"]` overrides
- Multilingual: full EN/ES content with translations
- Well-documented Twig templates with `@file` docblocks
- JavaScript: proper `Drupal.behaviors` + `once()` pattern
- Complete demo content: 19 nodes, 8 users, media, taxonomy, key Canvas pages, and Canvas Content Templates for node full pages
- Self-contained: all fonts, SVGs, icons, logo vendored locally
- Font performance: `font-display: swap` on all `@font-face`
- Cache correctness: breadcrumb preprocessor adds `url` cache context

---

## What's Needed Before This Truly Accomplishes the Ask

### Phase 1: Naming + Installability (for non-preview release)

1. **Decide naming strategy**: keep `drupal/umami_2026` as the recipe name, or align to the community plan (`drupal/umami`) for eventual Drupal.org template-gallery inclusion.
2. **Fix `*@dev` allowances**: cut tagged releases for all packages and remove the `*@dev` fallback constraints.
3. **Verify install paths**: keep “clean install from recipe” as a hard gate; optionally also verify `drush recipe:apply` if an “apply to existing site” story is desired.

### Phase 2: Canvas + Mercury SDC Adoption (fulfills the actual vision)

4. **Extend SDC mapping**: map remaining card-related view mode templates (for example `card_common` variants) into SDC components to reduce duplicated markup.
5. **Reduce CSS fragility**: replace remaining selectors that target Mercury/Tailwind utility classes with theme-owned wrapper classes and/or SDC props.
6. **Expand Canvas templating**: consider additional Canvas Content Templates (and/or Canvas pages) for other canonical routes that still rely on classic theming (for example: search).
7. **Reduce `umami_theme/src/umami.css`** over time as more presentation moves into SDC/Canvas composition.

### Phase 3: Documentation

9. **Document the AI agent process** -- completed in `AI_PROCESS.md` (what was automated vs. human reviewed, and failure modes like invalid Canvas prop expressions).

---

## Summary

The code is technically solid and the package installs cleanly. But from a maintainer/product perspective, it is still not the full “Umami for 2026” vision: the biggest remaining work is reducing fragile CSS overrides and leaning harder into Canvas composition and SDC components as the source of truth for presentation.

**Recommendation:** Treat the current repo as a public preview and keep tightening the “Mercury + Canvas best practices” story (less CSS, more SDC/Canvas), while deciding the long-term naming/versioning path.
