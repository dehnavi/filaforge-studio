---
title: Media Library and Fonts | FilaForge Studio
description: Upload, organize, edit, and select images and videos in the Laravel Filesystem media library, then configure multilingual WOFF2 fonts for page rendering.
---

# Media Library and Fonts

## Media library

The editor media library uses Laravel Filesystem and stores stable media identifiers in page documents. It supports image and video uploads, browsing, search, selection, metadata, folders, categories, bulk organization, image editing, and direct image/video URLs.

### Upload and organize

Open the media picker from an image or video setting. Upload a supported file or select an existing library item. Set alt text and captions for images, then organize assets into folders or categories. Page documents retain media references instead of hard-coding a generated storage URL.

The package configures a private local disk by default if the selected disk has not already been defined. The host is responsible for configuring and testing alternate disks, public/private visibility, temporary URL behavior, authorization, backups, retention, and CDN policy. Cloud storage drivers are not certified by the current test matrix.

### Use a remote media URL

You can set an HTTP(S) or root-relative URL for image/video content. The browser loads that URL directly. FilaForge does not download the asset, check its rights, or add it to the media library. For video, use a playable media file URL such as MP4/WebM; a YouTube page URL is not a video file URL.

Remote assets can reveal visitor IP addresses to their host and may be blocked by the website's CSP. Review availability, privacy, licensing, and CORS requirements before publication.

## Fonts

The Font Registry supports configured local/system font stacks and trusted WOFF2 sources. Font definitions can include a label, family, fallback stack, locale list, display behavior, and static or variable weights. Per-content-locale defaults are configurable. Published output emits only font faces used by the document.

Example config:

```php
'fonts' => [
    'brand-sans' => [
        'label' => ['en' => 'Brand Sans', 'fa' => 'فونت برند'],
        'family' => 'Brand Sans',
        'fallback' => ['Arial', 'sans-serif'],
        'locales' => ['en', 'fa'],
        'display' => 'swap',
        'sources' => [
            ['url' => '/fonts/brand-sans.woff2', 'weight' => '100 900'],
        ],
    ],
],
'font_defaults' => [
    'fa' => 'brand-sans',
],
```

Use font files only when you have redistribution and serving rights. Proprietary Persian fonts are not bundled. System font names rely on fonts installed on the visitor's device.

### Cross-origin font sources

The editor canvas uses an isolated iframe. A configured font served from another origin must permit the canvas origin through its CORS headers. A same-origin font URL is usually simpler. Add the source origin to the host CSP `font-src` directive where required.

## Progress and wizard presets

The progress widget accepts a label, value, color, height, and optional value display. The wizard preset presents a bounded sequence of content steps with previous/next controls. It is a content navigator, not a form workflow or submission engine. See [widgets and presets](widgets-and-presets.md).
