<?php
namespace FilaForge\Studio\Widgets;
final class Icons {
 public static function all(): array { static $icons; return $icons ??= json_decode(file_get_contents(__DIR__.'/../../resources/icons/fontawesome.json'),true,flags:JSON_THROW_ON_ERROR); }
 public static function get(string $name): ?array { $name=(['home'=>'house','mail'=>'envelope','arrow'=>'arrow-right'])[$name]??$name; return self::all()[$name]??null; }
}
