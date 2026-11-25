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

Emoji search is available in supported text controls. Text direction is independent from the page's overall layout direction, so an English label can remain LTR inside a Persian page. Press a direction button with a collapsed caret to set the direction of a whole block. To keep punctuation next to an English phrase inside Persian text, select the phrase and press the direction button; the selection becomes an isolated inline run.

## Embed a video or another iframe

1. Add **Embed code** to the page and paste the provider's direct iframe URL, complete iframe snippet, or supported script snippet into **Iframe URL or embed code**. For a URL, use the address the provider puts in the iframe's `src`.
2. Optionally enter a title for screen readers. If an iframe snippet already has a title, that title takes precedence.
3. Choose **Apply embed**. The inspector reports whether it accepted the input and lists any parts it removed. Applying updates the draft.
4. Choose **Preview page** to see the applied embed. The editing canvas shows a placeholder with the provider's host. A provider may block embedding or behave differently in the sandboxed preview. The preview uses an opaque-origin data URL, which allows nested players to retain their provider origins. If the admin panel sets a Content Security Policy, its frame policy must allow `data:` for this preview.

Script embeds are off by default. See [embed settings](configuration.md#third-party-embeds) and [third-party embed security](security.md#third-party-embeds). For semantic page markup, use the [HTML block](reusable-sections.md#html-block).

## Arrange and style

The inspector exposes the settings supported by each widget. Common settings include layout, spacing, alignment, typography, colors, backgrounds, media, opacity, hover/click actions, and responsive overrides. Collapsible groups keep long settings panels manageable.

Responsive values use the configured Desktop, Tablet, and Mobile presets. The active canvas stays centered inside the workspace; the inactive preview iframe does not occupy layout space. On small browser windows, the element library and inspector open as separate workspace panels instead of covering the canvas. Current responsive settings cover supported layout and appearance properties; they do not imply every CSS property is editable for every widget.

## Preview, save and publish

- **Preview** renders the current document with the server renderer. Page-level scripts remain disabled in preview.
- **Save** persists the draft and checks the current document version. A stale version is rejected so one editor cannot silently overwrite a newer save.
- **Publish** creates a publication revision separate from the draft. Public rendering must read that published revision.
- **Undo/redo** tracks local editing commands. Autosave is enabled by default; failed or conflicting saves pause automatic retries. Tab-local recovery can restore changes only when the server version still matches. Use **Save** to retry a failed save.

## RTL authoring

Choose page content locale and direction when creating the page. Use `fa` and `rtl` for a Persian page as appropriate, but set the direction of individual text independently when content mixes Persian, Arabic, and Latin. Prefer logical layout values and preview at narrow widths. Read [localization and RTL behavior](localization.md).

## Practical limitations

The editor is under active development and is not a GrapesJS-equivalent general-purpose CSS authoring environment. Complex custom website CSS, arbitrary framework classes, and unsupported imported HTML need manual adaptation. Check [current capabilities and limitations](limitations.md) before planning a production workflow.

Read [reusable sections and database repeaters](reusable-sections.md) for the repeater builder, HTML blocks, shared content, editing restrictions, version comparison and restore, and custom Filament inspectors.
