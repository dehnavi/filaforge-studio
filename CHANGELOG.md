# Changelog

All notable changes are documented here. The project is pre-1.0; breaking changes may occur before the first stable release.

## [0.2.3] - 2026-11-05

### Fixed

- Reconnect the editor canvas after repeated iframe connections and avoid rebuilding the editor during draft autosaves.

### Documentation

- Use absolute README documentation and image URLs for external plugin listings.

## [0.2.2] - 2026-11-04

### Added

- Publishable Blade views and public assets with optional installer flags and versioned URLs for published files.
- Configurable, nullable Page and Comment resource classes with per-panel plugin overrides.
- Extensible resource and resource-page classes for host-owned Filament customization.

### Documentation

- Added README examples and linked installation, configuration, localization and quick-start guidance for host customization and upgrade handling.

### Fixed

- Resolved the editor's return link through its active resource when using a replacement resource.

## [0.2.1] - 2026-11-04

### Added

- Host-configurable Page and Comment table headings using literal text or application translation keys.
- Publishable translation catalogs through the `filaforge-translations` tag.

### Fixed

- Completed Page resource form, table, plural name and editor title translations across all 17 catalogs.
- Used the comment body label consistently and preserved translated model label casing.

## [0.2.0] - 2026-11-04

### Added

- Independent published section embeds with isolated IDs, local anchor links and an optional response-local asset collector.
- Registered shared sections with published-source resolution and allowlisted per-instance content overrides.
- Sanitized HTML blocks and a visual database repeater builder with editable row-bound cards, filtering, sorting, responsive columns and pagination.
- Typed design tokens, designer-controlled element locks, exposed client fields and custom Filament inspector schemas.
- Debounced autosave, tab-local recovery, cross-page element copying and style copying.
- Publication history comparison and version-checked restoration to draft without automatic republication.
- Native authoring translations in all 17 locale catalogs and feature-specific regression coverage.

### Fixed

- Centered the active iframe across desktop, tablet and mobile modes without allowing the inactive frame to occupy space.
- Separated compact editor panels from the canvas and aligned authoring controls with existing icons and layout conventions.
- Preserved safe HTML table structure in the editor canvas and shared its typography with published output.

### Documentation

- Documented reusable sections, database providers, override permissions, shared tokens, custom inspectors and recovery behavior.
- Updated rendering examples, current verification evidence, localization coverage and known limitations.
- Added the requested Iran solidarity message and a locally stored flag image with source attribution.

## [0.1.4] - 2026-11-03

- Updated pinned GitHub Actions references to actions/checkout 7.0.1 and actions/setup-python 7.1.0.

## [0.1.3] - 2026-11-03

- Added Laravel 13 compatibility alongside Laravel 12 and a CI matrix for both framework lines.
- Verified 77 package tests with 39,400 assertions on each framework line using PHP 8.3.
- Updated CSRF and nested localization test fixtures for both framework versions.
- Added explicit three-day Dependabot cooldowns and excluded development files from Git distribution archives.

## [0.1.2] - 2026-11-03

- **CI security:** pinned external GitHub Actions to full commit SHAs, restricted workflow permissions to read-only contents, and disabled checkout credential persistence.
- **Dependency maintenance:** configured weekly Dependabot updates for Composer and GitHub Actions.
- **Distribution:** excluded composer.lock from Composer and Git export archives while retaining it for package development.

## [0.1.1] - 2026-11-03

- **Documentation:** refreshed the README with current capabilities and Laravel/Filament rendering helper examples; clarified contributor workflow and workbench handling.

## [0.1.0] - 2026-11-02

- **Editor and publishing:** Filament 5 page and comment resources, an iframe-isolated visual editor, validated versioned documents, optimistic save conflict checks, and separate draft and published revisions.
- **Content authoring:** 30 built-in widget types, 18 presets, drag-and-drop layouts, inline rich text, responsive properties, reusable media, and configurable fonts.
- **Rendering API:** `filaforge_html()`, `filaforge_document()`, and `filaforge_response()` helpers; the `HtmlRenderer` service and `StudioHtml` facade; published-revision rendering with metadata options.
- **Dynamic content and integrations:** explicitly registered data providers, field allowlists, bounded query loops, controlled shortcode conversion, comment moderation, and opt-in page integrations.
- **Localization:** 17 interface locale catalogs with Persian and RTL page authoring and rendering.
- **Developer resources:** installation, quick start, rendering, widget, media, dynamic data, security, SEO, and limitations guides; MkDocs Material publishing workflow.
- **Project metadata:** MIT license and Composer package metadata. See LICENSE and composer.json.

This release entry summarizes the `0.1.0` tag. It is not a compatibility promise; for verified behavior and known gaps, see [current capabilities and limitations](docs/limitations.md).
