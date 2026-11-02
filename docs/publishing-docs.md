---
title: Publish the FilaForge Studio Documentation
description: Preview and publish the FilaForge Studio developer book with MkDocs Material and GitHub Pages.
---

# Publish the Documentation

The repository includes an MkDocs Material site definition, curated navigation, per-page titles and descriptions, and a docs build check. The documentation source is the Markdown in docs/.

## Preview and verify

Install the documentation dependencies and build locally:

    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    python -m pip install -r requirements-docs.txt
    mkdocs serve
    mkdocs build --strict

Review the site at http://127.0.0.1:8000 before publishing. The build output is the ignored site/ directory; do not commit generated HTML.

## GitHub Pages

First configure the GitHub repository's Pages source and permissions according to your organization policy. Set site_url in mkdocs.yml to the final public documentation URL; use the project's actual owner and repository path. Then, from the primary branch, run:

    mkdocs gh-deploy

This builds the docs and publishes output to the configured Pages branch (gh-pages by default). Deployment pushes to the remote repository, so verify the site locally and obtain your normal repository review before running it. MkDocs warns that uncommitted and untracked content may be included in a deployment.

For a custom domain, configure it in GitHub Pages and preserve a CNAME file under docs/ so future builds include it. Do not invent a site_url or domain before the hosting URL is known.

MkDocs also supports serving generated static files from other hosts. Build the site and deploy the contents of site/ according to that host's process. See the official [MkDocs deployment guide](https://www.mkdocs.org/user-guide/deploying-your-docs/).

## Discoverability and search

The Material theme provides navigation and client-side documentation search. Page titles, descriptions, and the site description are authored in mkdocs.yml and Markdown metadata. Add the canonical site_url once the URL is final. Search indexing is controlled by the chosen host; publishing docs does not guarantee ranking or search-engine indexing.

## Theme maintenance notice

The pinned Material for MkDocs 9.8.0 release is in maintenance mode; its maintainers state that critical fixes and security updates end on May 5, 2027. The maintainers provide [Zensical as a successor](https://squidfunk.github.io/mkdocs-material/changelog/) and an [incremental migration guide](https://zensical.org/docs/compatibility/mkdocs/migration/). Keep the current MkDocs deployment until a migration is evaluated. Because this project uses the standard search plugin and MkDocs configuration, build and compare the same docs with Zensical, verify search/navigation and publishing behavior, then update pinned dependencies and CI when ready. Zensical does not provide the MkDocs gh-deploy command; publishing steps will need to change.
