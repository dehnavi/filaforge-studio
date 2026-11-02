<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use FilaForge\Studio\Models\Media;
use FilaForge\Studio\Media\MediaRepository;
use FilaForge\Studio\Models\Page;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Core\DocumentValidator;
final class StudioMediaTest extends TestCase {
 use RefreshDatabase;
 private function admin(){return \App\Models\User::factory()->create(['email'=>'admin@filaforge.test']);}
 public function test_upload_requires_authorized_user(): void {
  $this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('test.jpg')])->assertUnauthorized();
  $this->actingAs(\App\Models\User::factory()->create())->getJson('/filaforge-media')->assertForbidden();
 }
 public function test_image_upload_edit_metadata_and_signed_access(): void {
  Storage::fake('filaforge_media');$this->actingAs($this->admin());
  $uploaded=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('photo.jpg',320,180)])->assertCreated()->json();
  $id=$uploaded['id'];$this->assertSame(320,$uploaded['width']);$this->assertSame(180,$uploaded['height']);
  $this->get('/filaforge-media/'.$id.'/file')->assertForbidden();$this->get($uploaded['url'])->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
  $this->patchJson('/filaforge-media/'.$id,['name'=>'عکس نمونه','alt'=>'متن فارسی','caption'=>'توضیح','folder'=>'نمونه'])->assertOk()->assertJsonPath('alt','متن فارسی');
  $edited=$this->postJson('/filaforge-media/'.$id.'/edit',['x'=>10,'y'=>10,'crop_width'=>200,'crop_height'=>100,'rotate'=>90,'width'=>50])->assertCreated()->json();
  $this->assertNotSame($id,$edited['id']);$this->assertSame($id,$edited['originalId']);$this->assertSame(50,$edited['width']);$this->assertSame(100,$edited['height']);Storage::disk('filaforge_media')->assertExists(Media::find($id)->path);
  $this->postJson('/filaforge-media/'.$id.'/edit',['x'=>300,'y'=>0,'crop_width'=>100,'crop_height'=>100,'rotate'=>0])->assertUnprocessable();
 }
 public function test_invalid_upload_and_used_deletion_are_rejected(): void {
  Storage::fake('filaforge_media');$this->actingAs($this->admin());
  $this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->createWithContent('evil.svg','<svg onload="alert(1)"/>')])->assertUnprocessable();
  $item=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('photo.png',20,20)])->assertCreated()->json();
  $d=DocumentValidator::empty();$node=$d['root'];$node['id']='image';$node['type']='image';$node['props']=['src'=>'media:'.$item['id'],'alt'=>'x','width'=>100];$d['root']['children'][]=$node;
  PageLocale::create(['page_id'=>Page::create()->id,'locale'=>'en','title'=>'Test','slug'=>'test','direction'=>'ltr','draft_document'=>$d]);
  $this->deleteJson('/filaforge-media/'.$item['id'])->assertConflict();
  $html=app(\FilaForge\Studio\Rendering\BladeRenderer::class)->render($d);$this->assertStringContainsString('/filaforge-media/'.$item['id'].'/file',$html);$this->assertStringNotContainsString('src="media:',$html);
 }
 public function test_video_upload_deduplication_and_wrong_media_kind(): void {
  Storage::fake('filaforge_media');$this->actingAs($this->admin());
  $file=UploadedFile::fake()->createWithContent('sample.mp4',file_get_contents(public_path('sandbox-media/sample.mp4')));
  $item=$this->postJson('/filaforge-media',['file'=>$file])->assertCreated()->json();$this->assertSame('video',$item['kind']);
  $again=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->createWithContent('duplicate.mp4',file_get_contents(public_path('sandbox-media/sample.mp4')))])->assertCreated()->json();$this->assertSame($item['id'],$again['id']);
  $this->postJson('/filaforge-media/'.$item['id'].'/edit',['x'=>0,'y'=>0,'crop_width'=>10,'crop_height'=>10,'rotate'=>0])->assertUnprocessable();
  $this->expectException(\InvalidArgumentException::class);app(MediaRepository::class)->resolve('media:'.$item['id'],'image');
 }
 public function test_media_library_filters_folders_categories_and_bulk_organizes_files(): void {
  Storage::fake('filaforge_media');$this->actingAs($this->admin());
  $first=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('first.jpg',40,30)])->assertCreated()->json();
  $second=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('second.jpg',41,30)])->assertCreated()->json();
  $this->patchJson('/filaforge-media/'.$first['id'],['name'=>'تصویر محصول','alt'=>'نمای محصول','caption'=>'کالای تازه','folder'=>'محصولات/پاییز','category'=>'محصول'])->assertOk()->assertJsonPath('folder','محصولات/پاییز')->assertJsonPath('category','محصول');
  $this->patchJson('/filaforge-media/bulk',['ids'=>[$second['id']],'folder'=>'تیم/کارگاه','category'=>'پرسنل'])->assertOk()->assertJsonPath('updated',1);
  $this->getJson('/filaforge-media?folder='.urlencode('محصولات/پاییز'))->assertOk()->assertJsonPath('total',1)->assertJsonPath('items.0.id',$first['id']);
  $this->getJson('/filaforge-media?category='.urlencode('پرسنل'))->assertOk()->assertJsonPath('total',1)->assertJsonPath('folders.0','تیم/کارگاه');
  $this->getJson('/filaforge-media?search='.urlencode('نمای محصول'))->assertOk()->assertJsonPath('total',1);
  $this->patchJson('/filaforge-media/bulk',['ids'=>[$second['id']],'folder'=>'../private','category'=>'x'])->assertUnprocessable();
 }
 public function test_media_folders_can_be_created_and_only_empty_leaf_folders_deleted(): void {
  Storage::fake('filaforge_media');$this->actingAs($this->admin());
  $this->postJson('/filaforge-media/folders',['path'=>'Campaigns/Spring'])->assertOk()->assertJsonPath('path','Campaigns/Spring')->assertJsonPath('folders.0','Campaigns')->assertJsonPath('folders.1','Campaigns/Spring');
  $this->deleteJson('/filaforge-media/folders',['path'=>'Campaigns'])->assertConflict();
  $asset=$this->postJson('/filaforge-media',['file'=>UploadedFile::fake()->image('campaign.png',45,30),'folder'=>'Campaigns/Spring','category'=>'Campaign'])->assertCreated()->assertJsonPath('folder','Campaigns/Spring')->assertJsonPath('category','Campaign')->json();
  $this->deleteJson('/filaforge-media/folders',['path'=>'Campaigns/Spring'])->assertConflict();
  $this->patchJson('/filaforge-media/'.$asset['id'],['name'=>$asset['name'],'folder'=>'','category'=>''])->assertOk();
  $this->deleteJson('/filaforge-media/folders',['path'=>'Campaigns/Spring'])->assertNoContent();
  $this->getJson('/filaforge-media')->assertOk()->assertJsonMissing(['Campaigns/Spring']);
  $this->postJson('/filaforge-media/folders',['path'=>'../private'])->assertUnprocessable();
 }
}
