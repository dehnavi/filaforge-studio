<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use FilaForge\Studio\Presets\PresetRegistry;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Models\Page;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Documents\PageDocuments;
final class StudioPresetsTest extends TestCase {
 use RefreshDatabase;
 public function test_all_presets_validate_render_and_have_fresh_ids():void {
  $registry=app(PresetRegistry::class);$this->assertEqualsCanonicalizing(['hero','pricing','navbar','comments','slider','carousel','features','faq','testimonials','process','stats','services','contact-cta','cta','footer'],array_column($registry->all(),'key'));
  foreach($registry->all() as $preset){$a=$registry->instantiate($preset['key']);$b=$registry->instantiate($preset['key']);$this->assertNotSame($a['id'],$b['id']);$doc=DocumentValidator::empty('fa','rtl');$doc['root']['children']=[$a,$b];app(DocumentValidator::class)->validate($doc);$html=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc);$this->assertNotEmpty($html);}
 }
 public function test_custom_preset_is_persistent_and_access_controlled():void {
  $registry=app(PresetRegistry::class);$before=count($registry->all());$node=$registry->instantiate('hero');
  $this->postJson('/filaforge-media/presets',['label'=>'هیروی من','category'=>'شخصی','root'=>$node])->assertUnauthorized();
  $this->actingAs(\App\Models\User::factory()->create(['email'=>'admin@filaforge.test']));
  $result=$this->postJson('/filaforge-media/presets',['label'=>'هیروی من','category'=>'شخصی','root'=>$node])->assertCreated()->json();
  $this->app->forgetInstance(PresetRegistry::class);$registry=app(PresetRegistry::class);$this->assertCount($before+1,$registry->all());$this->assertNotSame($node['id'],$registry->instantiate($result['key'])['id']);
  $this->assertDatabaseHas(config('filaforge.table_prefix').'presets',['label'=>'هیروی من']);
 }
 public function test_all_nodes_support_safe_background_and_breakpoint_styles():void {
  $doc=DocumentValidator::empty('fa','rtl');$node=app(PresetRegistry::class)->instantiate('hero');
  $node['styles']=['backgroundColor'=>'#c7d2fe','backgroundOpacity'=>0.55,'color'=>'#18212f','borderRadius'=>18];
  $node['responsive']=['desktop'=>['fontSize'=>44,'textAlign'=>'center'],'tablet'=>['width'=>720,'padding'=>24,'columns'=>2],'mobile'=>['width'=>360,'padding'=>12,'fontSize'=>26,'display'=>'block']];
  $doc['root']['children']=[$node];app(DocumentValidator::class)->validate($doc);
  $html=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc);
  $this->assertStringContainsString('data-ff-background', $html);
  $this->assertStringContainsString('--ff-background-color:rgba(199,210,254,0.55)', $html);
  $this->assertStringContainsString('@media(max-width:1024px)', $html);
  $this->assertStringContainsString('@media(max-width:640px)', $html);
  $this->assertStringContainsString('width:360px!important', $html);
  $this->assertStringContainsString('font-size:26px!important', $html);
  $doc['root']['children'][0]['responsive']['mobile']['display']='expression(alert(1))';
  $this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($doc);
 }
 public function test_custom_background_renders_with_readable_text_and_rejects_css():void {
  $doc=DocumentValidator::empty('fa','rtl');$section=app(PresetRegistry::class)->instantiate('hero');$section['props']['tone']='#142538';$doc['root']['children']=[$section];
  $html=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc);$this->assertStringContainsString('background:#142538;color:#ffffff',$html);
  $doc['root']['children'][0]['props']['tone']='red;background:url(x)';$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($doc);
 }
 public function test_registry_rejects_duplicates_and_bad_trees():void {$r=app(PresetRegistry::class);$this->expectException(\InvalidArgumentException::class);$r->register('hero','x','x',$r->instantiate('hero'));}
 public function test_comment_requires_published_widget_and_moderation():void {
  $this->actingAs(\App\Models\User::factory()->create(['email'=>'admin@filaforge.test']));$doc=DocumentValidator::empty('fa','rtl');$comments=app(PresetRegistry::class)->instantiate('comments');$doc['root']['children']=[$comments];$node=$comments['children'][0]['id'];
  $page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'تست','slug'=>'comments','direction'=>'rtl','draft_document'=>$doc]);
  $body=['page'=>$page->id,'node'=>$node,'name'=>'کاربر تست','body'=>'دیدگاه نمونه'];$this->postJson('/filaforge-comments',$body)->assertNotFound();app(PageDocuments::class)->publish($page,0);$this->postJson('/filaforge-comments',$body)->assertCreated();$this->assertDatabaseHas(config('filaforge.table_prefix').'comments',['page_locale_id'=>$page->id,'status'=>'pending']);
  $html=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc,$page);$this->assertStringNotContainsString('دیدگاه نمونه',$html);\FilaForge\Studio\Models\Comment::first()->forceFill(['status'=>'approved'])->save();$approved=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($doc,$page);$this->assertStringContainsString('دیدگاه نمونه',$approved);$this->postJson('/filaforge-comments',[...$body,'node'=>'wrong'])->assertNotFound();
 }
}
