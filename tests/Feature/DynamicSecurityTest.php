<?php
namespace FilaForge\Studio\Tests\Feature;
use FilaForge\Studio\Tests\TestCase;
use FilaForge\Studio\Models\{Page,PageLocale,Comment};
use FilaForge\Studio\Documents\PageDocuments;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Presets\PresetRegistry;
use FilaForge\Studio\Rendering\BladeRenderer;
use Illuminate\Support\Facades\{Gate,Route};
class_alias(class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class) ? \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class : \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, __NAMESPACE__.'\\CsrfMiddleware');
final class RealCsrf extends CsrfMiddleware {protected function runningUnitTests(){return false;}}
final class DynamicSecurityTest extends TestCase
{
    private function published(): array {
        $this->actingAs($this->user(['email'=>'admin@filaforge.test']));
        $doc=DocumentValidator::empty('fa','rtl');$preset=app(PresetRegistry::class)->instantiate('comments');$doc['root']['children']=[$preset];
        $page=PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'fa','title'=>'Security','slug'=>'security','direction'=>'rtl','draft_document'=>$doc]);
        app(PageDocuments::class)->publish($page,0);
        return [$page->fresh(),['page'=>$page->id,'node'=>$preset['children'][0]['id'],'name'=>'Reader','body'=>'Comment']];
    }
    public function test_comments_require_per_record_permission_even_for_editor(): void {
        [$page,$body]=$this->published();Gate::define('filaforge.comment',fn($user,$record)=>false);
        $this->postJson('/filaforge-comments',$body)->assertForbidden();$this->assertSame(0,Comment::count());
        Gate::define('filaforge.viewComments',fn($user,$record)=>false);
        $html=app(BladeRenderer::class)->render($page->draft_document,$page);$this->assertStringNotContainsString('data-ff-comment',$html);
    }
    public function test_comment_xss_and_sql_payloads_remain_plain_text_and_status_is_pending(): void {
        [$page,$body]=$this->published();$body['name']='<img src=x onerror=alert(1)>';$body['body']="<script>document.cookie</script> '); DROP TABLE users; --";$body['status']='approved';
        $this->postJson('/filaforge-comments',$body)->assertCreated();$comment=Comment::firstOrFail();$this->assertSame('pending',$comment->status);$this->assertSame($body['body'],$comment->body);
        $comment->forceFill(['status'=>'approved'])->save();$html=app(BladeRenderer::class)->render($page->draft_document,$page);
        $this->assertStringContainsString('&lt;script&gt;document.cookie&lt;/script&gt;',$html);$this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;',$html);$this->assertStringNotContainsString('<script>document.cookie',$html);$this->assertDatabaseCount('users',1);
    }
    public function test_comment_forms_reject_honeypot_and_nonpublished_nodes(): void {
        [$page,$body]=$this->published();$this->postJson('/filaforge-comments',[...$body,'website'=>'spam'])->assertUnprocessable();$this->postJson('/filaforge-comments',[...$body,'node'=>'forged'])->assertNotFound();
        $page->forceFill(['published_revision_id'=>null])->save();$this->postJson('/filaforge-comments',$body)->assertNotFound();$this->assertSame(0,Comment::count());
    }
    public function test_real_csrf_validation_and_origin_checks(): void {
        [$page,$body]=$this->published();
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,RealCsrf::class);$this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,RealCsrf::class);
        if (class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)) $this->app->bind(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,RealCsrf::class);
        $this->withSession(['_token'=>'test-csrf-token']);$this->postJson('/filaforge-comments',$body)->assertStatus(419);
        $this->postJson('/filaforge-comments',[...$body,'_token'=>'wrong'])->assertStatus(419);
        $this->withHeader('Origin','https://attacker.invalid')->postJson('/filaforge-comments',[...$body,'_token'=>'test-csrf-token'])->assertForbidden();
        $this->withHeader('Origin','http://localhost')->postJson('/filaforge-comments',[...$body,'_token'=>'test-csrf-token'])->assertCreated();
    }
    public function test_session_settings_diagnostic_requires_production_cookie_settings(): void {
        config(['session.http_only'=>false,'session.secure'=>false,'session.same_site'=>'none','app.debug'=>true,'app.url'=>'http://localhost']);$this->artisan('filaforge:security-check')->assertExitCode(1);
        config(['session.http_only'=>true,'session.secure'=>true,'session.same_site'=>'lax','app.debug'=>false,'app.url'=>'https://example.test']);$this->artisan('filaforge:security-check')->assertExitCode(0);
    }
    public function test_forged_cookie_does_not_authorize_media(): void {
        $this->withCookie(config('session.cookie'),'forged-session-id')->getJson('/filaforge-media')->assertUnauthorized();
    }

    public function test_login_rotates_session_identifier(): void {
        $session=$this->app['session']->driver();$session->start();$before=$session->getId();
        auth()->guard('web')->login($this->user());$this->assertNotSame($before,$session->getId());
    }
    public function test_comment_rate_limit_is_enforced(): void {
        [$page,$body]=$this->published();for($i=0;$i<10;$i++)$this->postJson('/filaforge-comments',$body)->assertCreated();
        $this->postJson('/filaforge-comments',$body)->assertStatus(429);$this->assertSame(10,Comment::count());
    }
    public function test_preview_requires_page_permission_and_masks_invalid_data(): void {
        [$page]=$this->published();$editor=new \FilaForge\Studio\Filament\Resources\Pages\EditPage;$editor->pageId=$page->id;
        $this->assertArrayHasKey('sources',$editor->dynamicCatalog());
        $this->assertArrayHasKey('html',$editor->previewDocument($page->draft_document));
        $this->assertArrayHasKey('error',$editor->previewDocument($page->draft_document,['root'=>-1]));
        Gate::define('filaforge.update',fn($user)=>false);$this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);$editor->dynamicCatalog();
    }

    public function test_editor_permission_does_not_grant_global_comment_moderation(): void {
        [$page,$body]=$this->published();$this->postJson('/filaforge-comments',$body)->assertCreated();$comment=Comment::firstOrFail();
        $this->assertFalse(Gate::allows('viewAny',Comment::class));$this->assertFalse(Gate::allows('update',$comment));
        Gate::define('filaforge.moderateComments',fn($user,$record)=>$record->id===$page->id);
        $this->assertTrue(Gate::allows('update',$comment));$this->assertFalse(Gate::allows('viewAny',Comment::class));
        Gate::define('filaforge.moderateAllComments',fn($user)=>true);$this->assertTrue(Gate::allows('viewAny',Comment::class));
    }
}
