---
title: FilaForge Studio Configuration
description: Configure FilaForge Studio document limits, custom widgets, media storage, dynamic data providers, fonts, presets, and privileged page scripts.
---

# Configuration

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
