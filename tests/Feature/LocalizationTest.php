<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
final class LocalizationTest extends TestCase
{
    public function test_language_catalogs_have_matching_keys_and_valid_utf8(): void
    {
        $root=dirname(__DIR__,2).'/resources/lang';$english=\Illuminate\Support\Arr::dot((static fn ($path) => require $path)($root.'/en/studio.php'));
        foreach(['en','fa','ar','fr','de','es','tr','zh_CN','it','he','hi','ja','vi','zh_TW','ms','da','nl'] as $locale){
            $catalog=\Illuminate\Support\Arr::dot((static fn ($path) => require $path)($root.'/'.$locale.'/studio.php'));
            $this->assertEqualsCanonicalizing(array_keys($english),array_keys($catalog),$locale);
            foreach($catalog as $key=>$value){
                $this->assertIsString($value,$locale.'.'.$key);
                $this->assertNotSame('',trim($value),$locale.'.'.$key);
                $this->assertTrue(mb_check_encoding($value,'UTF-8'),$locale.'.'.$key);
                preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/',$english[$key],$expected);
                preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/',$value,$actual);
                $this->assertEqualsCanonicalizing($expected[0],$actual[0],$locale.'.'.$key);
            }
        }
    }
    public function test_laravel_resolves_each_new_language_without_english_fallback(): void
    {
        foreach(['ar'=>'حفظ','fr'=>'Enregistrer','de'=>'Speichern','es'=>'Guardar','tr'=>'Kaydet','zh_CN'=>'保存','it'=>'Salva','he'=>'שמירה','hi'=>'सहेजें','ja'=>'保存','vi'=>'Lưu','zh_TW'=>'儲存','ms'=>'Simpan','da'=>'Gem','nl'=>'Opslaan'] as $locale=>$label){
            app()->setLocale($locale);
            $this->assertSame($label,__('filaforge::studio.save'));
            $this->assertNotSame('filaforge::studio.data_preview_failed',__('filaforge::studio.data_preview_failed'));
        }
    }
    public function test_builder_has_native_translations_in_every_catalog(): void
    {
        $root=dirname(__DIR__,2).'/resources/lang';
        $english=(static fn($path)=>require $path)($root.'/en/studio.php');
        foreach (['fa','ar','fr','de','es','tr','zh_CN','it','he','hi','ja','vi','zh_TW','ms','da','nl'] as $locale) {
            $path=$root.'/'.$locale.'/studio.php';$source=file_get_contents($path);
            $this->assertStringContainsString("'builder' => [",$source,$locale);
            $this->assertStringNotContainsString("'canvas_ui', 'builder'",$source,$locale);
            $catalog=(static fn($path)=>require $path)($path);
            $this->assertEqualsCanonicalizing(array_keys($english['builder']),array_keys($catalog['builder']),$locale);
            foreach (['repeater','shared_section','repeater_help','html_help','recovery_found'] as $key) $this->assertNotSame($english['builder'][$key],$catalog['builder'][$key],$locale.'.'.$key);
        }
    }

}
