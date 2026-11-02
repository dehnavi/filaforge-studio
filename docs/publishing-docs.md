---
title: Preview and Publish the FilaForge Studio Documentation
description: Preview FilaForge Studio documentation with Mintlify and publish updates from the connected Git repository.
---

# Preview and Publish the Documentation

The repository is configured for Mintlify with a root `docs.json`, a Mintlify landing page at `index.mdx`, and the guides in `docs/`. The guides remain standard Markdown so they can also be built as a static book with MkDocs.

## Preview locally

Install Node.js, then install the Mintlify CLI and start the preview from the repository root:

```sh
npm install -g mint
mint dev
```

The preview runs at `http://localhost:3000`. Review the sidebar, page rendering, code examples, and internal links. Run `mint validate` to check the configuration and content before publishing.

## Connect the GitHub repository

In the Mintlify dashboard, connect `dehnavi/filaforge-studio`, choose `main` as the production branch, and set the documentation configuration path to the repository root because `docs.json` is at the root. Install the Mintlify GitHub App with access to this repository. Once connected, pushes to the production branch trigger a deployment.

## Publish with the CLI

For a new site that has not been claimed or connected to a repository, Mintlify's CLI can create and deploy a hosted site:

```sh
mint deploy --subdomain filaforge-studio
```

Use the dashboard GitHub integration for this existing project so that the canonical docs source remains in this repository. Follow Mintlify's site-claim flow if the CLI creates a new hosted site.

## Configure a custom domain

Set a documentation domain in the Mintlify dashboard and follow the DNS records it provides. Do not add a guessed canonical URL to the site configuration; use the confirmed live domain when setting SEO or social metadata.

## Optional MkDocs build

The same Markdown reference pages can still be built as a static book:

```sh
python3 -m pip install -r requirements-docs.txt
mkdocs build --strict
```

The generated `site/` directory is ignored and should not be committed.
