---
title: FilaForge Studio Architecture
description: Understand how FilaForge Studio separates Laravel persistence, the Filament and Livewire editor, the versioned document core, dynamic providers, and Blade rendering.
---

# Architecture

FilaForge Studio stores a validated page document and renders it through trusted server-side widget views. It separates page authoring from public output so the frontend does not need the visual editor runtime.

```text
Filament panel
    └── PageResource + Livewire editor
        ├── iframe canvas and local editing commands
        ├── widget/preset registry
        └── validated save/publish actions
              ↓
        PageLocale draft JSON + revision snapshots
              ↓
        host route resolves and scopes a page
              ↓
        BladeRenderer → validated widget views → HTML response
```

## Package layers

| Layer | Responsibilities |
| --- | --- |
| Core | Versioned document validation, widget registry, limits, tree structure |
| Laravel | Models, migrations, Filesystem, policies, routes, translations, commands |
| Filament | Opt-in panel plugin, page and comment resources, editor host |
| Livewire | Authorized server actions, save/publish, preview and inspector data |
| Browser editor | Iframe canvas, local drag/drop and history, responsive controls |
| Rendering | Blade views, safe text output, dynamic values and public interactions |

## Persistence model

The package uses prefixed tables for pages, localized page records, revisions, media, folders, comments, and presets. A page locale stores the current draft JSON and lock version; publication points to a revision belonging to that locale. Media references use stable IDs. Comments have moderation status and a relation to their page locale.

The current implementation does not provide tenant-aware global scoping. Host applications must scope page queries, media access, providers, and policies to their tenant model.

## Document lifecycle

1. The editor edits a local document.
2. Save sends a bounded document snapshot and expected lock version.
3. The server validates schema, registered widgets, properties, identifiers, limits, and dynamic bindings before persistence.
4. Publication writes a revision and updates the locale's published revision pointer atomically.
5. The host route resolves the page and asks the renderer for that published revision.

The JSON document is the source of truth; generated HTML is output only. The schema has version metadata, but a general document/widget migration runner is not complete.

## Rendering boundary

`BladeRenderer` resolves widget views from the trusted registry. User text is escaped or passed through the rich-text sanitizer. The document cannot select arbitrary PHP, Blade, model classes, SQL, or view paths. Page scripts are a separate, explicitly privileged integration and are not executed in editor preview.

The host application owns public routes, SEO policy, tenant resolution, authentication, caching, CSP, and final page shell. See [HTML output](html-output.md) and [security](security.md).

## Extension points

- `Contracts\Widget` for trusted widget validation and rendering.
- `Contracts\DataProvider` for explicitly scoped application data.
- `ShortcodeRegistry` for controlled text-to-widget conversion.
- `FontRegistry` configuration for licensed WOFF2 sources.
- `filaforge.presets` for reusable page compositions.

These APIs evolve during the pre-1.0 development period. Review [current limitations](limitations.md) before treating them as stable.
