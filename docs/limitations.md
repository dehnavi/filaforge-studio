---
title: Current Capabilities, Verification, and Limitations
description: Review what the current FilaForge Studio development version implements, what has been tested, and what remains before production release.
---

# Current Capabilities and Limitations

FilaForge Studio is a substantial development package, but it is not yet a stable production release. This page separates implemented behavior from broader product vision and identifies the validation gaps that remain.

## Current implementation

- Opt-in Filament 5 plugin with page and comment resources and a Livewire editor host.
- Versioned JSON documents, validation limits, draft and published revisions, optimistic save conflict checks, and atomic publication.
- 30 registered built-in widget types and 18 composition presets.
- Iframe canvas, drag and drop, layers, inline rich text, undo/redo, responsive properties, visual preview, and media controls.
- Laravel Filesystem media uploads, stable IDs, image editing, folders and categories, and remote image/video references.
- Configurable font registry, 17 translation catalogs, Persian/RTL document rendering, data providers, query loops, and strict shortcode-to-widget conversion.
- Published Blade rendering and helpers for fragments, complete documents, and HTTP responses.
- Comment controls with separate posting, viewing, and moderation authorization gates.
- Optional page integrations; custom JavaScript is disabled by default and requires explicit configuration and a separate permission.

Counts and UI details can change before the first stable release. Review the corresponding guide for exact behavior.

## Verification evidence

On 2026-11-03, the package suite passed 77 tests and 39,400 assertions independently on Laravel 12.70.2 / Testbench 10.12.0 and Laravel 13.39.0 / Testbench 11.3.0. Both runs used PHP 8.3.36, PHPUnit 11.5.57, Livewire 4.4.8, and a temporary SQLite extension. The Laravel 13 environment resolved Filament 5.11.1. Dependency audits reported no known security advisories.

CI covers Laravel 12 on PHP 8.2–8.4 and Laravel 13 on PHP 8.3–8.5. The local runs above establish PHP 8.3 coverage; other matrix combinations depend on CI results. Earlier browser checks are not a fresh browser run against either host environment.

## Not yet verified or complete

- Stable 1.0 API, compatibility matrix, and a clean public archive installation.
- Complete GrapesJS feature parity or unrestricted CSS editing.
- Full translation coverage and UI direction across every locale, dark mode, and customized Filament themes.
- Host-specific tenant authorization, private routes, and tenant-aware comment/media lists.
- Cloud filesystem behavior, database matrix, physical touch devices, broad accessibility audit, and performance budgets.
- Complete page/template/global-component/menu/font management resources.
- General document and widget migration runner, unknown-widget recovery, and revision-restore UI.
- Autosave/recovery guarantees, scheduled publishing, complete multilingual slug/redirect/hreflang/sitemap workflow, and render cache invalidation.
- Custom visual inspector API and Artisan widget/block generators.
- External independent security audit or production penetration test.

## Adoption guidance

Use a development or staging host first. Restrict the editor to trusted users, implement tenant scoping and authorization in host code, review the security guide, and test backup/restore and publication behavior before handling public production content. Do not treat passing package tests as proof that a host application's deployment is secure.

See the root CHANGELOG.md for implementation milestones and SECURITY.md for the current reporting status.
