# Contributing

FilaForge Studio is an active pre-1.0 development package. Public APIs may change before the first stable release. Check the root [CHANGELOG](CHANGELOG.md), [capabilities and limitations](docs/limitations.md), and relevant guide before changing behavior or documenting an API.

## Setup and validation

Requirements and host installation are documented in the [installation guide](docs/installation.md). For package development from the repository root:

    composer install
    vendor/bin/phpunit -c phpunit.xml

The package test suite needs `pdo_sqlite` enabled. Do not use a host application's production database for package checks. For documentation changes:

    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    python -m pip install -r requirements-docs.txt
    mkdocs serve
    mkdocs build --strict

The disposable `workbench/` directory is excluded from the Composer archive and ignored for new untracked files. Prefer the maintained package test suite for regression coverage; do not place credentials, local databases, generated assets, or production data in the workbench or documentation.

## Pull request expectations

- Keep core document validation separate from Filament-specific UI.
- Follow Laravel 12, Filament 5, and Livewire conventions present in the package.
- Include focused tests for new behavior, permissions, input validation, and rendered output.
- Add or update English and Persian translation keys for visible UI changes.
- Update README examples and the relevant developer guide when public APIs, installation, security requirements, or behavior changes; keep helper signatures and examples aligned with the implementation.
- Update CHANGELOG.md for user-visible capabilities and fixes. Keep unreleased work under `Unreleased`; do not imply that it has shipped in a tagged release.
- Describe exact commands, framework versions, and results that you verified.
- Do not claim production readiness, full translations, GrapesJS parity, or compatibility beyond evidence.

For rendering examples, use only validated FilaForge documents and the published revision when showing public output. Keep route lookup, tenant scoping, authorization, caching, and private data access in host application code. Never compile editor content as Blade or accept model names, SQL, or arbitrary view paths from a document.

Do not include credentials, private customer data, or production database exports in issues or pull requests. For vulnerability reports, see SECURITY.md. The docs book is built with MkDocs Material; deployment instructions are in docs/publishing-docs.md.
