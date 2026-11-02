---
title: Localization, Persian, and RTL
description: Configure FilaForge Studio translation overrides, choose page locale and direction, and author mixed Persian, Arabic, and English content.
---

# Localization and RTL

FilaForge separates the editor interface language from the language and direction of page content. A Persian page can be edited in an English Filament panel, and mixed-direction text can set its own direction.

## Available UI catalogs

The package includes 17 translation catalogs: English (en), Persian (fa), Arabic (ar), French (fr), German (de), Spanish (es), Turkish (tr), Simplified Chinese (zh_CN), Italian (it), Hebrew (he), Hindi (hi), Japanese (ja), Vietnamese (vi), Traditional Chinese for Taiwan (zh_TW), Malay (ms), Danish (da), and Dutch (nl).

Each catalog currently contains 238 matching keys. A matching key set does not mean every editor label is fully translated: some legacy strings remain hard-coded, and complete UI RTL/LTR parity is not yet claimed. Native translation review should happen before a public release.

## Set the interface locale

Laravel's current locale controls package translation resolution:

    app()->setLocale('fa');
    __('filaforge::studio.save');

Applications can override package strings in Laravel's standard path:

    lang/vendor/filaforge/fa/studio.php

Use the locale folder names exactly, including zh_CN and zh_TW.

## Set the page locale and direction

Page content locale and layout direction are stored with the page document. Choose values based on actual content and layout:

| Content | Typical locale | Typical direction |
| --- | --- | --- |
| Persian | fa | rtl |
| Arabic | ar | rtl |
| English | en | ltr |
| Hindi | hi | ltr |
| Hebrew | he | rtl |
| Japanese | ja | ltr |

These are common defaults, not forced pairings. Text widgets can set their own direction for Persian/English mixtures. Use the preview to check punctuation, numerals, wrapping, buttons, and nested layouts at mobile widths.

## Multilingual page strategy

The current persistence model stores localized page records and separate documents per locale. It does not automatically synchronize layout across translations or manage translated slugs, SEO redirects, hreflang, or sitemaps. The host application owns locale routing and fallback policy.

See [SEO and page metadata](seo.md) and [current limitations](limitations.md) before implementing a multilingual production site.
