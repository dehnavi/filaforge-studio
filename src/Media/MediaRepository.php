<?php
namespace FilaForge\Studio\Media;
use FilaForge\Studio\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class MediaRepository {
 public const TYPES=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','video/mp4'=>'mp4','video/webm'=>'webm'];
 public function store(UploadedFile $file,string $folder='',string $category=''): Media {
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());$size=$file->getSize();
  if(!$file->isValid()||!isset(self::TYPES[$mime])||$size>config('filaforge.media_max_bytes',52428800))throw ValidationException::withMessages(['file'=>'فقط JPG، PNG، WebP، MP4 یا WebM تا سقف ۵۰ مگابایت مجاز است.']);
  $dimensions=null;if(str_starts_with($mime,'image/')){$dimensions=@getimagesize($file->getRealPath());if(!$dimensions||$dimensions[0]*$dimensions[1]>20000000)throw ValidationException::withMessages(['file'=>'ابعاد تصویر معتبر نیست یا بیشتر از ۲۰ مگاپیکسل است.']);}
  $disk=config('filaforge.media_disk','filaforge_media');$hash=hash_file('sha256',$file->getRealPath());$duplicate=Media::where('hash',$hash)->where('disk',$disk)->first();if($duplicate&&Storage::disk($disk)->exists($duplicate->path))return $duplicate;
  $path=Storage::disk($disk)->putFileAs('uploads',$file,Str::ulid().'.'.self::TYPES[$mime]);if(!$path)throw new \RuntimeException('ذخیرهٔ فایل ناموفق بود.');
  try{return Media::create(['name'=>mb_substr(basename($file->getClientOriginalName()),0,200),'disk'=>$disk,'path'=>$path,'mime'=>$mime,'bytes'=>$size,'hash'=>$hash,'width'=>$dimensions[0]??null,'height'=>$dimensions[1]??null,'folder'=>$folder,'category'=>$category]);}catch(\Throwable $e){Storage::disk($disk)->delete($path);throw $e;}
 }
 public function url(Media $media): string {return URL::temporarySignedRoute('filaforge.media.file',now()->addDay(),['media'=>$media->id]);}
 public function resolve(string $value,string $kind): string {
  if(!str_starts_with($value,'media:'))return $value;
  $id=substr($value,6);if(!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/iD',$id))throw new \InvalidArgumentException('Invalid media ID.');
  $media=Media::findOrFail($id);if(!str_starts_with($media->mime,$kind.'/'))throw new \InvalidArgumentException('Media type mismatch.');return $this->url($media);
 }
 public function describe(Media $m): array {return ['id'=>$m->id,'name'=>$m->name,'mime'=>$m->mime,'bytes'=>$m->bytes,'width'=>$m->width,'height'=>$m->height,'alt'=>$m->alt,'caption'=>$m->caption??'','folder'=>$m->folder,'category'=>$m->category??'','originalId'=>$m->original_id,'url'=>$this->url($m),'kind'=>str_starts_with($m->mime,'image/')?'image':'video'];}
 public function editImage(Media $media,array $data): Media {
  if(!str_starts_with($media->mime,'image/'))throw ValidationException::withMessages(['image'=>'فقط تصاویر قابل برش و چرخش هستند.']);
  $bytes=Storage::disk($media->disk)->get($media->path);$image=@imagecreatefromstring($bytes);if(!$image)throw ValidationException::withMessages(['image'=>'تصویر قابل پردازش نیست.']);
  try{
   $width=imagesx($image);$height=imagesy($image);
   $x=$data['x']??0;$y=$data['y']??0;$w=$data['crop_width']??$width;$h=$data['crop_height']??$height;
   if($x<0||$y<0||$w<1||$h<1||$x+$w>$width||$y+$h>$height)throw ValidationException::withMessages(['crop'=>'کادر برش خارج از تصویر است.']);
   $cropped=imagecrop($image,['x'=>$x,'y'=>$y,'width'=>$w,'height'=>$h]);if(!$cropped)throw new \RuntimeException('برش ناموفق بود.');imagedestroy($image);$image=$cropped;
   if(($data['rotate']??0)!==0){$rotated=imagerotate($image,-$data['rotate'],imagecolorallocatealpha($image,0,0,0,127));if(!$rotated)throw new \RuntimeException('چرخش ناموفق بود.');imagedestroy($image);$image=$rotated;}
   if(!empty($data['width'])){$target=$data['width'];$targetH=max(1,(int)round(imagesy($image)*$target/imagesx($image)));if($target*$targetH>20000000)throw ValidationException::withMessages(['width'=>'ابعاد خروجی بیش از حد بزرگ است.']);$scaled=imagescale($image,$target,$targetH);if(!$scaled)throw new \RuntimeException('تغییر اندازه ناموفق بود.');imagedestroy($image);$image=$scaled;}
   imagesavealpha($image,true);ob_start();imagepng($image);$output=ob_get_clean();$path='edits/'.Str::ulid().'.png';$disk=Storage::disk($media->disk);if(!$disk->put($path,$output))throw new \RuntimeException('ذخیره ناموفق بود.');
   try{return Media::create(['name'=>pathinfo($media->name,PATHINFO_FILENAME).' - ویرایش.png','disk'=>$media->disk,'path'=>$path,'mime'=>'image/png','bytes'=>strlen($output),'hash'=>hash('sha256',$output),'width'=>imagesx($image),'height'=>imagesy($image),'alt'=>$media->alt,'caption'=>$media->caption,'folder'=>$media->folder,'category'=>$media->category??'','original_id'=>$media->id]);}catch(\Throwable $e){$disk->delete($path);throw $e;}
  }finally{imagedestroy($image);}
 }
}
