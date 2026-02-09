# Upstream Tracking

## Canvas: Recipe Config Actions Disabling `canvas.component.*` Can Fail Validation

Symptom during recipe apply (Drupal CMS 2.0.0 / Drupal core 11.3.2, `drupal/canvas` 1.0.4):

- Recipe config action:
  - `canvas.component.block.announce_block: disable: []`
- Install fails with:
  - `There were validation errors in canvas.component.block.announce_block:`
  - `active_version: The version d2ee7918676f072c does not match the hash of the settings for this version, expected 370a9ae608f6c7e1.`

Notes:
- This appears to be an install-time ordering issue: some Component config
  entities exist but have stale `active_version` hashes at the moment config
  actions validate them.

Workaround implemented in this template:
- Provide a config action plugin `umamiGenerateCanvasComponents` in
  `umami_2026_support` that calls `ComponentSourceManager::generateComponents()`.
- Invoke it first in `umami_2026/recipe.yml` before any `canvas.component.*`
  disables.

Related Canvas issues (context, not confirmed duplicates):
- https://www.drupal.org/project/canvas/issues/3562354 (recipes + `is_syncing`)
- https://www.drupal.org/project/canvas/issues/3570699 (block component version hashes change across core versions)

Action:
- If we want to remove the workaround, file a Canvas issue with the failure
  output and a minimal reproducer recipe.

### Issue Draft (Suggested Text)

Proposed title:
- Recipe apply fails when disabling `canvas.component.*` due to `active_version` hash mismatch

Environment:
- Drupal CMS `2.0.0` (core `11.3.2`)
- `drupal/canvas` `1.0.4`

Steps to reproduce (minimal):
1. Create a recipe that installs Canvas and disables one or more component config entities, e.g.:

   - `canvas.component.block.announce_block: disable: []`

2. Apply the recipe during install, for example:
   - `drush site:install <path-to-your-site-template-recipe> -y`

Actual:
- Install/recipe apply fails with validation errors similar to:
  - `There were validation errors in canvas.component.block.announce_block:`
  - `active_version: The version <hash> does not match the hash of the settings for this version, expected <hash>.`

Expected:
- Disabling (hiding) components should not fail validation during install/recipe apply.

Workaround:
- Trigger Canvas component generation before running any `canvas.component.*` config actions (for example, call `ComponentSourceManager::generateComponents()` early).
