# FilaForge Studio Host Workbench

This directory contains a Laravel host application used to exercise the Composer package in a realistic Filament panel. It is a development fixture, not a deployable example site. Its Composer path repository points to the package root with symlink disabled so package and host source remain independently reviewable.

## Run locally

Use compatible PHP extensions, then install the workbench dependencies:

    composer install
    cp .env.example .env
    php artisan key:generate

Configure a disposable local environment in .env. Set APP_URL=http://127.0.0.1:8875 and FILAFORGE_DEMO_ADMIN_PASSWORD to a locally generated random value of at least 24 characters; keep .env private. The sample app uses SQLite. Publish Filament assets, create the database, migrate, seed, and serve:

    php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
    php artisan filament:assets
    php artisan migrate
    php seed-sandbox.php
    php artisan serve --host=127.0.0.1 --port=8875

The seeder creates demo content and a test administrator using the local password from .env. Inspect the seeder before use and run it only against a disposable local database. Never reuse test accounts or demo authorization rules in a production or tenant application. Do not expose this host publicly.

Additional demo scripts create separate sample pages and data. Read each script before running; some create or replace demo-only records. The maintained package tests run from the package root, not this workbench.

For consumer installation and integration instructions, see the [package developer book](../../docs/index.md).
