# FilaForge Studio

**Build responsive Laravel pages visually inside Filament 5.** FilaForge Studio is an RTL-aware page builder package for teams that want editable pages, reusable layouts, media, and server-rendered output in the Laravel application they already maintain.

Compose a page from containers, headings, grids, buttons, media, navigation, tables, carousels, pricing sections, FAQs, and other reusable presets. Edit content on the canvas, work in Persian or English, and render the published document with Blade. The public renderer does not load the editor runtime.

> **Development status:** The package is under active development and is not yet a stable production release or published on Packagist. See [current capabilities and limitations](docs/limitations.md) before adopting it.

## Why FilaForge Studio

- **Laravel and Filament native:** Composer package discovery, Filament 5 panel plugin, Livewire editor, Eloquent persistence, Laravel Filesystem media, and Blade rendering.
- **Visual editing:** iframe-isolated canvas, drag and drop, layers, inline rich text, undo and redo, responsive settings, and reusable page sections.
- **RTL-aware authoring:** Persian and English content can use independent language and direction settings; the editor includes Persian translations and configurable fonts.
- **Designed to extend:** register widgets, data providers, presets, fonts, and shortcode-backed components from host application code.
- **Controlled publishing:** versioned JSON documents, draft and published revisions, permission checks, and server-side validation.

## Requirements

- PHP 8.2 or newer
- Laravel 12
- Filament 5 (and its compatible Livewire version)
- PHP extensions: DOM, Fileinfo, GD, and mbstring
- A database driver supported by the host Laravel application
- `pdo_sqlite` to run the package test suite
- Optional `intl` for Persian calendar date formatting

## Install

FilaForge Studio is not published on Packagist yet. For the current development checkout, install it through a Composer path repository. See the [complete installation guide](docs/installation.md) for both a local checkout and a Git VCS source.

```json
{
  "repositories": [
    { "type": "path", "url": "../filaforge-studio", "options": { "symlink": true } }
  ],
  "require": {
    "filaforge/studio": "@dev"
  }
}
```

Then run:

```sh
composer update filaforge/studio
php artisan filaforge:install
php artisan migrate
```

Register the plugin on each Filament panel where the builder should appear:

```php
use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilaForgeStudioPlugin::make());
}
```

Package discovery registers the service provider. The plugin is opt-in per panel; it does not claim your public website routes or replace the panel theme.

## Build your first page

1. Define page-management permissions in the host application.
2. Open **Pages** in the selected Filament panel and create a page with its language and direction.
3. Open the editor, add a section or container, then drag a heading, image, button, or preset into the canvas.
4. Select an element to edit its properties. Use preview to inspect the page before publishing.
5. Save the draft and publish when ready.
6. Render only the page's published revision in your website route.

The [quick start](docs/quick-start.md) includes the required authorization and rendering examples.

## Feature highlights

| Area | Included |
| --- | --- |
| Editor | Drag and drop, nested layout, inline rich text, layers, context actions, undo and redo, responsive preview |
| Widgets | 30 built-in widget types, including layout, content, media, navigation, interaction, dynamic data, progress and wizard |
| Presets | 18 built-in page sections, including hero, pricing, navbar, features, testimonials, FAQ, CTA, carousel and dropdown menu |
| Media | Laravel Filesystem uploads, stable media references, image editing, folders, categories, remote image/video URLs |
| Dynamic content | Explicit data providers, allowlisted fields and filters, query loops, conditional content, safe shortcode-to-widget conversion |
| Localization | 17 translation catalogs; page language and direction can be chosen separately |
| Output | Blade fragment, complete HTML document, response helper, published-revision rendering |

Counts describe the current implementation and may change before the first stable release. See the [full capability and release checklist](docs/limitations.md).

## Documentation

The complete developer book is built with MkDocs. Read it online after deployment, or preview it locally:

```sh
python3 -m venv .venv-docs
. .venv-docs/bin/activate
python -m pip install -r requirements-docs.txt
mkdocs serve
```

Then open `http://127.0.0.1:8000`. Browse the [installation](docs/installation.md), [editor guide](docs/editor.md), [widgets and presets](docs/widgets-and-presets.md), [security guide](docs/security.md), and [API guides](docs/).

## Tests

```sh
composer install
vendor/bin/phpunit -c phpunit.xml
```

The current checkout has a backend suite covering documents, rendering, Filament integration, permissions, media, dynamic data, scripts, and localization. Test results and their boundaries are listed in [project status](docs/limitations.md). The package is not yet verified against a complete Laravel/Filament version matrix or production host setup.

## License

FilaForge Studio is distributed under the [MIT License](LICENSE), using the copyright holder specified for this project. Third-party assets may have separate terms; see their bundled notices.
