# Umami Drupal CMS (Umami 2026 Site Template)

> **Status: Public Preview.**  This is a development snapshot intended for
> review and testing. Package names (`drupal/umami_2026`, `drupal/umami_theme`)
> and versioning are not yet final. `*@dev` constraints are expected because
> the packages are installed via Composer **path repositories**.

This repository contains the **install package** for the Umami "2026" rebuild as a **Drupal CMS 2.0 site template**.

- `umami_2026/` (`drupal-recipe`): configuration + default content
- `umami_2026_support/` (`drupal-module`): helper module for install-time behavior
- `umami_theme/` (`drupal-theme`): Umami look-and-feel theme (forked from the Mercury starterkit)

This is intended for **fresh installs** (Drupal CMS site templates), not for applying to an existing site.

Progress / remaining work: see `STATUS.md`.
Background and approach notes: see `MIGRATION.md`.
Maintainer-style findings: see `claude-findings.md`.
AI process notes: see `AI_PROCESS.md`.
Asset attribution / licensing notes: see `ASSET_ATTRIBUTION.md`.

## Tested Versions

- `drupal/cms` `2.0.0` (Drupal core `11.3.2`)
- `drupal/canvas` `1.0.4`

## Quick Start (Fresh Install)

This template composes Drupal CMS recipes (`drupal_cms_*`), so it is intended for `drupal/cms` projects (not a vanilla `drupal/recommended-project`).

From the **Drupal CMS project root** (a `drupal/cms` Composer project with DDEV):

```bash
# 1. Clone this repo into a local-packages directory the container can see.
mkdir -p local-packages
git clone https://github.com/scottfalconer/umami-drupal-cms.git local-packages/umami-drupal-cms

ddev start

# 2. Register the three packages as Composer path repositories.
ddev composer config repositories.umami_2026 path local-packages/umami-drupal-cms/umami_2026
ddev composer config repositories.umami_2026_support path local-packages/umami-drupal-cms/umami_2026_support
ddev composer config repositories.umami_theme path local-packages/umami-drupal-cms/umami_theme

# 3. Install the recipe package + its dependencies.
ddev composer require drupal/umami_2026:*@dev -W

# 4. Locate the installed recipe directory (path may vary).
ddev exec find recipes -maxdepth 4 -name recipe.yml -print
# Expected output includes a path like: recipes/umami_2026/recipe.yml

# 5. Install using the recipe.
# WARNING: This drops and recreates the database.
ddev exec drush site:install recipes/umami_2026 -y --site-name="Umami Food Magazine"
```

After install, visit the site at the DDEV URL. Key demo routes: `/`, `/articles`, `/recipes`, `/about`.

## Re-running Clean Installs (Testing)

```bash
# WARNING: Destructive.
ddev exec drush sql:drop -y
ddev exec drush site:install recipes/umami_2026 -y --site-name="Umami Food Magazine"
```

## Known behavior / caveats

- Drush (and the UI installer) will typically override `system.site:name` during the "configure site" step unless a name is provided (for Drush: `--site-name=...`).
- During install, Drush may log a notice about a missing core translation file download (for example: `drupal-11.3.2.es.po`). This does not affect the recipe itself.
- Canvas component disable ordering: the recipe works around a Canvas `active_version` hash mismatch by regenerating components before disabling them (see `UPSTREAM.md`).
- Some CSS overrides still target Tailwind utility classes from the Mercury starterkit; these are fragile across Mercury/Tailwind upgrades and are documented for future cleanup (see `STATUS.md`).

## What this package avoids

- No dependency on `core/profiles/demo_umami/...` assets:
  - Fonts and SVG assets used by the theme are vendored into `umami_theme/`.
  - The logo is vendored into `umami_theme/logo.svg` and referenced from recipe config.
- No hardcoded database driver module (`mysql`) in the recipe install list.
