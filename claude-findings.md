# Umami 2026 Release Review Findings

**Date:** 2026-02-07 (updated after fixes + maintainer perspective review)
**Packages reviewed:**
- `drupal/umami_2026` (drupal-recipe) -- 285 config files, 152 content files
- `drupal/umami_2026_support` (drupal-module) -- helper module for install-time behavior
- `drupal/umami_theme` (drupal-theme) -- Umami look-and-feel theme (forked from the Mercury starterkit)

Note: This document started as a point-in-time review when the theme was still a Mercury subtheme (`umami_mercury/`). The current baseline uses `umami_theme/` instead; treat old package/path references as historical context unless explicitly called out as still relevant.

**Verdict: Technically shippable, but does not fully accomplish the stated goals from the original thread.**

The code quality is solid and all technical blockers have been resolved, but the packages fall short of what the Drupal community thread was actually asking for in several important ways.

---

## Update (2026-02-09)

This review predates a significant improvement pass:

- The theme was migrated away from a Mercury subtheme to a Mercury starterkit fork theme:
  - `umami_theme/`
- Canvas Content Templates are now implemented for:
  - `node.article.full`
  - `node.recipe.full`
  (see `umami_2026/config/canvas.content_template.*`).
- Layout Builder is disabled for `node.article.full`, `node.recipe.full`, and `node.page.full` view displays.
- Clean install “block plugin was not found” warnings were eliminated by adding a tiny helper module:
  - `umami_2026_support/` provides a stable block plugin that renders `block_content` by UUID.
  - Block placements (`block.block.mercury_*`) now use that plugin so recipes can import block placement config before content exists.

The remaining gaps below are still broadly valid (Canvas beyond node templates, deeper Mercury SDC adoption, naming/versioning), but the “Canvas usage is token” critique is no longer accurate.

---

## Evaluation Against the Original Ask

