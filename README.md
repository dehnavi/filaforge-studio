# FilaForge Studio

**A visual page builder for Laravel and Filament 5, with Persian and RTL authoring built in.** Create responsive pages with drag and drop, reusable sections, media, dynamic data, and server-rendered Blade output.

FilaForge Studio stores pages as validated, versioned JSON documents. Editors work inside a Filament panel, while Laravel renders published revisions on the public site without loading the editor runtime.

[Composer package](https://packagist.org/packages/filaforge-studio/filaforge-studio) · [GitHub repository](https://github.com/dehnavi/filaforge-studio) · [Installation guide](docs/installation.md) · [Security guide](docs/security.md)

## Features

- **Visual editing:** iframe-isolated canvas, drag and drop, nested layouts, layers, inline rich text, undo and redo, responsive settings, preview, and context actions.
- **Widgets and presets:** containers, grids, columns, typography, buttons, icons, images, video, tables, navigation, carousels, pricing, testimonials, FAQ, progress, wizard, and dropdown menus.
- **Persian and RTL:** independent editor language and page direction, mixed RTL/LTR content, locale catalogs, and configurable fonts.
- **Media library:** Laravel Filesystem uploads, stable media references, folders, categories, metadata, image editing, and remote image/video URLs.
- **Dynamic content:** explicitly registered providers, allowlisted fields and filters, bounded query loops, and conditional content.
- **Blade output:** render an HTML fragment, a complete HTML document, an HTTP response, or a published page revision.
- **Extensibility:** register trusted widgets, presets, fonts, and Laravel data providers from the host application.

See [widgets and presets](docs/widgets-and-presets.md), [media and fonts](docs/media-and-fonts.md), and [current capabilities and limitations](docs/limitations.md) for the implemented behavior and coverage.

## Requirements

- PHP 8.2 or newer
- Laravel 12
- Filament 5 and its compatible Livewire version
- PHP extensions: DOM, Fileinfo, GD, and mbstring
- A database driver supported by the host Laravel application
- `pdo_sqlite` to run the package test suite
- Optional `intl` for Persian calendar date formatting

## Install

Install the package from Packagist and run its setup and migrations:

```sh
composer require filaforge-studio/filaforge-studio
php artisan filaforge:install
php artisan migrate
```

Register the plugin on each Filament panel where the page builder should be available:

```php
use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilaForgeStudioPlugin::make());
}
```

Package discovery registers the service provider. The plugin is opt-in per panel. Configure host authorization before allowing editors to create, update, publish, or moderate pages and comments. See the [complete installation guide](docs/installation.md) and [security guide](docs/security.md).

## Create your first page

1. Define page-management abilities in the host application.
2. Open **Pages** in the selected Filament panel and create a page with its language and direction.
3. Add a section or container, then drag widgets or presets onto the canvas.
4. Select an element to edit its content, typography, colors, background, and responsive settings.
5. Preview the page, save a draft, and publish when it is ready.
6. Render the published revision from a host-owned Laravel route.

Follow the [quick start](docs/quick-start.md) for authorization, publication, and rendering examples.

## Documentation

The complete developer book is configured for Mintlify. Install Node.js and the Mintlify CLI, then preview the documentation from the repository root:

```sh
npm install -g mint
mint dev
```

Open `http://localhost:3000`. The Mintlify configuration is in [`docs.json`](docs.json), the landing page is [`index.mdx`](index.mdx), and the guides are in [`docs/`](docs/). Run `mint validate` and `mint broken-links` before publishing. See [preview and publish](docs/publishing-docs.md).

The same Markdown guides can also be built as an optional static book with MkDocs:

```sh
python3 -m pip install -r requirements-docs.txt
mkdocs build --strict
```

## Development workbench

`workbench/sandbox` is a disposable Laravel host application for exercising the Composer package in a realistic Filament panel. Follow its [workbench guide](workbench/sandbox/README.md); do not expose it publicly or reuse its demo accounts and authorization rules in production.

## Tests

```sh
composer install
vendor/bin/phpunit -c phpunit.xml
```

The backend suite covers document validation, rendering, Filament integration, permissions, media, dynamic data, scripts, and localization. See [verification and limitations](docs/limitations.md) for the tested environment and remaining coverage gaps.

## Contributing and security

Read [CONTRIBUTING.md](CONTRIBUTING.md) before making changes. Report vulnerabilities privately using the instructions in [SECURITY.md](SECURITY.md).

## License

FilaForge Studio is distributed under the [MIT License](LICENSE). Third-party assets may have separate terms; see their bundled notices.
