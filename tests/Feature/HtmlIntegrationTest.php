<?php
namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Importing\HtmlImporter;
use FilaForge\Studio\Rendering\HtmlOptions;
use FilaForge\Studio\Rendering\HtmlRenderer;
use FilaForge\Studio\Tests\TestCase;

final class HtmlIntegrationTest extends TestCase
{
    public function test_fragments_documents_responses_and_safe_metadata(): void
    {
        $document = DocumentValidator::empty('fa', 'rtl');
        $options = new HtmlOptions(title: '<script>bad</script>', description: '" onload="bad', canonical: 'https://example.test/fa', stylesheets: ['/css/site.css']);
        $this->assertStringNotContainsString('<html', filaforge_html($document)->toHtml());
        $html = filaforge_document($document, $options)->toHtml();
        $this->assertStringStartsWith('<!doctype html>', $html);
        $this->assertStringContainsString('lang="fa" dir="rtl"', $html);
        $this->assertStringContainsString('&lt;script&gt;bad&lt;/script&gt;', $html);
        $this->assertStringContainsString('&quot; onload=&quot;bad', $html);
        $this->assertStringContainsString('/css/site.css', $html);
        $this->assertSame('text/html; charset=UTF-8', filaforge_response($document)->headers->get('Content-Type'));
        $this->assertSame(filaforge_html($document)->toHtml(), \FilaForge\Studio\Facades\StudioHtml::fragment($document)->toHtml());
    }

    public function test_options_reject_executable_and_protocol_relative_urls(): void
    {
        foreach (['javascript:alert(1)', '//evil.test/a.css', '/\\evil.test', "https://example.test/\nx"] as $url) {
            try {
                new HtmlOptions(stylesheets: [$url]);
                $this->fail('An unsafe asset URL was accepted.');
            } catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_import_converts_layout_media_and_persian_without_executing_html(): void
    {
        $importer = app(HtmlImporter::class);
        $result = $importer->import('<html><head><title>Ignore</title></head><body><section class="grid grid-cols-2" style="gap:20px"><div><h2>سلام</h2><p><strong>متن</strong></p></div><div><img src="https://example.test/a.jpg" alt="عکس"><video><source src="/movie.mp4"></video></div></section></body></html>');
        $this->assertSame('grid', $result['document']['root']['children'][0]['type']);
        $this->assertSame(2, $result['document']['root']['children'][0]['props']['columns']);
        $html = app(HtmlRenderer::class)->fragment($result['document'])->toHtml();
        foreach (['سلام','<strong>','/movie.mp4','https://example.test/a.jpg'] as $expected) $this->assertStringContainsString($expected, $html);
        $unsafe = $importer->import('<div onclick="alert(1)" style="background-image:url(javascript:bad)"><script>alert(1)</script><iframe src="http://localhost"></iframe><img src="javascript:alert(1)"><h1>Safe</h1></div>');
        $html = app(HtmlRenderer::class)->fragment($unsafe['document'])->toHtml();
        foreach (['onclick','javascript:','iframe','<script>'] as $forbidden) $this->assertStringNotContainsString($forbidden, $html);
        $this->assertContains('import_removed', $unsafe['warnings']);
        $this->assertContains('import_bad_url', $unsafe['warnings']);
        $this->assertContains('import_css', $unsafe['warnings']);
    }

    public function test_import_rejects_oversized_and_deep_input(): void
    {
        foreach (['',str_repeat('x',262145),str_repeat('<div>',26).'text'.str_repeat('</div>',26)] as $html) {
            try { app(HtmlImporter::class)->import($html); $this->fail('Invalid HTML accepted.'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_packaged_assets_do_not_depend_on_the_sandbox(): void
    {
        $this->get(route('filaforge.assets', ['asset'=>'components.js']))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $response = $this->get(route('filaforge.assets', ['asset'=>'fontawesome.json']))->assertOk();
        $icons = json_decode(file_get_contents($response->baseResponse->getFile()->getPathname()), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('star', $icons);
        $this->assertArrayHasKey('path', $icons['star']);
        $this->get('/filaforge-studio/assets/composer.json')->assertNotFound();
        $this->assertSame('local', config('filesystems.disks.filaforge_media.driver'));
    }

    public function test_font_styles_are_encoded_for_html_attributes(): void
    {
        $document = DocumentValidator::empty();
        $document['root']['styles'] = ['fontFamily'=>'vazirmatn'];
        $html = app(HtmlRenderer::class)->fragment($document)->toHtml();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $root = $xpath->query('//*[@data-node="root"]')->item(0);
        $this->assertStringContainsString('font-family:"Vazirmatn",Tahoma,sans-serif', $root->getAttribute('style'));
        $this->assertFalse($root->hasAttribute('vazirmatn'));
    }

    public function test_unpublished_pages_never_fall_back_to_draft(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(HtmlRenderer::class)->publishedDocument(new \FilaForge\Studio\Models\PageLocale);
    }
}
