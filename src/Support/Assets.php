<?php

namespace FilaForge\Studio\Support;

final class Assets
{
    public static function files(): array
    {
        return [
            'fontawesome.json' => dirname(__DIR__, 2).'/resources/icons/fontawesome.json',
            'components.js' => dirname(__DIR__, 2).'/resources/js/components.js',
        ];
    }

    public static function url(string $name): string
    {
        $files = self::files();
        if (!isset($files[$name])) {
            throw new \InvalidArgumentException('Unknown FilaForge asset.');
        }

        $published = (bool) config('filaforge.published_assets', false);
        $path = $published ? public_path('vendor/filaforge/'.$name) : $files[$name];
        $version = substr(hash_file('sha256', is_file($path) ? $path : $files[$name]), 0, 12);

        return $published
            ? asset('vendor/filaforge/'.$name).'?v='.$version
            : route('filaforge.assets', ['asset' => $name, 'v' => $version]);
    }
}
