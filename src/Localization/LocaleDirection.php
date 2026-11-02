<?php

namespace FilaForge\Studio\Localization;

final class LocaleDirection
{
    public static function forLocale(?string $locale = null): string
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale ?? app()->getLocale()))[0]);
        $rtlLocales = array_map('strtolower', (array) config('filaforge.rtl_locales', ['ar', 'fa', 'he', 'ur']));

        return in_array($language, $rtlLocales, true) ? 'rtl' : 'ltr';
    }
}
