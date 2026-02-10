# Umami 2026 (Drupal CMS 2.0) Migration Notes

This repository contains the current **public preview** output of the “Umami, but for 2026” effort: an Umami-like demo built on **Drupal CMS 2.0 + Canvas**, packaged as a **recipe** plus a **theme** (forked from the Mercury starterkit).

Start here:

- Install + test instructions: `README.md`
- What works / what’s left: `STATUS.md`
- Upstream Canvas context: `UPSTREAM.md`
- AI disclosure notes: `AI_PROCESS.md`

## What Exists (Source Of Truth vs Baseline)

- This repository is the **install package** (portable output) for the Umami 2026 site template:
  - Recipe: `umami_2026/` (`drupal-recipe`)
  - Support module: `umami_2026_support/` (`drupal-module`)
  - Theme (Mercury starterkit fork): `umami_theme/` (`drupal-theme`)

During development, we used separate local sandboxes (authoring vs clean-install testing) to
avoid “works on my machine” drift. Those sandboxes are not part of this repo.

## Snapshots

When working on large export-driven changes (Canvas templates, theme restructuring, etc.), it’s
worth taking rollback snapshots (DB dump + files). Snapshots are intentionally not committed.

## The Approach We Followed

1. **Start from Drupal CMS (not Drupal core’s `demo_umami`).**
   - We intentionally did not “rebuild Umami as an install profile”.
   - We treated this as a Drupal CMS 2.0 template/recipe effort from the beginning.
2. **Build the site like a normal CMS site first (UI-first where appropriate).**
   - Establish content types, fields, taxonomy, media, translations, menus, and demo content.
   - Start from the Mercury starterkit to create a standalone theme, then customize it for Umami branding.
3. **Adopt Canvas where it helps reduce bespoke presentation code.**
   - Canvas is used on the homepage (Canvas page content export).
   - Article + Recipe full pages render via Canvas Content Templates.
4. **Export the result as an installable recipe.**
   - Export produces recipe config + default content.
   - We used `drush site:export` as the starting point, then curated the output for portability (remove environment-specific config, remove Umami profile/theme dependencies, etc.).
   - The theme is packaged separately, with assets vendored so it does not depend on core’s Umami profile/theme paths.
5. **Validate by fresh installing on a tester site.**
   - The tester sandbox exists specifically to catch “works on my site” gaps (missing config deps, missing assets, install-time warnings).

## How An AI Agent Helped (Concrete)

We used the agent for:

- **Inventory + packaging hygiene**
  - Finding and removing references to core Umami profile asset paths.
  - Vendoring fonts/SVG/logo into the theme to avoid external dependency.
  - Auditing recipe module lists for environment-specific or inappropriate dependencies.
- **Config/content review at scale**
  - Scanning exported config for problematic settings (cache max-age, strictness, etc.).
  - Identifying install-time warnings and their likely root causes (ordering/deps).
- **Repeatable documentation**
  - Maintaining a clear “baseline vs source of truth” record and install instructions.
  - Capturing known failure modes and mitigations.

We did not rely on the agent for:

- Product/design decisions (what “perfect parity” means at each breakpoint).
- Any irreversible actions (destructive changes were always snapshot first).

## Known Failure Modes (So We Don’t Re-Learn Them)

- `{"detail":"Bad Request"}` during Canvas iteration usually indicates malformed Canvas structured-data prop expressions (JSON:API-driven edits).
  - Practical mitigation: copy known-good formats from Canvas’ own test fixtures (for example, `NodeTemplatesTest` in the Canvas project).
- Clean install warnings like “block plugin was not found” usually indicate configuration referencing blocks/plugins that are not yet present at the point the recipe applies them.
  - Mitigation used here: avoid exporting legacy Umami custom blocks for the template (banner/footer promo/disclaimer are now theme SDC components rendered by the page layout).

## What’s Left Before “Serious Drupal Community Review”

1. **Reduce brittle custom presentation code**
   - Move page assembly into Canvas templates and Umami Theme SDC components (forked from Mercury starterkit).
   - Reduce CSS that targets utility-class selectors or internal Mercury structure.
2. **Clean install and parity gates**
   - Fresh install should produce clean logs (or documented, understood exceptions).
   - Mobile/tablet/desktop layouts match baseline expectations (spacing, alignment, no duplication).

Progress tracking and remaining tasks: `STATUS.md`.

Maintainer-style findings and remediation ideas: `claude-findings.md`.
