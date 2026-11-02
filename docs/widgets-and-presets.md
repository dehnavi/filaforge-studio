---
title: Widgets and Page Presets | FilaForge Studio
description: Browse the built-in FilaForge widgets and reusable website section presets, and learn how developers can register safe custom widgets.
---

# Widgets and Presets

Widgets are individual document nodes. Presets are ready-to-edit compositions built from ordinary FilaForge widget nodes, so users can rearrange and restyle their contents after insertion.

## Built-in widget types

The current registry includes 30 widget types:

| Group | Examples |
| --- | --- |
| Layout | Container, section, grid, columns, spacer, divider |
| Content | Heading, text, rich text, button, icon, link, quote |
| Media | Image, video, slider, carousel |
| Website | Navigation, dropdown menu and menu item |
| Interaction | FAQ item, comment form, progress bar, wizard and wizard step |
| Dynamic | Query loop, shortcode component, dynamic pagination |
| Composition | Card and other section building blocks |

The editor provides controls only for the properties supported by each type. Unknown widget names and properties are rejected by the document validator; adding a PHP class alone does not automatically create a custom visual inspector.

## Built-in presets

The current package provides 18 built-in section presets, including:

- Hero and call-to-action sections
- Three-plan pricing and feature grids
- Navigation bar and dropdown menu
- Services, stats, process steps, and testimonials
- FAQ and contact sections
- Footer, slider, carousel, progress bar, and wizard

The exact catalog can change before a stable release. Presets use normal document nodes and can be customized on the canvas. The wizard is a content navigator; it is not a form submission workflow engine.

## Register a custom widget

Implement `FilaForge\Studio\Contracts\Widget`, then list the class in `config/filaforge.php`:

```php
namespace App\FilaForge\Widgets;

use FilaForge\Studio\Contracts\Widget;
use InvalidArgumentException;

final class NoticeWidget implements Widget
{
    public function name(): string { return 'notice'; }
    public function version(): int { return 1; }
    public function defaults(): array { return ['text' => 'Notice']; }
    public function acceptsChildren(): bool { return false; }
    public function view(): string { return 'widgets.notice'; }

    public function validate(array $props): void
    {
        if (array_diff(array_keys($props), ['text']) || ! is_string($props['text'] ?? null) || mb_strlen($props['text']) > 2000) {
            throw new InvalidArgumentException('Invalid notice properties.');
        }
    }
}
```

Create the trusted Blade view at `resources/views/widgets/notice.blade.php`:

```blade
<aside class="ff-notice">{{ $node['props']['text'] }}</aside>
```

Register it:

```php
'widgets' => [
    App\FilaForge\Widgets\NoticeWidget::class,
],
```

Widget names must be unique. Validate every property and render user-provided text with Blade escaping. Do not allow a document to select a PHP class, view path, Blade expression, or arbitrary HTML. Custom widgets currently extend server validation and rendering; a public custom editor-inspector API is not yet complete.

## Extend presets

The `filaforge.presets` configuration accepts preset definitions validated through the registry. Review `src/Presets/PresetRegistry.php` for the current array structure before adding a definition. Persisted presets can be managed from the editor. There is no Artisan preset generator yet.

## Versioning custom widgets

Store a stable widget name and increment `version()` when the meaning of its properties changes. The current package does not yet provide a general widget migration runner. Plan host migrations for existing documents before changing a public custom widget schema.
