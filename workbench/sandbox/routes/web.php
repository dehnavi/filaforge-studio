<?php
use Illuminate\Support\Facades\Route;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Rendering\BladeRenderer;
Route::get('/', function () {
    $pages=PageLocale::query()->whereHas('page')->get();
    $links='';
    foreach($pages as $page)$links.='<li><strong>'.e($page->title).'</strong> — <a href="/admin/pages/'.e($page->id).'/edit">ویرایش</a> · <a href="/preview/'.e($page->slug).'">خروجی منتشرشده</a></li>';
    return response('<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>محیط تست FilaForge</title><style>body{font:18px system-ui;max-width:1000px;margin:3rem auto;padding:1rem;line-height:2.2}a{color:#4338ca}li{margin-block:1rem}</style><h1>محیط تست مستقل FilaForge Studio</h1><p>برنامه و دیتابیس این محیط محلی و آزمایشی هستند.</p><p><a href="/admin">ورود به پنل مدیریت</a> · <a href="/sections-demo">نمونهٔ درج خروجی در صفحهٔ موجود</a></p><ul>'.$links.'</ul></html>');
});
Route::get('/sections-demo',function(){
    $page=PageLocale::query()->where('locale','fa')->where('slug','authoring-demo')->whereHas('page')->firstOrFail();
    return response('<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>خروجی مستقل بخش‌ها</title></head><body style="font:16px system-ui;max-width:1100px;margin:2rem auto;padding:20px"><header><h1>هدر برنامهٔ میزبان</h1><p>این قالب متعلق به برنامهٔ میزبان است.</p></header><hr><main>'.filaforge_section($page,null,'sandbox-section')->toHtml().'</main><hr><footer>فوتر برنامهٔ میزبان — خروجی بالا بدون وابستگی به این layout درج شده است.</footer></body></html>');
});
Route::get('/preview/{slug}', function (string $slug) {
    $page=PageLocale::where('locale','fa')->where('slug',$slug)->whereHas('page')->firstOrFail();
    abort_unless($revision=$page->publishedRevision,404);
    return app(\FilaForge\Studio\Rendering\HtmlRenderer::class)->publishedResponse($page);
});
