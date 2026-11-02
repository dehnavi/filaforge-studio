<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
final class LocalizationTest extends TestCase
{
    public function test_language_catalogs_have_matching_keys_and_valid_utf8(): void
    {
        $root=dirname(__DIR__,2).'/resources/lang';$english=require $root.'/en/studio.php';
        foreach(['en','fa','ar','fr','de','es','tr','zh_CN','it','he','hi','ja','vi','zh_TW','ms','da','nl'] as $locale){
            $catalog=require $root.'/'.$locale.'/studio.php';
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
}
