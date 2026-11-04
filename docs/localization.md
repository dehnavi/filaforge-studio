---
title: Localization, Persian, and RTL
description: Configure FilaForge Studio translation overrides, choose page locale and direction, and author mixed Persian, Arabic, and English content.
---

# Localization and RTL

FilaForge separates the editor interface language from the language and direction of page content. A Persian page can be edited in an English Filament panel, and mixed-direction text can set its own direction.

## Available UI catalogs

The package includes 17 translation catalogs: English (en), Persian (fa), Arabic (ar), French (fr), German (de), Spanish (es), Turkish (tr), Simplified Chinese (zh_CN), Italian (it), Hebrew (he), Hindi (hi), Japanese (ja), Vietnamese (vi), Traditional Chinese for Taiwan (zh_TW), Malay (ms), Danish (da), and Dutch (nl).

The editor, media library, and canvas controls use dedicated `editor_ui`, `media_ui`, and `canvas_ui` groups. These groups are present for every catalog so a locale never displays raw translation keys. English and Persian have native translations for these groups; the other catalogs currently fall back to English for these newer controls until their translations are reviewed. The `builder` group has its own native entries in all 17 catalogs and is not copied from English. Older package strings continue to use each catalog's translations.

## Set the interface locale

Laravel's current locale controls package translation resolution:

    app()->setLocale('fa');
    __('filaforge::studio.save');

Applications can override package strings in Laravel's standard path:

    lang/vendor/filaforge/fa/studio.php

Use the locale folder names exactly, including zh_CN and zh_TW.

## Configure editor direction

The editor detects Arabic, Persian, Hebrew, and Urdu locale codes as right-to-left. Add or remove language codes with `rtl_locales` in `config/filaforge.php`:

    'rtl_locales' => ['ar', 'fa', 'he', 'ur'],

Regional locale variants such as `ar-SA` and `he-IL` are matched by their language code.

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
