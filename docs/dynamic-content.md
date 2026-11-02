---
title: Dynamic Content and Safe Components
description: Bind FilaForge widgets to allowlisted Laravel data, render bounded query loops, and build controlled components without executing stored PHP or Blade.
---

# Dynamic Content

The editor's **Data & tools** tab lets authors search registered fields and insert them into supported widgets. To bind an existing element, select it and choose a data field for the supported property. Query Loop repeats its child layout over a bounded provider result. The editor stores references and validated parameters; provider callbacks run only during server preview or rendering.

## Provider contract

Developers register data providers with explicit aliases, public field definitions, filters, sorts, result limits, and authorization. See [Connect Trusted Data Sources](allowed-data-sources.md) for Eloquent examples and tenant scoping.

Custom `DataProvider` implementations return a `DataResult` for a validated `DataQuery`:

```php
public function fetch(DataQuery $query, DataContext $context): DataResult
{
    // Query only the authorized, bounded source defined by this provider.
}
```

The provider contract exposes aliases; it does not expose table metadata to page JSON. Keep remote calls timeout-bounded and authorize before fetching. The renderer limits provider fetches and expanded nodes per render. A deadline check cannot interrupt a blocking host callback; configure database, HTTP, and provider timeouts in host code.

## Context and privacy

`DataContext` carries locale, page, tenant key, record key, user, audience, pagination, and current loop row. The host creates tenant, record, and identity values after authorization. Never copy those values from the page document or an untrusted request field.

Public render uses public audience and no user by default—even if an administrator is logged into the panel. Private data requires a private provider and trusted context with an explicit access decision. Host applications must also apply private/no-store caching where content is not public.

## Bindings and query loops

Bindings connect an allowlisted widget property to a provider field, with an optional formatter and fallback. Conditions support a constrained set of typed comparisons. Missing data or denied access uses a safe fallback/empty state. Hiding content is not access control.

Query Loop controls a bounded page size, known sort/filter identifiers, and child layout. `@item` references are accepted only inside the owning loop. Nested loops are bounded; arbitrary recursion and arbitrary query expressions are rejected.

## Safe shortcode conversion

For developer-authored import text, the strict parser recognizes forms such as:

```text
[[field source="company" name="phone"]]
[[component name="contact-card"]]
```

The parser converts these to ordinary FilaForge nodes. It never evaluates Blade, PHP, SQL, or JavaScript. The editor uses structured selection controls instead of requiring authors to type shortcode syntax.

`DynamicElementFactory::make(...)` can construct a validated field or component node in host code. Registered component builders should return a native node tree and have an explicit authorization callback. A component cannot return raw HTML or select a Blade view. Reference depth and expanded node count are bounded.

## Formatters and built-ins

Built-in text, number, money, date, Persian number, and Persian date formatters return text and are escaped. Persian date support depends on optional `ext-intl`. Custom formatters must return a string; `Htmlable` or raw HTML results are rejected.

The built-in `company` provider exposes only `name`, `phone`, `email`, and `url` configured under `filaforge.company`; these values are public. Do not put secrets there. The built-in `contact-card` component is a native heading/text composition.

## Comments and security

The comment widget does not make a page public. Comment posting and viewing require separate host gates; moderation uses dedicated per-page or global permissions. The built-in web action checks publication, native comment-widget presence, parent validity, CSRF, same-origin, rate limits, input size, and a honeypot. New comments are pending; approved names and bodies are escaped plain text.

For session security, SQL injection boundaries, page scripts, media, and deployment requirements, see [Security](security.md). Do not expose private data with a public provider, trust a visibility condition, or use custom scripts as a sandbox.
