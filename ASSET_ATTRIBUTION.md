# Assets, Licensing, and Attribution (Umami 2026)

This install package vendors theme assets (fonts, SVGs, logo) and ships default content (including media) as part of the Umami 2026 site template.

## Source Of Truth

Unless otherwise noted, the assets and demo content originate from Drupal core's Umami demo profile/theme (`core/profiles/demo_umami/...`) and were exported/vendored here to make the template self-contained.

## Vendored Theme Assets

### Fonts

Vendored into: `umami_theme/fonts/`

- `Source Sans Pro` (WOFF2 files prefixed `source-sans-pro-*`)
- `Scope One` (WOFF2 file prefixed `scope-one-*`)

These font files match the versions shipped with Drupal core's Umami demo theme and were originally sourced from Google Fonts. Both fonts are published under the SIL Open Font License (OFL) 1.1.

### Logo and SVG Icons

Vendored into:
- `umami_theme/logo.svg`
- `umami_theme/icons/`

These assets are carried forward from Drupal core's Umami demo theme to preserve the Umami visual identity in a Drupal CMS template context.

## Default Content (Including Media)

Exported into: `umami_2026/content/`

The recipe includes default content entities (nodes, media, files, taxonomy terms, blocks, etc.) exported from the Umami demo content set.

## Notes / Caveats

- If this template is published publicly (Drupal.org or otherwise), it should be accompanied by a final review confirming that all shipped media files have appropriate redistribution rights, and that any third-party licenses are compatible with the distribution channel.
