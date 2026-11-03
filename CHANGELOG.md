# Changelog

All notable changes are documented here. The project is pre-1.0; breaking changes may occur before the first stable release.

## Unreleased

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
