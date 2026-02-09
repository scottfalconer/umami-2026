# AI Process Notes (Umami 2026)

This file documents how an AI coding agent was used during the Umami 2026 Drupal CMS 2.0 site template work.

## Scope

The baseline to share/commit is:

- `umami_2026/` (recipe: config + default content)
- `umami_2026_support/` (small helper module)
- `umami_theme/` (theme forked from the Mercury starterkit)

The sandboxes under `sandboxes/` are local working environments used to iterate and validate and are not part of the baseline package.

## What The Agent Did

- Planned and implemented Canvas Content Templates for:
  - `node.article.full`
  - `node.recipe.full`
- Migrated the front-end theme away from a Mercury subtheme to a Mercury starterkit fork theme (`umami_theme/`), to align with Mercury’s recommended approach.
- Added small Umami-specific SDC components in the Umami theme when the Mercury starterkit did not provide an exact match:
  - `umami_theme/components/recipe-meta/`
  - `umami_theme/components/ingredients-list/`
- Reduced Layout Builder reliance by moving full node rendering to Canvas Content Templates and removing exported `layout_builder__layout` field config from node bundles/displays.
- Investigated clean-install warnings, identified root cause (recipes import config before content), and implemented an install-safe fix:
  - Added `umami_2026_support` with a stable block plugin (`umami_2026_block_content_uuid`) that renders a custom block by UUID.
- Updated placed block config entities (`block.block.mercury_*`) to use that plugin.
- Updated docs (`README.md`, `STATUS.md`, `MIGRATION.md`) to reflect the current state and workflow.

## What Needed Human Review

- Visual parity / responsive checks at key breakpoints.
- Decisions that affect project direction and community alignment (naming, drupal.org project strategy, release versioning).
- Validation of Canvas prop-expression correctness when iterating (invalid expressions can fail with HTTP 400 / `{"detail":"Bad Request"}` when using JSON:API).

## Known Failure Mode: Canvas Prop Expressions

Canvas structured-data prop expressions are strict. Invalid expression strings can cause HTTP 400 responses and/or watchdog errors.

Mitigation:

- Prefer copying known-good expression formats from Canvas test coverage:
  - `canvas/tests/src/Kernel/NodeTemplatesTest.php`
- Treat the expression strings as copy/paste artifacts (avoid re-typing separators).

## Verification Approach

Primary verification was:

- Clean install on the tester sandbox using DDEV:
  - `cd sandboxes/umami-2026-tester && ddev exec drush site:install ../recipes/umami_2026 -y`
- Smoke checks via HTTP requests for key pages and expected markup.

## Disclosure Guidance

If posting to a Drupal.org issue, disclose that an AI coding agent was used for parts of the implementation, and include:

- The exact verification commands run.
- The scope of AI assistance (planning, code edits) and the scope of human review (visual QA, final decisions).
