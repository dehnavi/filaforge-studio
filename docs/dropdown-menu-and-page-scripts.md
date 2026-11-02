---
title: Responsive Dropdown Menus and Page Scripts
description: Build responsive, nested FilaForge dropdown menus and safely govern optional Google Tag Manager or custom JavaScript integrations.
---

# Dropdown Menus and Page Scripts

## Responsive dropdown menu

The dropdown-menu widget supports a brand label, toggle label, nested menu-item children, and a responsive collapse breakpoint. Legacy documents without collapseAt use the tablet breakpoint.

Available collapse behavior:

- tablet: collapses at tablet and mobile widths.
- mobile: collapses at mobile width.
- never: remains expanded.

Menu items accept labels and validated HTTP(S), root-relative, or page-anchor URLs. The tree is bounded to four levels and 30 children per parent. Invalid schemes and malformed trees are rejected.

Menu responsive settings support bounded menuGap, submenuWidth, and submenuIndent values. Values are compiled into scoped CSS variables for the corresponding menu node. Collapsed menus use full-width nested panels and touch-friendly targets. Desktop flyouts adapt near viewport edges; tall panels become scrollable. Native details/summary disclosure keeps basic navigation available without JavaScript.

## Page integrations

The editor's Page scripts panel can configure Google Tag Manager and two trusted JavaScript slots: head and bodyEnd. Enter JavaScript without script tags. Only the GTM container ID is required; the package generates the fixed loader and noscript markup.

The scripts are stored with the versioned document, saved with optimistic concurrency, and included only by an authorized published response. They do not execute in the editor canvas, preview, or fragment output.

## Security and permissions

The package config defaults to:

    'integrations' => [
        'gtm_enabled' => true,
        'custom_scripts_enabled' => false,
    ],

Custom JavaScript is executable browser code, not a sandbox. To use it, the host must opt in and grant the separate filaforge.manageScripts ability for the page. Ordinary edit/publish permission does not grant script management. Restrict this ability to trusted administrators, audit changes, and apply the host's CSP and privacy/consent policies.

GTM is also a third-party tracking integration. The package does not configure tags, obtain consent, or validate your Google Tag Manager account. Do not add the same container in both the host layout and the page integration.

## Rendering behavior

- Editor canvas, preview, and fragment helpers do not execute page-level scripts.
- HtmlRenderer::document() and response() default page scripts off.
- publishedResponse($page) includes configured published integrations by default.
- If a caller passes HtmlOptions, it must explicitly set includePageScripts: true to include them.

A trusted host can pass a CSP nonce through HtmlOptions::scriptNonce. The host remains responsible for CSP generation and policy. Disabling a config flag suppresses scripts even for an older published revision.

## Verification boundary

Package tests cover validation, access checks, versioning, preview isolation, nonce handling, and script config. Browser testing used a harmless local script; a live GTM container and Tag Assistant were not tested. See [security](security.md) and [limitations](limitations.md).
