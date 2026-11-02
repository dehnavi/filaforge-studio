---
title: Visual Editor Guide | FilaForge Studio
description: "Learn the FilaForge visual editor: canvas, drag and drop, inline editing, layers, responsive controls, history, preview, media, and RTL page authoring."
---

# Visual Editor

FilaForge Studio provides a Filament-hosted editing workspace. The admin interface follows the active panel theme; the website canvas runs in an isolated iframe so previewed content can use the website's styles independently.

## Workspace

| Area | Use |
| --- | --- |
| Widget library | Search and insert widgets or page presets |
| Canvas | Select, arrange, edit and preview page content |
| Inspector | Edit selected widget content, layout, appearance, responsive values and actions |
| Layers | Navigate and reorder nested page elements |
| Toolbar | Save, preview, publish, change viewport, undo, redo, import HTML and open page settings |

Panels can be collapsed to make more room for the canvas. Editor controls are localized in English and Persian catalogs; some older labels remain hard-coded, so the full UI is not yet translated into all 17 available locales.

## Add and arrange elements

1. Add a section or container to the page.
2. Drag a widget from the library into a valid container. You can also select a container and insert an item with the library controls.
3. Reorder or nest items using the canvas or Layers panel.
4. Select an element to open its property controls.

Use keyboard alternatives where available. Drag-and-drop coordinates are handled in the iframe canvas; continuous pointer movement stays client-side rather than issuing Livewire requests.

## Edit text inline

Select a text-bearing element and use its inline editor for common formatting such as emphasis, links, lists, alignment, and text direction. The inspector remains available for the structured properties. Saved rich text is validated against an allowlist and rendered as sanitized markup; it is not executed as Blade.

Emoji search is available in supported text controls. Text direction is independent from the page's overall layout direction, so an English label can remain LTR inside a Persian page.

## Arrange and style

The inspector exposes the settings supported by each widget. Common settings include layout, spacing, alignment, typography, colors, backgrounds, media, opacity, hover/click actions, and responsive overrides. Collapsible groups keep long settings panels manageable.

Responsive values use the configured Desktop, Tablet, and Mobile presets. Current responsive settings cover supported layout and appearance properties; they do not imply every CSS property is editable for every widget.

## Preview, save and publish

- **Preview** renders the current document with the server renderer. Page-level scripts remain disabled in preview.
- **Save** persists the draft and checks the current document version. A stale version is rejected so one editor cannot silently overwrite a newer save.
- **Publish** creates a publication revision separate from the draft. Public rendering must read that published revision.
- **Undo/redo** tracks local editing commands. Save explicitly after changes; automatic recovery and a full autosave guarantee are not implemented.

## RTL authoring

Choose page content locale and direction when creating the page. Use `fa` and `rtl` for a Persian page as appropriate, but set the direction of individual text independently when content mixes Persian, Arabic, and Latin. Prefer logical layout values and preview at narrow widths. Read [localization and RTL behavior](localization.md).

## Practical limitations

The editor is under active development and is not a GrapesJS-equivalent general-purpose CSS authoring environment. Complex custom website CSS, arbitrary framework classes, and unsupported imported HTML need manual adaptation. Check [current capabilities and limitations](limitations.md) before planning a production workflow.
