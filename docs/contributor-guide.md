---
title: Contributing to FilaForge Studio
description: Set up the FilaForge Studio development environment, run tests, build documentation, and contribute safe Laravel and Filament features.
---

# Contributing

FilaForge Studio is an active development package. Before starting a large change, open an issue or discussion so maintainers can confirm scope and API direction.

## Development setup

Use PHP 8.2+, Composer, and the PHP extensions required by the package. The package test suite needs pdo_sqlite enabled.

    composer install
    vendor/bin/phpunit -c phpunit.xml

For the documentation site:

    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    python -m pip install -r requirements-docs.txt
    mkdocs serve
    mkdocs build --strict

## Contribution guidelines

- Keep document validation and rendering independent from Filament-specific interfaces.
- Follow Laravel, Filament 5, and Livewire conventions used by the package.
- Add English and Persian translation keys for new user-facing behavior.
- Keep security boundaries server-side; never trust browser validation or visibility settings.
- Include focused tests for authorization, validation, RTL behavior, and rendering.
- Update the relevant guide when a public API or user workflow changes.
- State which PHP/Laravel/Filament versions and commands you actually tested.
- Avoid claiming feature parity or production readiness without evidence.

Public APIs are pre-1.0 and may change. The package currently does not provide widget or block generators. Read the root CONTRIBUTING.md for repository workflow notes.

## Authoring runtime regression

The PHP authoring tests can export the compiled editor runtime for deterministic JavaScript checks:

```sh
FILAFORGE_RUNTIME_OUTPUT=/tmp/filaforge-runtime.js vendor/bin/phpunit --filter AuthoringEditorTest
node tests/Browser/authoring-runtime.cjs /tmp/filaforge-runtime.js
```

These checks cover protected editing, token resolution, recovery, concurrent saves, offline retry, cross-page clipboard and repeater insertion. They complement the PHP/Livewire suite; they do not replace authenticated end-to-end browser coverage.

The authenticated layout regression checks all three canvas modes at desktop, tablet and phone browser widths, plus preview switching and HTML table rendering:

```sh
FILAFORGE_EDITOR_URL=http://127.0.0.1:8875/admin/pages/YOUR_PAGE_ID/edit \
FILAFORGE_ACCESS_FILE=/path/to/local-test-access.txt \
node tests/Browser/editor-layout.cjs
```

This optional check requires Playwright and Chrome. The access file contains `Email:` and `Password:` lines for a disposable local account. The current fixture expects the authoring demo's shared-section and HTML node IDs.
