<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\Tests\Fixtures\Product;
use FilaForge\Studio\DynamicData\{DataRegistry,ArrayDataProvider,EloquentDataProvider,DataContext,ShortcodeRegistry,ShortcodeParser,Formatters};
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Rendering\{BladeRenderer,HtmlRenderer};
use Illuminate\Support\Facades\{Schema,DB};
use Illuminate\Database\Schema\Blueprint;
final class DynamicDataTest extends TestCase
{
    private function node(string $id,string $type,array $props): array {return ['id'=>$id,'type'=>$type,'widgetVersion'=>1,'props'=>$props,'children'=>[],'styles'=>[],'responsive'=>[],'bindings'=>[],'visibility'=>[],'meta'=>[]];}
    private function doc(array $nodes): array {$doc=DocumentValidator::empty('fa','rtl');$doc['root']['children']=$nodes;return $doc;}
    private function heading(string $source='company',string $field='name'): array {$n=$this->node('title','heading',['text'=>'fallback','tag'=>'h2']);$n['bindings']=['text'=>['provider'=>$source,'field'=>$field,'fallback'=>'Missing']];return $n;}
    private function render(array $doc,?DataContext $context=null): string {return app(BladeRenderer::class)->render($doc,null,$context);}
    private function products(): void
    {
        Schema::create('test_products',fn(Blueprint $t)=>[$t->id(),$t->string('tenant'),$t->string('title'),$t->integer('price'),$t->boolean('published'),$t->string('secret')]);
        Product::insert([['tenant'=>'a','title'=>'Public A','price'=>10,'published'=>true,'secret'=>'NEVER-A'],['tenant'=>'b','title'=>'Public B','price'=>20,'published'=>true,'secret'=>'NEVER-B'],['tenant'=>'a','title'=>'Draft A','price'=>30,'published'=>false,'secret'=>'NEVER-DRAFT']]);
        app(DataRegistry::class)->register(new EloquentDataProvider('products','Products',['title'=>['label'=>'Title','type'=>'text'],'price'=>['label'=>'Price','type'=>'number']],fn($c)=>Product::query()->where('tenant',$c->tenantKey)->where('published',true),fn($c)=>in_array($c->tenantKey,['a','b'],true),true,['search'=>['column'=>'title','rules'=>['string','max:100'],'operator'=>'eq']],['price'=>['label'=>'Price','column'=>'price','direction'=>'asc']]));
    }
    public function test_binding_escapes_xss_and_overrides_formatted_static_content(): void
    {
        config(['filaforge.company'=>['name'=>'<script>document.cookie</script>']]);$node=$this->heading();$node['props']['formatted']=['text'=>'OLD'];
        $html=$this->render($this->doc([$node]));
        $this->assertStringContainsString('&lt;script&gt;document.cookie&lt;/script&gt;',$html);$this->assertStringNotContainsString('>OLD<',$html);$this->assertStringNotContainsString('<script>document.cookie',$html);
        $node=$this->node('button','button',['text'=>'Go','url'=>'#']);$node['bindings']=['url'=>['provider'=>'company','field'=>'url','fallback'=>'#safe']];config(['filaforge.company.url'=>'javascript:alert(1)']);
        $html=$this->render($this->doc([$node]));$this->assertStringContainsString('href="#safe"',$html);$this->assertStringNotContainsString('javascript:',$html);
    }
    public function test_private_sources_never_run_in_public_context_even_for_logged_in_admin(): void
    {
        $calls=0;app(DataRegistry::class)->register(new ArrayDataProvider('private','Private',['secret'=>['label'=>'Secret','type'=>'text']],function($c)use(&$calls){$calls++;return [['secret'=>'PRIVATE']];},fn($c)=>true,false));
        $this->actingAs($this->user(['email'=>'admin@filaforge.test']));$doc=$this->doc([$this->heading('private','secret')]);
        $this->assertStringNotContainsString('PRIVATE',$this->render($doc));$this->assertSame(0,$calls);
        $html=$this->render($doc,new DataContext(user:auth()->user(),publicAudience:false));$this->assertStringContainsString('PRIVATE',$html);$this->assertSame(1,$calls);
        $this->assertStringNotContainsString('PRIVATE',$this->render($doc));$this->assertSame(1,$calls);
    }
    public function test_tenant_scope_projection_and_sql_bindings_are_enforced(): void
    {
        $this->products();$node=$this->heading('products','title');$doc=$this->doc([$node]);
        $html=$this->render($doc,new DataContext(tenantKey:'a'));$this->assertStringContainsString('Public A',$html);foreach(['Public B','Draft A','NEVER'] as $secret)$this->assertStringNotContainsString($secret,$html);
        $this->assertStringContainsString('Public B',$this->render($doc,new DataContext(tenantKey:'b')));
        $payload="' OR 1=1 --";$node['bindings']['text']['query']=['filters'=>['search'=>$payload]];$queries=[];DB::listen(function($q)use(&$queries){$queries[]=$q;});
        $html=$this->render($this->doc([$node]),new DataContext(tenantKey:'a'));$this->assertStringContainsString('Missing',$html);$this->assertSame(3,Product::count());
        $bound=false;foreach($queries as $query){if(in_array($payload,$query->bindings,true)){$bound=true;$this->assertStringNotContainsString($payload,$query->sql);}}$this->assertTrue($bound);
        $node['bindings']['text']['query']=['sort'=>'price desc; drop table users'];$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($this->doc([$node]));
    }
    public function test_unknown_fields_and_query_options_are_rejected(): void
    {
        foreach([['field'=>'password'],['query'=>['model'=>'App\\Models\\User']],['query'=>['filters'=>['tenant'=>'other']]],['formatter'=>'raw']] as $change){
            $node=$this->heading();$node['bindings']['text']=array_merge($node['bindings']['text'],$change);
            try{app(DocumentValidator::class)->validate($this->doc([$node]));$this->fail('Unsafe binding accepted.');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}
        }
        $node=$this->heading('@item','name');$this->expectException(\InvalidArgumentException::class);app(DocumentValidator::class)->validate($this->doc([$node]));
    }
    public function test_loops_conditions_pagination_and_empty_states(): void
    {
        $rows=[['name'=>'First','price'=>10],['name'=>'Second','price'=>20],['name'=>'Third','price'=>30]];
        app(DataRegistry::class)->register(new ArrayDataProvider('catalog','Catalog',['name'=>['label'=>'Name','type'=>'text'],'price'=>['label'=>'Price','type'=>'number']],fn($c)=>$rows,fn($c)=>true,true));
        $child=$this->heading('@item','name');$child['visibility']=['provider'=>'@item','field'=>'price','operator'=>'gte','value'=>15];
        $loop=$this->node('loop','query-loop',['provider'=>'catalog','query'=>['limit'=>2],'emptyText'=>'No results']);$loop['children']=[$child];$doc=$this->doc([$loop]);
        $html=$this->render($doc);$this->assertStringNotContainsString('First',$html);$this->assertStringContainsString('Second',$html);$this->assertStringContainsString('ff_page%5Bloop%5D=2',$html);
        $html=$this->render($doc,new DataContext(pagination:['loop'=>2]));$this->assertStringContainsString('Third',$html);$this->assertStringNotContainsString('Second',$html);
        $html=$this->render($doc,new DataContext(pagination:['loop'=>1000]));$this->assertStringContainsString('No results',$html);
        $loop['children']=[$this->heading('@item','name')];$loop['props']['query']['limit']=3;$html=$this->render($this->doc([$loop]));preg_match_all('/data-node="([^"]+)"/',$html,$ids);$this->assertCount(count(array_unique($ids[1])),$ids[1]);
    }
    public function test_request_local_memoization_and_no_cross_render_cache(): void
    {
        $calls=0;app(DataRegistry::class)->register(new ArrayDataProvider('memo','Memo',['name'=>['label'=>'Name','type'=>'text']],function($c)use(&$calls){$calls++;return [['name'=>$c->tenantKey]];},fn($c)=>true,true));
        $a=$this->heading('memo','name');$b=$a;$b['id']='second';$doc=$this->doc([$a,$b]);$renderer=app(BladeRenderer::class);
        $this->assertStringContainsString('tenant-a',$renderer->render($doc,null,new DataContext(tenantKey:'tenant-a')));$this->assertSame(1,$calls);
        $html=$renderer->render($doc,null,new DataContext(tenantKey:'tenant-b'));$this->assertStringNotContainsString('tenant-a',$html);$this->assertStringContainsString('tenant-b',$html);$this->assertSame(2,$calls);
    }
    public function test_shortcodes_are_parsed_as_data_not_blade_or_php(): void
    {
        config(['filaforge.company.phone'=>'12345']);$doc=app(ShortcodeParser::class)->parse('Phone: [[field source="company" name="phone"]] {{ phpinfo() }} [[component name="contact-card"]]');
        $html=$this->render($doc);$this->assertStringContainsString('12345',$html);$this->assertStringContainsString('{{ phpinfo() }}',$html);$this->assertStringNotContainsString('PHP Version',$html);
        foreach(['[[field source="company" name="password"]]','[[component name="App-Class"]]','[[php code="system()"]]','[[field source="company" source="company" name="name"]]'] as $input){try{app(ShortcodeParser::class)->parse($input);$this->fail('Unsafe shortcode accepted.');}catch(\InvalidArgumentException){$this->addToAssertionCount(1);}}
    }
    public function test_formatters_never_return_raw_html_objects(): void
    {
        $formatters=app(Formatters::class);$this->assertSame('۱۲۳',$formatters->format(123,'persian_number','fa'));$this->assertSame('1,234.50',$formatters->format(1234.5,'money','en'));
        if(class_exists(\IntlDateFormatter::class))$this->assertSame('۱۴۰۵/۰۱/۰۱',$formatters->format('2026-03-21T12:00:00+03:30','persian_date','fa'));
        $formatters->register('unsafe',fn($v)=>new \Illuminate\Support\HtmlString('<script>bad</script>'));
        $node=$this->heading();$node['bindings']['text']['formatter']='unsafe';$html=$this->render($this->doc([$node]));$this->assertStringNotContainsString('<script>bad',$html);$this->assertStringContainsString('Missing',$html);
    }
    public function test_unknown_provider_during_public_render_uses_fallback(): void
    {
        $node=$this->heading('removed','name');$html=$this->render($this->doc([$node]));$this->assertStringContainsString('Missing',$html);
    }
    public function test_expansion_and_nested_loop_budgets_are_enforced(): void
    {
        $loop=$this->node('loop','query-loop',['provider'=>'company','query'=>[],'emptyText'=>'']);$loop2=$loop;$loop2['id']='loop2';$loop3=$loop;$loop3['id']='loop3';$loop4=$loop;$loop4['id']='loop4';$loop3['children']=[$loop4];$loop2['children']=[$loop3];$loop['children']=[$loop2];
        $this->expectException(\InvalidArgumentException::class);$this->render($this->doc([$loop]));
    }
    public function test_full_dynamic_responses_are_not_shared_cached(): void
    {
        $response=app(HtmlRenderer::class)->response($this->doc([$this->heading()]));$this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
    }

