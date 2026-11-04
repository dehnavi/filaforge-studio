<?php
return [
    'table_prefix' => 'filaforge_',
    'max_bytes' => 1048576,
    'max_nodes' => 1000,
    'max_depth' => 32,
    'widgets' => [],
    'media_disk' => 'filaforge_media',
    'media_max_bytes' => 52428800,
    'asset_prefix' => 'filaforge-studio/assets',
    'presets' => [],
    'data_providers' => [],
    // Optional invokable host class: (authorized PageLocale, current user) => DataContext.
    'editor_context' => null,
    'dynamic_slots' => [],
    'design_tokens' => [],
    'company' => [],
    // Locales whose editor chrome should use right-to-left layout.
    'rtl_locales' => ['ar', 'fa', 'he', 'ur'],
    // Trusted font definitions: family, translated label, fallback, locales, WOFF2 sources.
    'fonts' => [
        // 'noto-hi' => [
        //     'label' => ['en'=>'Hindi font', 'fa'=>'فونت هندی'],
        //     'family' => 'Noto Sans Devanagari',
        //     'fallback' => ['sans-serif'],
        //     'locales' => ['hi'],
        //     'display' => 'swap',
        //     'sources' => [['url'=>'/fonts/noto-hi.woff2', 'weight'=>'100 900']],
        // ],
    ],
    // Optional document font defaults keyed by content locale (for example 'ja' => 'noto-jp').
    'font_defaults' => [],
    'integrations' => [
        'gtm_enabled' => true,
        'custom_scripts_enabled' => false,
    ],
];
