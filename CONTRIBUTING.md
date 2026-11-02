# Contributing

FilaForge Studio is an active pre-1.0 development package. Public APIs may change before the first stable release.

## Setup and validation

Requirements and host installation are documented in the [developer book](docs/installation.md). For package development:

    composer install
    vendor/bin/phpunit -c phpunit.xml

The test suite needs pdo_sqlite enabled. For documentation changes:

    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    python -m pip install -r requirements-docs.txt
    mkdocs serve
    mkdocs build --strict

## Pull request expectations

- Keep core document validation separate from Filament-specific UI.
- Follow Laravel 12, Filament 5, and Livewire conventions present in the package.
- Include focused tests for new behavior, permissions, input validation, and output.
- Add or update English and Persian translation keys for visible UI changes.
- Update the developer book when public APIs, installation, or behavior changes.
- Describe exact commands, framework versions, and results that you verified.
- Do not claim production readiness, full translations, GrapesJS parity, or compatibility beyond evidence.

Do not include credentials, private customer data, or production database exports in issues or pull requests. For vulnerability reports, see SECURITY.md. The docs book is built with MkDocs Material; deployment instructions are in docs/publishing-docs.md.
