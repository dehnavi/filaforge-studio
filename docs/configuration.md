---
title: FilaForge Studio Configuration
description: Configure FilaForge Studio document limits, custom widgets, media storage, dynamic data providers, fonts, presets, and privileged page scripts.
---

# Configuration

Install the published package with `composer require filaforge-studio/filaforge-studio`, then publish its configuration with the command below. For local package development, follow the path or Git VCS steps in the [installation guide](installation.md).

Publish `config/filaforge.php` with `php artisan filaforge:install` or `php artisan vendor:publish --tag=filaforge-config`. Keep application-specific providers and authorization in the host application.

## Document and media limits

| Key | Default | Purpose |
| --- | ---: | --- |
| `table_prefix` | `filaforge_` | Prefix used for package tables |
| `max_bytes` | `1048576` | Maximum serialized page document size |
| `max_nodes` | `1000` | Maximum nodes in a document |
| `max_depth` | `32` | Maximum document tree depth |
| `media_disk` | `filaforge_media` | Laravel Filesystem disk for uploaded media |
| `media_max_bytes` | `52428800` | Maximum upload size in bytes |
| `published_assets` | `false` | Use published files in `public/vendor/filaforge` |
| `asset_prefix` | `filaforge-studio/assets` | Package editor asset route prefix |

The provider configures a private local media disk if the selected disk has not already been defined. Configure and test a private cloud disk explicitly if the host uses object storage; cloud access behavior is not certified by the current suite.

## Custom widgets

Add trusted widget class names to `widgets`. See [widgets and presets](widgets-and-presets.md) for the contract and a safe example. Never derive class names or view names from editor JSON.

## Dynamic data providers

Add container-resolvable trusted `Contracts\DataProvider` class names to `data_providers`, or register provider instances in the host service provider. Use explicit aliases, field allowlists, authorization, tenant constraints, safe filters, and bounded sorting. See [allowed data sources](allowed-data-sources.md).

## Fonts

Configure trusted WOFF2 font sources under `fonts` and locale defaults under `font_defaults`. The package does not bundle proprietary fonts. The host must have rights to use and serve each font. See [media and fonts](media-and-fonts.md).

## Page scripts

The config includes:

```php
'integrations' => [
    'gtm_enabled' => true,
    'custom_scripts_enabled' => false,
],
```

Custom scripts are disabled by default. Enabling scripts permits actual JavaScript execution in visitors' browsers after the separate `filaforge.manageScripts` ability grants access. See [dropdown menus and page scripts](dropdown-menu-and-page-scripts.md) and the [security guide](security.md).

## Translation overrides

Laravel applications can override package strings in the standard `lang/vendor/filaforge/<locale>/studio.php` location. Available catalogs and current translation limits are documented in [localization](localization.md).

## Developer customization

The APIs in this section are available starting with `v0.2.2`. For table headings without subclassing, see [host-controlled column labels](localization.md#host-controlled-table-column-headings).

### Publish views, assets, and translations

```bash
php artisan vendor:publish --tag=filaforge-views
php artisan vendor:publish --tag=filaforge-assets
php artisan vendor:publish --tag=filaforge-translations
```

Or publish them together with configuration during installation:

```bash
php artisan filaforge:install --views --assets --translations
```

| Tag | Host destination |
| --- | --- |
| `filaforge-config` | `config/filaforge.php` |
| `filaforge-views` | `resources/views/vendor/filaforge` |
| `filaforge-assets` | `public/vendor/filaforge` |
| `filaforge-translations` | `lang/vendor/filaforge` |

Publishing does not overwrite existing files unless you explicitly add Laravel's `--force` option. Blade overrides take effect through Laravel's standard package view lookup. You can copy just the views you need instead of publishing all views; this keeps other views up to date with the package.

The asset tag copies `components.js` and `fontawesome.json`. Editor templates and their inline scripts/styles are customized through the Blade views. After publishing assets, set `'published_assets' => true` in `config/filaforge.php`. This switches all package-generated script and icon URLs to `asset('vendor/filaforge/...')`, including Laravel's configured asset host. The web server must serve the application's public directory. URLs include a content hash so edited local files receive a new cache key. Leave this setting false to keep the default allowlisted package asset routes.

After upgrades, review published overrides against the new package files and merge changes deliberately. Published files remain host-owned and are not automatically refreshed. Clear compiled views with `php artisan view:clear` and rebuild cached configuration after relevant changes. Do not force-publish over customized files without a backup.

### Replace or disable resources

Configure application-wide defaults:

```php
// config/filaforge.php
'resources' => [
    'pages' => App\Filament\Resources\PageResource::class,
    'comments' => null,
],
```

Or configure a specific panel; these methods override the application defaults:

```php
FilaForgeStudioPlugin::make()
    ->pageResource(App\Filament\Resources\PageResource::class)
    ->commentResource(null);
```

Both methods accept a Filament Resource class, or `null` to disable registration. The plugin still registers its security middleware. Disabling a resource only removes its admin registration; it does not disable public comment endpoints or change authorization rules. Do not additionally discover/register the original package resource in the same panel.

### Extend a resource and its pages

Package resources and their page classes support inheritance. For example, create your resource with your own `getPages()` mapping:

```php
namespace App\Filament\Resources;

class PageResource extends \FilaForge\Studio\Filament\Resources\PageResource
{
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
```

Create each page class in its own file under `App\Filament\Resources\Pages`:

```php
namespace App\Filament\Resources\Pages;

class ListPages extends \FilaForge\Studio\Filament\Resources\Pages\ListPages
{
    protected static string $resource = \App\Filament\Resources\PageResource::class;
}

class CreatePage extends \FilaForge\Studio\Filament\Resources\Pages\CreatePage
{
    protected static string $resource = \App\Filament\Resources\PageResource::class;
}

class EditPage extends \FilaForge\Studio\Filament\Resources\Pages\EditPage
{
    protected static string $resource = \App\Filament\Resources\PageResource::class;
}
```

Rebinding `$resource` and overriding `getPages()` are required: inherited page mappings otherwise still reference the package resource. Comment customization follows the same pattern using `CommentResource`, `ListComments`, and `EditComment`. Override `form()`, `table()`, labels, or navigation in your resource as needed. Preserve parent authorization and document-save behavior when overriding page methods. For column headings alone, use [host-controlled labels](localization.md#host-controlled-table-column-headings) instead of subclassing.
