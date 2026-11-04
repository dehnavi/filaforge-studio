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

Publish the catalogs for customization:

```bash
php artisan vendor:publish --tag=filaforge-translations
```

The Page and Comment resources use these strings for form fields, table columns, model names, navigation, and breadcrumbs. Filament supplies its standard create/edit/save actions and validation messages in the panel locale; customize those through Filament and Laravel's own translation catalogs.

In `lang/vendor/filaforge/fa/studio.php`, override `page`, `title`, the `comment_*` keys, and the `resources` group. For example:

```php
return [
    'page' => 'صفحه',
    'resources' => [
        'pages' => 'صفحه‌ها',
        'slug' => 'نشانی صفحه',
        'locale' => 'زبان محتوا',
        'locales' => ['en' => 'انگلیسی', 'fa' => 'فارسی', 'ar' => 'عربی'],
        'direction' => 'جهت نمایش',
        'directions' => ['ltr' => 'چپ به راست', 'rtl' => 'راست به چپ'],
        'version' => 'نسخه',
        'edit_page' => 'ویرایش صفحه',
    ],
];
```

`resources.locales` controls the creation form's available content languages. Locale codes and direction values are stored unchanged; translated labels only affect presentation. Existing records with other locale codes display their stored code in the table.

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

## Host-controlled table column headings

Developers can override individual columns in the host application's `config/filaforge.php`, independently of the package language files. Values accept literal text or keys from the host application's own Laravel catalogs:

```php
'resource_labels' => [
    'pages' => ['columns' => [
        'title' => 'admin.pages.title',
        'locale' => 'admin.pages.language',
        'lock_version' => 'admin.pages.version',
    ]],
    'comments' => ['columns' => [
        'name' => 'admin.comments.author',
        'body' => 'admin.comments.message',
        'status' => 'admin.comments.status',
        'created_at' => 'admin.comments.date',
    ]],
],
```

For example, define `pages.title` in `lang/fa/admin.php` and `lang/en/admin.php`. The current interface locale resolves the value on each request. Omitted or empty overrides use the package translation. Rebuild the application's configuration cache after changing cached configuration.
