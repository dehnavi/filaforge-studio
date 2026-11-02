---
title: Security Guide | FilaForge Studio
description: Secure FilaForge Studio with host authorization, tenant-scoped queries, safe page rendering, private media, CSRF protection, and controlled script execution.
---

# Security Guide

Treat every editor document, upload, dynamic value, URL, and comment as untrusted input. FilaForge Studio validates documents and escapes or sanitizes rendered content, but the host application remains responsible for access control, tenancy, session configuration, CSP, and public routes.

## Required host responsibilities

- Define least-privilege policies for page listing, creation, editing, and publishing.
- Scope page, media, comment, and data-provider queries to the correct tenant and owner.
- Use HTTPS, a strong `APP_KEY`, secure and HttpOnly session cookies, and SameSite cookies.
- Keep `APP_DEBUG=false` in production; use Laravel's normal session ID rotation and invalidation lifecycle.
- Configure a CSP compatible with the assets your site actually uses.
- Protect previews and private pages with host authentication and non-cacheable responses.
- Keep public CDN caches away from authenticated or tenant-private HTML.

The package `filaforge:security-check` command reports several deployment configuration problems without modifying them. It is not a penetration test and cannot inspect your reverse proxy, CDN, identity provider, or database permissions.

## Rendering and XSS

Ordinary text is escaped. Rich text passes through a strict sanitizer. Stored documents cannot execute Blade/PHP expressions, choose arbitrary widget classes/views, or supply SQL. Imported HTML drops scriptable and unsupported content. Custom widgets are trusted server code: validate their properties and escape output in their Blade views.

Dynamic content is resolved through registered providers and field allowlists. A hidden element or display condition is not authorization. Never expose private records through a public provider; enforce scope and access in host code.

## SQL injection and data access

Do not accept table names, model class names, column names, SQL fragments, or sort expressions from page JSON. Define trusted providers in application code. Use query bindings for values and fixed allowlists for fields, operators, filters, sorts, and tenant constraints. See [allowed data sources](allowed-data-sources.md).

## Comments

Comment posting, public comment viewing, record moderation, and global moderation use separate abilities. They are denied unless the host grants them. The built-in submission path checks published comment-enabled content, uses Laravel's web CSRF middleware, same-origin checks, rate limits, a honeypot, bounded input, and pending moderation. Approved comment names and bodies render as escaped plain text.

For multi-tenant moderation, scope the comment resource and policy to the tenant; the generic global list ability is not a tenant boundary. Review the comment implementation before exposing it publicly.

## Page scripts and tracking

Custom page JavaScript is disabled by default. When explicitly enabled, scripts are executable code in visitors' browsers. Restrict configuration and the `filaforge.manageScripts` ability to trusted administrators, audit changes, and apply consent, privacy, and CSP rules. Do not use this feature as a sandbox for untrusted page authors. Preview does not execute page integrations.

## Media

Uploads are validated and stored through Laravel Filesystem. Use private disks for protected content, authorize media lookup, and test signed URL lifetime and caching for the chosen driver. A media URL entered by an editor is referenced directly by visitors' browsers; review third-party privacy and availability before publishing it.

## Session hijacking and CSRF

HttpOnly reduces script access to session cookies, but does not make session theft impossible. Use HTTPS everywhere, secure cookies, correct trusted-proxy configuration, session rotation after authentication, and invalidation on logout or security changes. Retain CSRF protection for state-changing web actions. Do not disable middleware to make an integration test or external form submit.

## Report a vulnerability

Before public release, the maintainer must publish a private security contact and response policy. Until then, do not include credentials, customer data, or live exploit details in public issues. The repository's SECURITY.md records the current reporting status.