    public function test_component_permission_and_recursive_expansion_are_bounded(): void
    {
        $registry=app(ShortcodeRegistry::class);$calls=0;
        $registry->register('private-card','Private',[],function()use(&$calls){$calls++;return $this->node('private-node','text',['text'=>'SECRET COMPONENT']);},fn($context)=>true,false);
        $node=$this->node('component','shortcode',['name'=>'private-card','arguments'=>[]]);
        $this->assertStringNotContainsString('SECRET COMPONENT',$this->render($this->doc([$node])));$this->assertSame(0,$calls);
        $this->assertStringContainsString('SECRET COMPONENT',$this->render($this->doc([$node]),new DataContext(user:$this->user(),publicAudience:false)));$this->assertSame(1,$calls);
        $cycles=0;$registry->register('cycle','Cycle',[],function()use(&$cycles){$cycles++;return $this->node('cycle-node','shortcode',['name'=>'cycle','arguments'=>[]]);},fn($context)=>true,true);
        $this->render($this->doc([$this->node('start','shortcode',['name'=>'cycle','arguments'=>[]])]));$this->assertLessThanOrEqual(16,$cycles);
    }
    public function test_provider_exception_uses_fallback_without_showing_database_details(): void
    {
        app(DataRegistry::class)->register(new ArrayDataProvider('broken','Broken',['name'=>['label'=>'Name','type'=>'text']],fn($context)=>throw new \RuntimeException('SQLSTATE secret database password'),fn($context)=>true,true));
        $html=$this->render($this->doc([$this->heading('broken','name')]));$this->assertStringContainsString('Missing',$html);$this->assertStringNotContainsString('SQLSTATE',$html);$this->assertStringNotContainsString('password',$html);
    }
}
