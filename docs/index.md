---
title: FilaForge Studio | Laravel and Filament 5 Visual Page Builder
description: Build responsive Laravel pages visually inside Filament 5 with drag and drop, RTL authoring, reusable sections, dynamic content, a media library, and Blade rendering.
---

# FilaForge Studio

**A visual page builder for Laravel and Filament 5, with Persian and RTL support built in.**

FilaForge Studio lets an authorized editor compose website pages inside a Filament panel and publish them as versioned JSON documents. Laravel renders the published document with Blade, so the public page does not need the editor's JavaScript runtime.

## Create pages without leaving Laravel

Use an isolated canvas to arrange containers, columns, grids, headings, rich text, buttons, icons, images, video, navigation, tables, and reusable page sections. Edit text inline, adjust responsive settings, organize layers, and preview before publishing.

## Design for real websites

Start from reusable hero, pricing, feature, testimonial, FAQ, contact, call-to-action, navigation, carousel, progress, wizard, and dropdown menu presets. Customize layout, typography, color, backgrounds, media, and responsive behavior to match your site.

## Extend the builder with application code

Register trusted widgets, fonts, dynamic data providers, filters, formatters, and reusable components in Laravel. The page document stores identifiers and values; it cannot choose arbitrary model classes, tables, SQL, PHP, or Blade expressions.

## Customize the integration

Publish Blade views, public assets, and translation catalogs into your Laravel application. Use application translation keys for table headings, extend resource and page classes, or replace and disable resources for individual Filament panels. The host application owns its overrides; package upgrades do not automatically overwrite them.

See [developer customization](configuration.md#developer-customization) for publishing commands, asset selection, resource inheritance and upgrade handling, and [localization](localization.md#host-controlled-table-column-headings) for column labels. View and asset publishing, resource replacement, and resource inheritance are available starting with `v0.2.2`.

## Start here

- [Install the package](installation.md)
- [Build and publish your first page](quick-start.md)
- [Learn the visual editor](editor.md)
- [Customize views, assets, and panel resources](configuration.md#developer-customization)
- [Browse widgets and presets](widgets-and-presets.md)
- [Connect a page to your website's SEO metadata](seo.md)
- [Review security and current limitations](security.md) · [Capabilities and release status](limitations.md)

## Product status

FilaForge Studio is published on Packagist as `v0.1.0` and remains under active development. Some capabilities require explicit host application authorization and integration. Read the [current status and known limits](limitations.md) before adopting it.
