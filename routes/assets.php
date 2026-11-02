<?php

use Illuminate\Support\Facades\Route;

// Public assets are a fixed allowlist: route input never becomes a filesystem path.
Route::get(trim(config('filaforge.asset_prefix', 'filaforge-studio/assets'), '/').'/{asset}', function (string $asset) {
    $assets = [
        'fontawesome.json' => [__DIR__.'/../resources/icons/fontawesome.json', 'application/json'],
        'components.js' => [__DIR__.'/../resources/js/components.js', 'application/javascript'],
    ];
    abort_unless(isset($assets[$asset]), 404);
    [$path, $mime] = $assets[$asset];
    return response()->file($path, ['Content-Type'=>$mime, 'X-Content-Type-Options'=>'nosniff', 'Cache-Control'=>'public, max-age=3600']);
})->whereIn('asset', ['fontawesome.json','components.js'])->name('filaforge.assets');