The thread ([#3523324](https://www.drupal.org/project/drupal/issues/3523324)) asked:

> *"Take Umami's content model and sample content and bring it to the 2026 age on top of Drupal CMS + Canvas + Mercury... a good stress test of what Mercury is capable of and can maybe have a fast way to get into the planned template gallery on d.o as a free template."*

### What we accomplished

| Goal | Status | Notes |
|------|--------|-------|
| Retain Umami content model + sample content | Done | 19 nodes, 8 users, media, taxonomy, bilingual EN/ES |
| Convert to a recipe (not install profile) | Done | `drupal-recipe` type, applied via `drush recipe:apply` |
| Build on Drupal CMS | Done | Requires `drupal_cms_helper`, `canvas`, `gin` |
| Build on Mercury | Partially | Mercury is base theme, but heavily overridden with 1763 lines of custom CSS |
| Build on Canvas | Partially | Homepage is a Canvas page; Article/Recipe full pages render via Canvas Content Templates |
| Stress test Mercury's capabilities | No | This is a CSS skin on Mercury, not a showcase of Mercury's component system |
| Template gallery ready on d.o | Unclear | Namespace and project structure don't match the discussed plan |
| Document how AI agent was used | Done | See `AI_PROCESS.md` (workflow notes + disclosure guidance) |

### What's missing or underdelivered

**Canvas adoption is still incomplete.** Article and Recipe full pages now render via Canvas Content Templates, but the “site as a template” story would be stronger if more key pages were Canvas-based (recipes listing, articles listing, about page) and if more Mercury SDCs were used directly (hero/card/cta), reducing bespoke Twig/CSS.

**The theme overrides Mercury rather than leveraging it.** The 1763-line `theme.css` overrides Mercury's design tokens, disables all shadows, replaces the typography system, and targets Mercury's Tailwind utility classes (`.min-h-\[500px\]`, `.bg-accent`, `.container.mx-auto.px-4`) with CSS specificity hacks. This is the opposite of "a stress test of what Mercury is capable of" -- it's a demonstration of working *around* Mercury.

**Naming doesn't match the community plan.** phenaproxima proposed `drupal/umami` for the recipe and `drupal/umami_theme` for the theme. We have `drupal/umami_2026` and `drupal/umami_mercury`. This means it can't slot into the `umami` d.o project namespace that was being prepared, and creates confusion about the relationship to the community effort.

---

## Review from Each Participant's Perspective

### Gabor Hojtsy (Product Manager)

**Would likely say:** "This preserves the sample content, which is great, but it doesn't bring Umami to 'the 2026 age.' The Canvas usage is a checkbox -- only 1 page with 2 plain sections. I specifically said 'a good stress test of what Mercury is capable of' and that 'applying the look and feel on top of Mercury is easier than converting the classic templates.' What we got instead is 1763 lines of CSS fighting Mercury's design system. The inner pages (recipes, articles) are still Layout Builder with classic Twig templates -- they should be Canvas pages using Mercury SDCs for cards, heroes, CTAs, etc. Also, the naming (`umami_2026`, `umami_mercury`) doesn't align with what we discussed for the d.o project namespace."

**Gap:** Canvas and Mercury SDC adoption. A second pass should push more “page assembly” into Canvas and use more Mercury built-in components (hero/card/cta) instead of custom Twig templates and large CSS overrides.

### phenaproxima (DevOps / Technical Lead)

**Would likely say:** "The recipe structure is solid -- proper `drupal-recipe` type, `composer.json` with correct dependencies, content export via Drupal CMS Helper. But a few things concern me:

1. I proposed a 1.x/2.x branching strategy where 1.x is archival (based on core tools) and 2.x gets CMS-ified. This jumped straight to a 2.x-style approach without the 1.x foundation. There's no upgrade path from core Umami.

2. The naming is wrong. I said I'd use the `umami` namespace for the recipe and `umami_theme` for the theme. `umami_2026` and `umami_mercury` create a parallel project that can't merge into the community effort I was coordinating.

3. `drupal/umami_mercury: *@dev` (and the helper module added later) in `composer.json` means this can't be installed from packagist — you need a path repository. For a site template that's supposed to be installable by end users, that's a problem.

4. The recipe installs 58 modules. That's essentially the entire Umami install profile in recipe form. A tighter recipe that composes other recipes (e.g., a `drupal_cms_blog` recipe for articles) would be more maintainable."

**Gap:** Namespace alignment, composer installability, and recipe composability.

### catch (Core Maintainer)

**Would likely say:** "This is cleanly decoupled from core, which is good -- no namespace conflicts with `demo_umami`. The content model export looks correct and the config is clean. My main concern is whether this was tested with `drush recipe:apply` on a fresh Drupal CMS install. The recipe has `strict: false` which papers over config conflicts, but I'd want to see it actually run. Also, the Layout Builder usage on recipe/article nodes means we're still carrying forward the same architecture that made Umami hard to maintain in core. If this is supposed to be the future, it should lean harder into Canvas."

**Gap:** Integration testing and forward-looking architecture.

### finnsky (SDC / Components Expert)

**Would likely say:** "I mentioned 'there are a lot of hidden treasures' in Umami's SDCs. None of them survived here. The theme doesn't use any SDCs -- it has 17 classic Twig templates that reproduce Umami's old markup patterns. Mercury has a card SDC, a hero SDC, a section SDC, a CTA SDC -- none are used for the node display. The only Mercury SDC usage is `sdc.mercury.section` on the homepage Canvas page. If we're rebuilding Umami for 2026, the card templates (`node--card.html.twig`, `node--card-common.html.twig`, `node--card-common-alt.html.twig`) should be rendering Mercury's card SDC, not hand-rolled `<article class="umami-card">` markup."

**Gap:** SDC/component adoption. The theme should use Mercury's components rather than reimplementing Umami's markup.

---

## Technical Blockers -- ALL FIXED

### ~~B1. Font/asset paths referenced missing profile~~ FIXED

Fonts vendored in `umami_mercury/fonts/`, CSS uses relative paths, `pointer--white.svg` ships locally.

### ~~B2. Logo path referenced missing profile~~ FIXED

Logo ships as `umami_mercury/logo.svg`, recipe updated.

### ~~B3. Site name was "Umami Export"~~ FIXED

Changed to "Umami Food Magazine".

### ~~B4. `mysql` module hardcoded~~ FIXED

Removed from install list.

### ~~B5. Page cache `max_age: 0`~~ FIXED

Changed to `900` (15 minutes).

---

## Technical Issues -- Top 4 FIXED

### ~~I1. Stale `@see` to `byte_theme`~~ FIXED

Updated to `\Drupal\mercury\Hook\ThemeHooks`.

### ~~I2. Undeclared regions~~ FIXED

All 10 regions now declared in `.info.yml`.

### ~~I3. Unsafe `rendered_by_canvas` check~~ FIXED

Now uses `|default(false)`.

### ~~I4. Missing `font-display`~~ FIXED

All `@font-face` rules now have `font-display: swap`.

---

## Remaining Technical Items (non-blocking)

- **I5.** Empty `templates/block/` directory
- **I6.** `admin@example.com` as default email (standard for demos)
- **I7.** Unused `mobile`/`narrow`/`wide` breakpoints
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
- Complete demo content: 19 nodes, 8 users, media, taxonomy, Canvas homepage
- Self-contained: all fonts, SVGs, icons, logo vendored locally
- Font performance: `font-display: swap` on all `@font-face`
- Cache correctness: breadcrumb preprocessor adds `url` cache context

---

## What's Needed Before This Truly Accomplishes the Ask

### Phase 1: Namespace + Installability (ship-blocking)

1. **Rename packages** to `drupal/umami` and `drupal/umami_theme` (or coordinate with the d.o namespace plan)
2. **Fix `*@dev` constraint** -- `drupal/umami_mercury` needs a proper version constraint or the packages need to be published together
3. **Test `drush recipe:apply`** on a fresh Drupal CMS install to verify the recipe actually works end-to-end

### Phase 2: Canvas + Mercury SDC Adoption (fulfills the actual vision)

4. **Convert recipe/article full view** to Canvas pages using Mercury SDCs instead of Layout Builder + custom Twig
5. **Use Mercury's card SDC** for the card view modes instead of `node--card.html.twig` with hand-rolled markup
6. **Use Mercury's hero SDC** for the banner blocks instead of custom `block--bundle--banner-block.html.twig`
7. **Build more pages in Canvas** -- Recipes listing, Articles listing, About page should be Canvas pages, not just Views with traditional block layout
8. **Reduce theme.css** significantly -- if Mercury SDCs are used, much of the 1763-line override file becomes unnecessary

### Phase 3: Documentation

9. **Document the AI agent process** -- completed in `AI_PROCESS.md` (what was automated vs. human reviewed, and failure modes like invalid Canvas prop expressions).

---

## Summary

The code is technically solid and all bugs/blockers are fixed. But from a maintainer perspective, this is **Phase 0.5** of what was asked for: the content model was preserved and packaged as a recipe, but the "bring it to the 2026 age" part -- genuine Canvas page building and Mercury SDC integration -- hasn't happened yet. What we have is classic Umami in a recipe wrapper with a CSS skin on Mercury, not a reimagining of Umami on top of Drupal CMS's new tools.

**Recommendation:** Ship the current packages as a `0.x` or `alpha` release to get the content model and recipe structure out the door, but communicate clearly that a second pass for Canvas/SDC integration is needed before this fulfills the community vision. And write up the AI agent process documentation.
