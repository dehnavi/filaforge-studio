# Changelog

All notable changes are documented here. The project is pre-1.0; breaking changes may occur before the first stable release.

## [Unreleased]

### Added

- Accept direct iframe URLs alongside iframe and script snippets in the **Embed code** widget. The inspector shows validation feedback and opens **Preview page** after the embed is applied.
- Add an **Insert HTML tag** menu to the HTML block. Insert semantic tags such as `article`, `section`, `p`, and `span` at the cursor or wrap a selection.

### Fixed

- Allow Embed code widgets to be inserted before the widget catalog finishes loading.
- Apply Embed code and HTML block changes without rebuilding the editor during server validation.

## [0.2.6] - 2026-11-25

### Added

- Added host-deny authorization configuration, opt-in authenticated fallback, per-panel authorization mode, resource enable/disable helpers, and richer install/security diagnostics.
- Documented the difference between resource registration, Filament navigation visibility, and authorization.
- Added column add/remove (1–6 columns) for column groups, an inline layout toolbar for sections, cards, and column groups in the canvas, and matching quick controls in the inspector.
- Added an **Embed code** widget for third-party iframes and external scripts (Aparat, YouTube, Vimeo, and others). The kept parts of a pasted code are rebuilt from an allowlist on every render; script embeds stay off until `embeds.scripts` is enabled in the host configuration.

### Fixed

- Quick tone and spacing changes are no longer reverted by the inspector's **Apply changes** button. Removing a column only deletes an empty column container, so legacy column groups keep their content widgets, and a removal rejected by editing rules no longer reports success.
- Text direction chosen in the inline toolbar is now saved for text, heading, and quote widgets, and selecting part of a block isolates the selected words as an inline run so punctuation stays next to an English phrase inside Persian text. Inline formatting marks (direction, tone, size, alignment) now render in published output for every text widget, not only rich text.

### Security

- Preserved deny-by-default behavior and guest denial; authenticated mode is opt-in and unsuitable for role-based or multi-tenant production applications.

## [0.2.5] - 2026-11-18

### Changed

- Allow Composer to resolve Filament 4 or newer and Illuminate Support 12 or newer instead of restricting installation to specific major releases.

## [0.2.4] - 2026-11-05

### Fixed

- Return preview HTML without rebuilding the surrounding editor during save-to-preview transitions.
- Isolate the canvas iframe from Livewire DOM updates and recover automatically when the canvas handshake or render acknowledgement is lost, preserving the current draft and undo history.

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
