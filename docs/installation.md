---
title: Install FilaForge Studio in Laravel and Filament 5
description: Install the FilaForge Studio Composer package, run setup and migrations, register the Filament 5 plugin, and configure host authorization.
---

# Installation

FilaForge Studio is published on Packagist as `v0.1.0`. For local package development, use a Composer path repository or Git VCS repository as described below.

## Requirements

| Requirement | Supported or needed |
| --- | --- |
| PHP | 8.2+ |
| Laravel | 12.x or 13.x (PHP 8.3+ for 13.x) |
| Filament | 5.x |
| PHP extensions | dom, fileinfo, gd, mbstring |
| Database | A Laravel-supported driver; package suite currently runs on SQLite |
| Test extension | pdo_sqlite to run the package suite |
| Optional | intl for Persian calendar date formatting |

Filament brings its compatible Livewire version.

## Install from Packagist

Require the package in your Laravel application:

    composer require filaforge-studio/filaforge-studio

Then run setup and migrations:

    php artisan filaforge:install
    php artisan migrate

To stay on the `0.1` release line, use `composer require filaforge-studio/filaforge-studio:^0.1`.

Package discovery loads the service provider. Its migrations are applied by Laravel migrate. The package does not alter your panel registration automatically.

## Install from a local Composer path

In your host application's `composer.json`, add a path repository and package requirement. Adjust the path to your checkout:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../filaforge-studio",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "filaforge-studio/filaforge-studio": "@dev"
  }
}
```

Then install and run setup:

```sh
composer update filaforge-studio/filaforge-studio
php artisan filaforge:install
php artisan migrate
```

The install command publishes package configuration and reports the remaining steps. Package discovery loads the service provider; its migration files are applied by Laravel migrate. The package does not alter your panel registration automatically.

## Install from a Git VCS repository

Replace the placeholders with the actual repository location and branch:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/dehnavi/filaforge-studio"
    }
  ],
  "require": {
    "filaforge-studio/filaforge-studio": "dev-main"
  }
}
```

If the repository default branch has another name, use the matching Composer dev-branch constraint. Prefer a tagged stable release once available.

## Register the Filament plugin

Add the plugin to each panel that should manage FilaForge pages:

```php
use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilaForgeStudioPlugin::make());
}
```

The plugin registers page and comment resources in that panel. It respects the panel path and theme. It does not add public website routes; the host application owns public page lookup and routing.

## Configure authorization

Page access is denied unless the host application grants it. Define abilities for the roles that should access the builder. For example:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('filaforge.viewAny', fn ($user) => $user->can('manage-pages'));
    Gate::define('filaforge.create', fn ($user) => $user->can('manage-pages'));
    Gate::define('filaforge.update', fn ($user, $page) => $user->can('manage-pages'));
    Gate::define('filaforge.publish', fn ($user, $page) => $user->can('publish-pages'));
}
```

Bind these examples to real roles, ownership, and tenant access. The package does not implement host-specific tenancy. Comment creation, comment viewing, moderation, and privileged scripts use separate abilities; read the [security guide](security.md).

## Verify setup

```sh
php artisan about
php artisan migrate:status
php artisan filaforge:security-check
```

The security command reports deployment settings and makes no changes. A local HTTP environment may fail HTTPS and secure-cookie checks by design. Create a test page, confirm unauthorized users cannot access it, publish a revision, and render it through a host-owned route as shown in the [quick start](quick-start.md).

## Publish configuration

```sh
php artisan vendor:publish --tag=filaforge-config
```

See [Configuration](configuration.md) for available settings.
