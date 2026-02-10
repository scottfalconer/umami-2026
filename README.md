# Umami 2026 Site Template

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
How this template was built: see `MIGRATION.md`.
AI process notes: see `AI_PROCESS.md`.
Upstream tracking / known issues: see `UPSTREAM.md`.
Asset attribution / licensing notes: see `ASSET_ATTRIBUTION.md`.

## Tested Versions

- `drupal/cms` `2.0.0` (Drupal core `11.3.2`)
- `drupal/canvas` `1.1.0`

## Quick Start (Fresh Install)

This template composes Drupal CMS recipes (`drupal_cms_*`), so it is intended for `drupal/cms` projects (not a vanilla `drupal/recommended-project`).

From the **Drupal CMS project root** (a `drupal/cms` Composer project with DDEV):

```bash
# 1. Clone this repo into a local-packages directory the container can see.
mkdir -p local-packages
git clone <your-repo-url> local-packages/umami-drupal-cms

ddev start

# 2. Register the three packages as Composer path repositories.
ddev composer config repositories.umami_2026 path local-packages/umami-drupal-cms/umami_2026
ddev composer config repositories.umami_2026_support path local-packages/umami-drupal-cms/umami_2026_support
ddev composer config repositories.umami_theme path local-packages/umami-drupal-cms/umami_theme

# 3. Install the recipe package + its dependencies.
# Note: quote the constraint to avoid zsh globbing on `*`.
ddev composer require 'drupal/umami_2026:*@dev' -W

# 4. Locate the installed recipe directory (path may vary).
ddev exec find -L recipes -maxdepth 4 -name recipe.yml -print
# Expected output includes a path like: recipes/umami_2026/recipe.yml

# 5. Install using the recipe directory found above.
# WARNING: This drops and recreates the database.
ddev exec drush site:install recipes/umami_2026 -y --site-name="Umami Food Magazine" --account-name=admin --account-pass=admin
```

### Composer Stability Note (Important)

This install package is currently consumed via Composer **path repositories** and does not yet have tagged releases on packages.drupal.org. Keep your Drupal CMS project `minimum-stability` at `stable` and use the explicit `*@dev` constraint shown above.

If you accidentally run `composer require drupal/umami_2026` without `*@dev`, Composer will usually fail due to minimum stability. Either re-run the require with `*@dev`, or (less preferred) temporarily relax stability in your Drupal CMS project:

```bash
ddev composer config minimum-stability dev
ddev composer config prefer-stable true
```

After install, visit the site at the DDEV URL. Key demo routes: `/`, `/articles`, `/recipes`, `/about`.

To add more Drupal CMS features after install, use **Extend > Recommended** (`/admin/modules/browse/recipes`). This template also ships a small curated list in `umami_2026/recommended-add-ons.yml`.

## Re-running Clean Installs (Testing)

```bash
# WARNING: Destructive.
ddev exec drush sql:drop -y
ddev exec drush site:install recipes/umami_2026 -y --site-name="Umami Food Magazine" --account-name=admin --account-pass=admin
```

## Known behavior / caveats

- Drush (and the UI installer) will typically override `system.site:name` during the "configure site" step unless a name is provided (for Drush: `--site-name=...`).
- During install, Drush may log a notice about a missing core translation file download (for example: `drupal-11.3.2.es.po`). This does not affect the recipe itself.
- Canvas component disable ordering: the recipe works around a Canvas `active_version` hash mismatch by regenerating components before disabling them (see `UPSTREAM.md`).
- Canvas UI + default language URL prefix: Canvas is mounted at `/canvas` and does not currently account for a default-language URL prefix like `/en`. This template removes the default language prefix (`en: ''`) to keep Canvas editing working in multilingual installs.
- Some CSS overrides still target Tailwind utility classes from the Mercury starterkit; these are fragile across Mercury/Tailwind upgrades and are documented for future cleanup (see `STATUS.md`).

## What this package avoids

- No dependency on `core/profiles/demo_umami/...` assets:
  - Fonts and SVG assets used by the theme are vendored into `umami_theme/`.
  - The logo is vendored into `umami_theme/logo.svg` and referenced from recipe config.
- No hardcoded database driver module (`mysql`) in the recipe install list.
